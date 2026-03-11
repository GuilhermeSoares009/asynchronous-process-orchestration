<?php

namespace App\AsyncOrchestration\Domain\Steps\ImportUsers;

use App\AsyncOrchestration\Domain\Steps\Step;

final class ParseCsvStep implements Step
{
    public function name(): string
    {
        return "parse_csv";
    }

    public function handle(array $context): array
    {
        $context["parsed"] = true;

        return $context;
    }

    public function maxAttempts(): int
    {
        return 3;
    }

    public function backoffSeconds(): array
    {
        return [5, 30, 120];
    }
}
