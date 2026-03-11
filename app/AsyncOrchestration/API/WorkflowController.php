<?php

namespace App\AsyncOrchestration\API;

use App\AsyncOrchestration\API\Requests\StartWorkflowRequest;
use App\AsyncOrchestration\API\Requests\StatusWorkflowRequest;
use App\AsyncOrchestration\API\Requests\ReplayWorkflowRequest;
use App\AsyncOrchestration\Application\CancelPauseResumeService;
use App\AsyncOrchestration\Application\ReplayService;
use App\AsyncOrchestration\Application\StartWorkflowService;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

final class WorkflowController extends Controller
{
    public function __construct(
        private StartWorkflowService $startWorkflow,
        private CancelPauseResumeService $cancelPauseResume,
        private ReplayService $replayService
    ) {
    }

    public function start(StartWorkflowRequest $request, string $type): JsonResponse
    {
        try {
            $run = $this->startWorkflow->start(
                $type,
                $request->input("context", []),
                $request->input("correlation_id"),
                $request->input("tenant_id")
            );
        } catch (InvalidArgumentException $error) {
            return response()->json([
                "message" => $error->getMessage(),
            ], 422);
        }

        return response()->json([
            "run_id" => $run->id,
            "status" => $run->status,
            "current_step" => $run->current_step,
        ], 201);
    }

    public function status(StatusWorkflowRequest $request, int $runId): JsonResponse
    {
        $run = WorkflowRun::findOrFail($runId);

        return response()->json([
            "run_id" => $run->id,
            "type" => $run->type,
            "status" => $run->status,
            "current_step" => $run->current_step,
            "context" => $run->context_json,
            "attempts_total" => $run->attempts_total,
            "last_error_code" => $run->last_error_code,
            "last_error_message" => $run->last_error_message,
        ]);
    }

    public function pause(StatusWorkflowRequest $request, int $runId): JsonResponse
    {
        $run = WorkflowRun::findOrFail($runId);
        $run = $this->cancelPauseResume->pause($run);

        return response()->json([
            "run_id" => $run->id,
            "status" => $run->status,
        ]);
    }

    public function resume(StatusWorkflowRequest $request, int $runId): JsonResponse
    {
        $run = WorkflowRun::findOrFail($runId);
        $run = $this->cancelPauseResume->resume($run);

        return response()->json([
            "run_id" => $run->id,
            "status" => $run->status,
        ]);
    }

    public function cancel(StatusWorkflowRequest $request, int $runId): JsonResponse
    {
        $run = WorkflowRun::findOrFail($runId);
        $run = $this->cancelPauseResume->cancel($run);

        return response()->json([
            "run_id" => $run->id,
            "status" => $run->status,
        ]);
    }

    public function replay(ReplayWorkflowRequest $request, int $runId): JsonResponse
    {
        $run = WorkflowRun::findOrFail($runId);

        try {
            $run = $this->replayService->replay($run, (int) $request->input("step_index"));
        } catch (InvalidArgumentException $error) {
            return response()->json([
                "message" => $error->getMessage(),
            ], 422);
        }

        return response()->json([
            "run_id" => $run->id,
            "status" => $run->status,
            "current_step" => $run->current_step,
        ]);
    }
}
