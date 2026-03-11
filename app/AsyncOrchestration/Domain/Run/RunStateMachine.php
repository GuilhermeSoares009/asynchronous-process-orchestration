<?php

namespace App\AsyncOrchestration\Domain\Run;

use InvalidArgumentException;

final class RunStateMachine
{
    private const TRANSITIONS = [
        RunStatus::RUNNING => [RunStatus::PAUSED, RunStatus::FAILED, RunStatus::CANCELLED, RunStatus::SUCCEEDED],
        RunStatus::FAILED => [RunStatus::RUNNING, RunStatus::DEAD, RunStatus::CANCELLED],
        RunStatus::PAUSED => [RunStatus::RUNNING, RunStatus::CANCELLED],
        RunStatus::DEAD => [RunStatus::CANCELLED],
        RunStatus::CANCELLED => [],
        RunStatus::SUCCEEDED => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function assertTransition(string $from, string $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw new InvalidArgumentException("Invalid transition from {$from} to {$to}");
        }
    }
}
