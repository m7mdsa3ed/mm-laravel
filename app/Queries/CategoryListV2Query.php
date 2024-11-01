<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class CategoryListV2Query
{
    public static function get(int $userId): array
    {
        $sql = <<<SQL
            WITH RECURSIVE CategoryHierarchy AS (
                SELECT
                    id AS category_id,
                    name AS category_name,
                    parent_id,
                    name AS path
                FROM categories
                WHERE parent_id IS NULL and user_id = $userId

                UNION ALL

                SELECT
                    c.id AS category_id,
                    c.name AS category_name,
                    c.parent_id,
                    CONCAT(ch.path, ', ', c.name) AS path
                FROM categories c
                    INNER JOIN CategoryHierarchy ch ON c.parent_id = ch.category_id
            )

            SELECT
                ch.category_id as id,
                ch.category_name as name,
                ch.path as parent_names,
                SUM(t.amount * IF(t.action = 1, 1, -1)) AS balance,
                COUNT(t.id) AS transactions_count
            FROM CategoryHierarchy ch
                     LEFT JOIN transactions t ON t.category_id = ch.category_id
            GROUP BY ch.category_id, ch.category_name, ch.path
            ORDER BY ch.category_id;
        SQL;

        $data = DB::select($sql);

        return array_map(function ($item) {
            $parentNames = explode(', ', $item->parent_names);

            $parentNames = array_filter($parentNames, fn ($name) => $name !== $item->name);

            return [
                ...(array) $item,
                'parent_names' => $parentNames,
            ];
        }, $data);
    }
}
