<?php

namespace App\AsyncOrchestration\Application;

use App\AsyncOrchestration\Domain\Run\RunStateMachine;
use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use App\AsyncOrchestration\Infrastructure\Queue\ExecuteWorkflowStepJob;
use InvalidArgumentException;

final class CancelPauseResumeService
{
    private RunStateMachine $stateMachine;

    public function __construct()
    {
        $this->stateMachine = new RunStateMachine();
    }

    public function pause(WorkflowRun $run): WorkflowRun
    {
        $this->stateMachine->assertTransition($run->status, RunStatus::PAUSED);

        $run->status = RunStatus::PAUSED;
        $run->save();

        return $run;
    }

    public function resume(WorkflowRun $run): WorkflowRun
    {
        $this->stateMachine->assertTransition($run->status, RunStatus::RUNNING);

        $run->status = RunStatus::RUNNING;
        $run->next_retry_at = null;
        $run->save();

        ExecuteWorkflowStepJob::dispatch($run->id, $run->current_step, $run->tenant_id)
            ->onQueue(config("async_orchestration.default_queue"));

        return $run;
    }

    public function cancel(WorkflowRun $run): WorkflowRun
    {
        if (!$this->stateMachine->canTransition($run->status, RunStatus::CANCELLED)) {
            throw new InvalidArgumentException("Run status cannot transition to CANCELLED.");
        }

        $run->status = RunStatus::CANCELLED;
        $run->finished_at = now();
        $run->save();

        return $run;
    }
}
