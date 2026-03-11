<?php

namespace App\AsyncOrchestration\Domain\Run;

use Illuminate\Database\Eloquent\Model;

final class WorkflowStepExecution extends Model
{
    protected $table = "workflow_step_executions";

    protected $fillable = [
        "run_id",
        "step_index",
        "step_name",
        "status",
        "attempt",
        "idempotency_key",
        "started_at",
        "finished_at",
        "duration_ms",
        "error_summary",
    ];

    protected $casts = [
        "started_at" => "datetime",
        "finished_at" => "datetime",
    ];
}
