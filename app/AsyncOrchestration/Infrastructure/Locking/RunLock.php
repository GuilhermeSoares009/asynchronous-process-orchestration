<?php

namespace App\AsyncOrchestration\Infrastructure\Locking;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

final class RunLock
{
    public function acquire(int $runId): ?Lock
    {
        $ttl = (int) config("async_orchestration.lock_ttl_seconds");
        $lock = Cache::lock($this->key($runId), $ttl);

        if (!$lock->get()) {
            return null;
        }

        return $lock;
    }

    private function key(int $runId): string
    {
        return "async_orch:run:{$runId}";
    }
}
