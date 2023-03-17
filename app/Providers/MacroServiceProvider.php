<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->toRawSqlMacro();

        $this->httpClientHandlerMacro();
    }

    private function toRawSqlMacro()
    {
        // toRawSql
        \Illuminate\Database\Query\Builder::macro('toRawSql', fn () => array_reduce(
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

        \Illuminate\Database\Eloquent\Builder::macro('toRawSql', function () {
            return $this->getQuery()->toRawSql();
        });
    }

    private function httpClientHandlerMacro()
    {
        // TODO: handle concurrency requests
        Http::macro('execute', function (array $data) {
            $request = Http::baseUrl($data['endpoint']);

            $response = $request->{$data['method']}('', $data['data']);

            $parser = $data['parser'] ?? null;

            if ($parser && is_callable($parser)) {
                $response = $parser($response, $data['data']);
            }

            $listener = $data['listener'] ?? null;

            if ($listener && is_callable($listener)) {
                $listener($response, $data['data']);
            }

            return $response;
        });
    }
}
