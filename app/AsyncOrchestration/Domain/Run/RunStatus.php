<?php

namespace App\AsyncOrchestration\Domain\Run;

final class RunStatus
{
    public const RUNNING = "RUNNING";
    public const PAUSED = "PAUSED";
    public const FAILED = "FAILED";
    public const DEAD = "DEAD";
    public const CANCELLED = "CANCELLED";
    public const SUCCEEDED = "SUCCEEDED";

    public const ALL = [
        self::RUNNING,
        self::PAUSED,
        self::FAILED,
        self::DEAD,
        self::CANCELLED,
        self::SUCCEEDED,
    ];

    private function __construct()
    {
    }
}
