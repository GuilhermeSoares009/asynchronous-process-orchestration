<?php

namespace App\AsyncOrchestration\Domain\Steps;

interface Step
{
    public function name(): string;

    public function handle(array $context): array;

    public function maxAttempts(): int;

    public function backoffSeconds(): array;
}
