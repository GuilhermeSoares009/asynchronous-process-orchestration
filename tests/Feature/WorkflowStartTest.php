<?php

namespace Tests\Feature;

use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkflowStartTest extends TestCase
{
    use RefreshDatabase;

    public function testStartWorkflowCreatesRun(): void
    {
        $payload = [
            "context" => ["source" => "upload"],
            "correlation_id" => "corr-123",
            "tenant_id" => "tenant-a",
        ];

        $response = $this->postJson("/api/workflows/import_users/start", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                "run_id",
                "status",
                "current_step",
            ]);

        $runId = (int) $response->json("run_id");
        $run = WorkflowRun::find($runId);

        $this->assertNotNull($run);
        $this->assertSame(RunStatus::RUNNING, $run->status);
        $this->assertSame("import_users", $run->type);
        $this->assertSame("corr-123", $run->correlation_id);
        $this->assertSame("tenant-a", $run->tenant_id);
        $this->assertSame("upload", $run->context_json["source"] ?? null);
        $this->assertTrue($run->context_json["validated"] ?? false);
    }
}
