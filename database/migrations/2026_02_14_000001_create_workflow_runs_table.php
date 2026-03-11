<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("workflow_runs", function (Blueprint $table): void {
            $table->id();
            $table->string("type");
            $table->string("status");
            $table->unsignedInteger("current_step")->default(0);
            $table->json("context_json")->nullable();
            $table->string("correlation_id")->nullable();
            $table->string("tenant_id")->nullable();
            $table->unsignedInteger("attempts_total")->default(0);
            $table->timestamp("next_retry_at")->nullable();
            $table->timestamp("started_at")->nullable();
            $table->timestamp("finished_at")->nullable();
            $table->string("last_error_code")->nullable();
            $table->text("last_error_message")->nullable();
            $table->timestamps();

            $table->index(["status", "updated_at"]);
            $table->index(["type", "status", "updated_at"]);
            $table->index(["tenant_id", "status", "updated_at"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("workflow_runs");
    }
};
