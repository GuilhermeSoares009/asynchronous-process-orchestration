<?php

namespace App\AsyncOrchestration\Domain\Run;

use Illuminate\Database\Eloquent\Model;

final class WorkflowRun extends Model
{
    protected $table = "workflow_runs";

    protected $fillable = [
        "type",
        "status",
        "current_step",
        "context_json",
        "correlation_id",
        "tenant_id",
        "attempts_total",
        "next_retry_at",
        "started_at",
        "finished_at",
        "last_error_code",
        "last_error_message",
    ];

    protected $casts = [
        "context_json" => "array",
        "next_retry_at" => "datetime",
        "started_at" => "datetime",
        "finished_at" => "datetime",
    ];
}
