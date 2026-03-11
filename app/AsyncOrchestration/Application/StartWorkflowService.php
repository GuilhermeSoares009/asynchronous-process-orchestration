<?php

namespace App\AsyncOrchestration\Application;

use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use App\AsyncOrchestration\Domain\WorkflowTypes\WorkflowTypeRegistry;
use App\AsyncOrchestration\Infrastructure\Queue\ExecuteWorkflowStepJob;
use InvalidArgumentException;

final class StartWorkflowService
{
    public function __construct(
        private WorkflowTypeRegistry $registry
    ) {
    }

    public function start(
        string $type,
        array $context = [],
        ?string $correlationId = null,
        ?string $tenantId = null
    ): WorkflowRun {
        if (!$this->registry->has($type)) {
            throw new InvalidArgumentException("Unknown workflow type: {$type}");
        }

        $run = WorkflowRun::create([
            "type" => $type,
            "status" => RunStatus::RUNNING,
            "current_step" => 0,
            "context_json" => $context,
            "correlation_id" => $correlationId,
            "tenant_id" => $tenantId,
            "attempts_total" => 0,
            "started_at" => now(),
        ]);

        ExecuteWorkflowStepJob::dispatch($run->id, 0, $run->tenant_id)
            ->onQueue(config("async_orchestration.default_queue"));

        return $run;
    }
}
