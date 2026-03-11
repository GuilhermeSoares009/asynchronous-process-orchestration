<?php

namespace App\AsyncOrchestration\Domain\WorkflowTypes;

final class WorkflowTypeRegistry
{
    /** @var array<string, WorkflowType> */
    private array $types;

    /** @param array<class-string<WorkflowType>>|null $workflowClasses */
    public function __construct(?array $workflowClasses = null)
    {
        $classes = $workflowClasses ?? $this->defaultWorkflows();
        $types = [];

        foreach ($classes as $class) {
            $instance = new $class();
            if (!$instance instanceof WorkflowType) {
                continue;
            }

            $types[$instance->name()] = $instance;
        }

        $this->types = $types;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->types);
    }

    public function get(string $type): ?WorkflowType
    {
        return $this->types[$type] ?? null;
    }

    /** @return array<string, WorkflowType> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array<class-string<WorkflowType>> */
    private function defaultWorkflows(): array
    {
        $configured = config("async_orchestration.workflows");
        if (is_array($configured) && count($configured) > 0) {
            return $configured;
        }

        return [ImportUsersWorkflow::class];
    }
}
