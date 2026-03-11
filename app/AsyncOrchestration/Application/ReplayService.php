<?php

namespace App\AsyncOrchestration\Application;

use App\AsyncOrchestration\Domain\Run\RunStateMachine;
use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use App\AsyncOrchestration\Infrastructure\Queue\ExecuteWorkflowStepJob;
use InvalidArgumentException;

final class ReplayService
{
    private RunStateMachine $stateMachine;

    public function __construct()
    {
        $this->stateMachine = new RunStateMachine();
    }

    public function replay(WorkflowRun $run, int $stepIndex): WorkflowRun
    {
        if (!in_array($run->status, [RunStatus::FAILED, RunStatus::DEAD], true)) {
            throw new InvalidArgumentException("Replay allowed only for FAILED or DEAD runs.");
        }

        if (!$this->stateMachine->canTransition($run->status, RunStatus::RUNNING)) {
            throw new InvalidArgumentException("Run status cannot transition to RUNNING.");
        }

        $run->status = RunStatus::RUNNING;
        $run->current_step = $stepIndex;
        $run->next_retry_at = null;
        $run->save();

        ExecuteWorkflowStepJob::dispatch($run->id, $stepIndex, $run->tenant_id)
            ->onQueue(config("async_orchestration.default_queue"));

        return $run;
    }
}
