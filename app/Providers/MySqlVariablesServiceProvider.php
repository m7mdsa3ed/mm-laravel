<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class MySqlVariablesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->setSessionVariables();
    }

    private function setSessionVariables(): void
    {
        $variables = [
            'userId' => 1,
            'ip' => request()->ip(),
            'url' => request()->url(),
        ];

        $statements = [];

        foreach ($variables as $variable => $value) {
            $value = is_string($value) ? "'$value'" : $value;

            $statements[] = "@$variable = $value";
        }

        $sql = 'set ' . implode(', ', $statements);

        DB::statement($sql);
    }
}
