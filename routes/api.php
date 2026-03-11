<?php

use App\AsyncOrchestration\API\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix("workflows")->group(function (): void {
    Route::post("{type}/start", [WorkflowController::class, "start"]);
    Route::get("runs/{runId}", [WorkflowController::class, "status"]);
    Route::post("runs/{runId}/pause", [WorkflowController::class, "pause"]);
    Route::post("runs/{runId}/resume", [WorkflowController::class, "resume"]);
    Route::post("runs/{runId}/cancel", [WorkflowController::class, "cancel"]);
    Route::post("runs/{runId}/replay", [WorkflowController::class, "replay"]);
});
