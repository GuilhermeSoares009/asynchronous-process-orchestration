<?php

namespace App\AsyncOrchestration\Domain\WorkflowTypes;

interface WorkflowType
{
    public function name(): string;

    /** @return array<class-string> */
    public function steps(): array;
}
