<?php

namespace App\Services\Analytics;

use App\Traits\HasInstanceGetter;
use Exception;

class AnalyticsService
{
    use HasInstanceGetter;

    public function getCharts(array $charts): array
    {
        $chartsData = [];

        foreach ($charts as $chart) {
            $class = $this->getClass($chart);

            $chartsData[$chart] = app()->call("$class@get");
        }

        return $chartsData;
    }

    /** @throws Exception */
    private function getClass(mixed $chartName): string
    {
        $className = __NAMESPACE__ . '\\Charts\\' . ucfirst($chartName) . 'Chart';

        if (class_exists($className)) {
            return $className;
        }

        throw new Exception("$className is not exist");
    }
}
