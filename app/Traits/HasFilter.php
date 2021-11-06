<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFilter
{

    public function scopeFilter(Builder $query, array $filters = [], $searching = false): Builder
    {

        foreach ($filters as $col => $value) {

            if ($value) {

                $method = 'scopeFilterBy' . $col;

                if (method_exists($this, $method)) {

                    $this->$method($query, $value, $searching);
                } else {

                    if (is_array($value)) {
                        if ($searching) {
                            $query->OrWhereIn($col, $value);
                        } else {
                            $query->whereIn($col, $value);
                        }
                    } else {
                        if ($searching) {
                            $query->OrWhere($col, trim($value));
                        } else {
                            $query->where($col, trim($value));
                        }
                    }
                }
            }
        }

        return $query;
    }
}
