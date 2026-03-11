<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("workflow_step_executions", function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger("run_id");
            $table->unsignedInteger("step_index");
            $table->string("step_name");
            $table->string("status");
            $table->unsignedInteger("attempt");
            $table->string("idempotency_key")->nullable();
            $table->timestamp("started_at")->nullable();
            $table->timestamp("finished_at")->nullable();
            $table->unsignedInteger("duration_ms")->nullable();
            $table->text("error_summary")->nullable();
            $table->timestamps();

            $table->unique(["run_id", "step_index", "attempt"]);
            $table->index(["run_id", "step_index", "status"]);
            $table->index(["status", "finished_at"]);
            $table->foreign("run_id")->references("id")->on("workflow_runs")->onDelete("cascade");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("workflow_step_executions");
    }
};
