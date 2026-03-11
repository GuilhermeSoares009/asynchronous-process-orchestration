<?php

namespace App\AsyncOrchestration\Ops\Logging;

use Illuminate\Support\Facades\Log;

final class StructuredLogger
{
    public static function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
