<?php

namespace App\AsyncOrchestration\Infrastructure\Queue\Middleware;

use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class TenantRateLimit
{
    public function handle($job, $next): void
    {
        $tenantId = $job->tenantId ?? null;
        $enabled = (bool) config("async_orchestration.rate_limit.enabled");

        if (!$enabled || $tenantId === null) {
            $next($job);
            return;
        }

        $key = "async_orch_tenant:{$tenantId}";
        $max = (int) config("async_orchestration.rate_limit.per_tenant");
        $decay = (int) config("async_orchestration.rate_limit.decay_seconds");

        $tooMany = RateLimiter::tooManyAttempts($key, $max);
        if ($tooMany) {
            $job->release($decay);
            return;
        }

        RateLimiter::hit($key, $decay);

        $next($job);
    }
}
