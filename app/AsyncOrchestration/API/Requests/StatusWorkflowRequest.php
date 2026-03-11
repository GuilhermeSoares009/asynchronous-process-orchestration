<?php

namespace App\AsyncOrchestration\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StatusWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            "runId" => $this->route("runId"),
        ]);
    }

    public function rules(): array
    {
        return [
            "runId" => ["required", "integer", "min:1"],
        ];
    }
}
