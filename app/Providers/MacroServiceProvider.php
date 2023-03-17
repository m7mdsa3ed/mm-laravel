<?php

namespace App\Providers;

use App\Http\Requests\HttpRequest;
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
        Http::macro('execute', function (HttpRequest $httpRequest) {
            $request = Http::baseUrl($httpRequest->url);

            $response = $request->{$httpRequest->method}('', $httpRequest->params);

            $formatter = $httpRequest->formatter ?? null;

            if ($formatter && is_callable($formatter)) {
                $response = $formatter($response, $httpRequest->params);
            }

            $listeners = $httpRequest->listeners ?? null;

            foreach ($listeners as $listener) {
                if ($listener && is_callable($listener)) {
                    $listener($response, $httpRequest->params);
                }
            }

            return $response;
        });
    }
}
