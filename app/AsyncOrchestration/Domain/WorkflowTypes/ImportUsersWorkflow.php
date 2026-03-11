<?php

namespace App\AsyncOrchestration\Domain\WorkflowTypes;

use App\AsyncOrchestration\Domain\Steps\ImportUsers\ParseCsvStep;
use App\AsyncOrchestration\Domain\Steps\ImportUsers\PersistUsersStep;
use App\AsyncOrchestration\Domain\Steps\ImportUsers\ValidateCsvStep;

final class ImportUsersWorkflow implements WorkflowType
{
    public const TYPE = "import_users";

    public function name(): string
    {
        return self::TYPE;
    }

    public function steps(): array
    {
        return [
            ValidateCsvStep::class,
            ParseCsvStep::class,
            PersistUsersStep::class,
        ];
    }
}
