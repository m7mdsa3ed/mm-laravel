<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // withWhereHas
        EloquentBuilder::macro('withWhereHas', fn ($relation, $constraint) => $this->whereHas($relation, $constraint)->with([$relation => $constraint]));

        // toRawSql
        QueryBuilder::macro('toRawSql', fn () =>  array_reduce(
            $this->getBindings(),
            static function ($sql, $binding) {
                if ($binding instanceof DateTime) {
                    $binding = $binding->format('Y-m-d H:i:s');
                }

                if (is_string($binding)) {
                    $binding = "'$binding'";
                }

                if ($binding == null) {
                    $binding = "null";
                }

                return preg_replace('/\?/', $binding, $sql, 1);
            },
            $this->toSql()
        ));

        EloquentBuilder::macro('toRawSql', function () {
            return $this->getQuery()->toRawSql();
        });
    }
}
