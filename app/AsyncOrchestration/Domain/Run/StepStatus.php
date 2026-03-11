<?php

namespace App\AsyncOrchestration\Domain\Run;

final class StepStatus
{
    public const RUNNING = "RUNNING";
    public const SUCCEEDED = "SUCCEEDED";
    public const FAILED = "FAILED";
    public const DEAD = "DEAD";
    public const SKIPPED = "SKIPPED";

    public const ALL = [
        self::RUNNING,
        self::SUCCEEDED,
        self::FAILED,
        self::DEAD,
        self::SKIPPED,
    ];

    private function __construct()
    {
    }
}
