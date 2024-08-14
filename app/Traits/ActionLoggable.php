<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ActionLoggable
{
    /**
     * Log an action to the custom action log file.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logAction(string $message, array $context = [], string $level = 'info')
    {
        Log::channel('action')->{$level}($message, $context);
    }
}
