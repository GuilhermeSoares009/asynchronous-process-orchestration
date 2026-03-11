<?php

namespace App\AsyncOrchestration\Domain\Steps\ImportUsers;

use App\AsyncOrchestration\Domain\Steps\Step;

final class PersistUsersStep implements Step
{
    public function name(): string
    {
        return "persist_users";
    }

    public function handle(array $context): array
    {
        $context["persisted"] = true;

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
