<?php

return [
    "default_queue" => env("ASYNC_ORCH_QUEUE", "default"),
    "max_attempts" => env("ASYNC_ORCH_MAX_ATTEMPTS", 5),
    "backoff_seconds" => [5, 30, 120],
    "lock_ttl_seconds" => env("ASYNC_ORCH_LOCK_TTL", 300),
    "workflows" => [
        App\AsyncOrchestration\Domain\WorkflowTypes\ImportUsersWorkflow::class,
    ],
    "rate_limit" => [
        "enabled" => env("ASYNC_ORCH_RATE_LIMIT", false),
        "per_tenant" => env("ASYNC_ORCH_RATE_LIMIT_PER_TENANT", 10),
        "decay_seconds" => env("ASYNC_ORCH_RATE_LIMIT_DECAY", 60),
    ],
];
