<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;
use DateTime;

class MacroServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->toRawSqlMacro();
    }

    private function toRawSqlMacro(): void
    {
        // toRawSql
        QueryBuilder::macro('toRawSql', fn () => array_reduce(
            /** @var QueryBuilder $this */
            $this->getBindings(),
            static function ($sql, $binding) {
                if ($binding instanceof DateTime) {
                    $binding = $binding->format('Y-m-d H:i:s');
                }

                if (is_string($binding)) {
                    $binding = "'$binding'";
                }

                if ($binding == null) {
                    $binding = 'null';
                }

                return preg_replace('/\?/', $binding, $sql, 1);
            },
            $this->toSql()
        ));

        EloquentBuilder::macro('toRawSql', function () {
            /** @var EloquentBuilder $this */
            return $this->getQuery()->toRawSql();
        });
    }
}
