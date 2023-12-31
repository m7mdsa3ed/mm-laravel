<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasAppendSelect
{
    public function scopeAppendSelect(Builder $builder, mixed $payload): void
    {
        $tableName = $builder->getModel()->getTable();

        $addSelectAll = $builder->getQuery()->columns == null;

        if ($addSelectAll) {
            $builder->addSelect("{$tableName}.*");
        }

        $builder->addSelect($payload);
    }
}
