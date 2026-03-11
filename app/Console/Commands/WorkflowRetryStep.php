<?php

namespace App\Console\Commands;

use App\AsyncOrchestration\Domain\Run\RunStateMachine;
use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use App\AsyncOrchestration\Infrastructure\Queue\ExecuteWorkflowStepJob;
use Illuminate\Console\Command;

final class WorkflowRetryStep extends Command
{
    protected $signature = "workflow:retry-step {run_id} {step_index}";

    protected $description = "Retry a specific workflow step";

    public function handle(): int
    {
        $runId = (int) $this->argument("run_id");
        $stepIndex = (int) $this->argument("step_index");

        $run = WorkflowRun::find($runId);
        if ($run === null) {
            $this->error("Run not found.");
            return self::FAILURE;
        }

        $stateMachine = new RunStateMachine();
        if (!$stateMachine->canTransition($run->status, RunStatus::RUNNING)) {
            $this->error("Run status cannot transition to RUNNING.");
            return self::FAILURE;
        }

        $run->status = RunStatus::RUNNING;
        $run->current_step = $stepIndex;
        $run->next_retry_at = null;
        $run->save();

        ExecuteWorkflowStepJob::dispatch($run->id, $stepIndex, $run->tenant_id)
            ->onQueue(config("async_orchestration.default_queue"));

        $this->info("Retry enqueued.");

        return self::SUCCESS;
    }
}
