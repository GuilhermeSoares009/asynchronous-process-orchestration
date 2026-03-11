<?php

namespace Tests\Feature;

use App\AsyncOrchestration\Domain\Run\RunStatus;
use App\AsyncOrchestration\Domain\Run\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkflowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function testStatusReturnsRunInfo(): void
    {
        $run = WorkflowRun::create([
            "type" => "import_users",
            "status" => RunStatus::RUNNING,
            "current_step" => 1,
            "context_json" => ["source" => "upload"],
        ]);

        $response = $this->getJson("/api/workflows/runs/{$run->id}");

        $response->assertOk()
            ->assertJson([
                "run_id" => $run->id,
                "type" => "import_users",
                "status" => RunStatus::RUNNING,
                "current_step" => 1,
            ]);
    }
}
