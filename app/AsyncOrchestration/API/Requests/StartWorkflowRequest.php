<?php

namespace App\AsyncOrchestration\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StartWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "context" => ["array"],
            "correlation_id" => ["nullable", "string"],
            "tenant_id" => ["nullable", "string"],
        ];
    }
}
