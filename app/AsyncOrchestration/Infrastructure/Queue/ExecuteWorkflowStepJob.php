<?php

namespace App\AsyncOrchestration\Infrastructure\Queue;

use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\RunStateMachine;
use App\AsyncOrchestration\Domain\Run\StepStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use App\AsyncOrchestration\Domain\Run\WorkflowStepExecution;
use App\AsyncOrchestration\Domain\Steps\Step;
use App\AsyncOrchestration\Domain\WorkflowTypes\WorkflowTypeRegistry;
use App\AsyncOrchestration\Infrastructure\Locking\RunLock;
use App\AsyncOrchestration\Infrastructure\Queue\Middleware\TenantRateLimit;
use App\AsyncOrchestration\Ops\Logging\StructuredLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ExecuteWorkflowStepJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $runId,
        public int $stepIndex,
        public ?string $tenantId = null
    ) {
    }

    public function middleware(): array
    {
        return [new TenantRateLimit()];
    }

    public function handle(WorkflowTypeRegistry $registry, RunLock $runLock, RunStateMachine $stateMachine): void
    {
        $lock = $runLock->acquire($this->runId);
        if ($lock === null) {
            return;
        }

        $run = WorkflowRun::find($this->runId);

        if ($run === null) {
            StructuredLogger::warning("workflow_run_missing", ["run_id" => $this->runId]);
            $lock->release();
            return;
        }

        if ($run->status !== RunStatus::RUNNING) {
            if (!$this->canResumeFailedRun($run)) {
                $lock->release();
                return;
            }

            $run->status = RunStatus::RUNNING;
            $run->next_retry_at = null;
            $run->save();
        }

        if ($run->current_step !== $this->stepIndex) {
            $lock->release();
            return;
        }

        $workflow = $registry->get($run->type);
        if ($workflow === null) {
            $this->markRunFailed($run, "UNKNOWN_WORKFLOW", "Unknown workflow type");
            $lock->release();
            return;
        }

        $steps = $workflow->steps();
        if ($this->stepIndex >= count($steps)) {
            $stateMachine->assertTransition($run->status, RunStatus::SUCCEEDED);
            $run->status = RunStatus::SUCCEEDED;
            $run->finished_at = now();
            $run->save();
            $lock->release();
            return;
        }

        if ($this->stepAlreadySucceeded($run->id, $this->stepIndex)) {
            $this->advanceRun($run);
            $lock->release();
            return;
        }

        $stepClass = $steps[$this->stepIndex];
        $step = app($stepClass);
        if (!$step instanceof Step) {
            $this->markRunFailed($run, "INVALID_STEP", "Step does not implement interface");
            $lock->release();
            return;
        }

        $attempt = $this->nextAttempt($run->id, $this->stepIndex);
        $execution = WorkflowStepExecution::create([
            "run_id" => $run->id,
            "step_index" => $this->stepIndex,
            "step_name" => $step->name(),
            "status" => StepStatus::RUNNING,
            "attempt" => $attempt,
            "started_at" => now(),
        ]);

        $startedAt = microtime(true);

        try {
            $context = $step->handle($run->context_json ?? []);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $execution->status = StepStatus::SUCCEEDED;
            $execution->finished_at = now();
            $execution->duration_ms = $durationMs;
            $execution->save();

            $run->context_json = $context;
            $run->current_step = $this->stepIndex + 1;
            $run->attempts_total = $run->attempts_total + 1;
            $run->save();

            self::dispatch($run->id, $run->current_step, $run->tenant_id)
                ->onQueue(config("async_orchestration.default_queue"));
            $lock->release();
        } catch (Throwable $error) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $execution->status = StepStatus::FAILED;
            $execution->finished_at = now();
            $execution->duration_ms = $durationMs;
            $execution->error_summary = $error->getMessage();
            $execution->save();

            $this->handleFailure($run, $step, $attempt, $error->getMessage());
            $lock->release();
        }
    }

    private function stepAlreadySucceeded(int $runId, int $stepIndex): bool
    {
        return WorkflowStepExecution::query()
            ->where("run_id", $runId)
            ->where("step_index", $stepIndex)
            ->where("status", StepStatus::SUCCEEDED)
            ->exists();
    }

    private function nextAttempt(int $runId, int $stepIndex): int
    {
        $count = WorkflowStepExecution::query()
            ->where("run_id", $runId)
            ->where("step_index", $stepIndex)
            ->count();

        return $count + 1;
    }

    private function advanceRun(WorkflowRun $run): void
    {
        $run->current_step = $run->current_step + 1;
        $run->save();

        self::dispatch($run->id, $run->current_step, $run->tenant_id)
            ->onQueue(config("async_orchestration.default_queue"));
    }

    private function handleFailure(WorkflowRun $run, Step $step, int $attempt, string $message): void
    {
        $run->attempts_total = $run->attempts_total + 1;

        if ($attempt >= $step->maxAttempts()) {
            $this->markRunDead($run, "MAX_ATTEMPTS", $message);
            return;
        }

        $delay = $this->resolveBackoffSeconds($step, $attempt);

        $run->status = RunStatus::FAILED;
        $run->last_error_message = $message;
        $run->next_retry_at = now()->addSeconds($delay);
        $run->save();

        self::dispatch($run->id, $this->stepIndex, $run->tenant_id)
            ->delay($delay)
            ->onQueue(config("async_orchestration.default_queue"));
    }

    private function canResumeFailedRun(WorkflowRun $run): bool
    {
        if ($run->status !== RunStatus::FAILED) {
            return false;
        }

        if ($run->next_retry_at === null) {
            return false;
        }

        return $run->next_retry_at->isPast();
    }

    private function resolveBackoffSeconds(Step $step, int $attempt): int
    {
        $backoff = $step->backoffSeconds();
        $index = max(0, $attempt - 1);

        if (!array_key_exists($index, $backoff)) {
            return (int) end($backoff);
        }

        return (int) $backoff[$index];
    }

    private function markRunFailed(WorkflowRun $run, string $code, string $message): void
    {
        $stateMachine = new RunStateMachine();
        if ($stateMachine->canTransition($run->status, RunStatus::FAILED)) {
            $run->status = RunStatus::FAILED;
        }
        $run->status = RunStatus::FAILED;
        $run->last_error_code = $code;
        $run->last_error_message = $message;
        $run->save();
    }

    private function markRunDead(WorkflowRun $run, string $code, string $message): void
    {
        $stateMachine = new RunStateMachine();
        if ($stateMachine->canTransition($run->status, RunStatus::DEAD)) {
            $run->status = RunStatus::DEAD;
        }
        $run->status = RunStatus::DEAD;
        $run->last_error_code = $code;
        $run->last_error_message = $message;
        $run->finished_at = now();
        $run->save();
    }
}
