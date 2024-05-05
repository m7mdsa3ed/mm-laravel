<?php

function dispatchAction(App\Actions\Action $action)
{
    return $action->execute();
}

if (!function_exists('r')) {
    function r(string $string): string
    {
        return str($string)
            ->camel()
            ->ucfirst()
            ->ucsplit()
            ->join(' ');
    }
}

if (!function_exists('settings')) {
    function settings(mixed $key = null, ?int $userId = null, bool $fullObject = false): mixed
    {
        if (isset($key)) {
            return App\Models\Settings::getByKey($key, $userId, $fullObject);
        }

        return app(App\Services\Settings\SettingsService::class);
    }
}

if (!function_exists('liveResponse')) {
    function liveResponse(callable $callback): void
    {
        set_time_limit(0);

        ob_implicit_flush(1);

        ob_end_clean();

        header('X-Accel-Buffering: no');

        $callback();

        usleep(100);
    }
}

if (!function_exists('sendLiveResponse')) {
    function sendLiveResponse(mixed $response): void
    {
        if (is_array($response)) {
            $response = json_encode($response);
        }

        echo $response . "\n"; // send response
    }
}

if (!function_exists('money')) {
    function money(mixed $value, string $currency = 'USD'): string
    {
        return number_format($value, 2) . ' ' . $currency;
    }
}

if (!function_exists('recursiveBase64Encode')) {
    function recursiveBase64Encode($value): string|array
    {
        if (is_array($value)) {
            return array_map('recursiveBase64Encode', $value);
        }

        return base64_encode($value);
    }
}
if (!function_exists('recursiveBase64Decode')) {
    function recursiveBase64Decode($value): string|array
    {
        if (is_array($value)) {
            return array_map('recursiveBase64Decode', $value);
        }

        return base64_decode($value);
    }
}

if (!function_exists('rawNotification')) {
    function rawNotification(int $userId, array $payload): void
    {
        Illuminate\Notifications\DatabaseNotification::query()
            ->insert([
                'id' => str()->uuid()->toString(),
                'type' => 'Raw',
                'data' => json_encode($payload),
                'notifiable_id' => $userId,
                'notifiable_type' => App\Models\User::class,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}

if (!function_exists('parseCSVWithHeadersAndMerge')) {
    function parseCSVWithHeadersAndMerge(
        $filePath,
        ?callable $rowCallback = null,
        ?callable $headerCallback = null,
        array $options = [],
    ): false|array {
        $csvFile = fopen($filePath, 'r');

        if ($csvFile !== false) {
            $firstLine = fgets($csvFile);

            if (str_starts_with($firstLine, "\u{FEFF}")) {
                $firstLine = substr($firstLine, 3);
            }

            $headers = str_getcsv($firstLine);

            $headers = $headerCallback ? call_user_func($headerCallback, $headers) : $headers;

            $r = [];

            while (($data = fgetcsv($csvFile)) !== false) {
                $rowData = array_combine($headers, $data);

                $rowData = $rowCallback ? call_user_func($rowCallback, $rowData) : $rowData;

                if (array_filter($rowData)) {
                    $r[] = $rowData;
                }
            }

            fclose($csvFile);

            return $r;
        } else {
            return false;
        }
    }
}

if (!function_exists('loadRelations')) {
    function loadRelations(
        array &$array,
        Illuminate\Database\Eloquent\Model|array $model,
        ?string $modelKey = null,
        ?string $arrayKey = null
    ): array {
        if (!is_array($model)) {
            $model = [
                [
                    'model' => $model,
                    'modelKey' => $modelKey,
                    'arrayKey' => $arrayKey,
                ],
            ];
        }

        foreach ($model as &$m) {
            $class = $m['model'];

            $modelName = str(get_class($class))
                ->explode('\\')
                ->last();

            $m['modelName'] = strtolower($modelName);

            $query = $class::query();

            $values = array_unique(array_filter(array_column($array, $m['arrayKey'])));

            $data = $query->whereIn($m['modelKey'], $values)
                ->get();

            $data = $data->mapWithKeys(fn ($d) => [$d->{$m['modelKey']} => $d])->toArray();

            $m['data'] = $data;
        }

        $array = array_map(function ($row) use ($model) {
            foreach ($model as $m) {
                $row[$m['modelName']] = $m['data'][$row[$m['arrayKey']]] ?? null;
            }

            return $row;
        }, $array);

        return $array;
    }
}
