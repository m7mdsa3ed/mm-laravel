<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class CategoryListV2Query
{
    public static function get(int $userId): array
    {
        $sql = <<<SQL
            WITH RECURSIVE
                Descendants AS (
                    SELECT
                        id,
                        id AS ancestor_id
                    FROM
                        categories
                    WHERE user_id = $userId
                    UNION ALL
                    SELECT
                        c.id,
                        d.ancestor_id
                    FROM
                        categories c
                            INNER JOIN
                        Descendants d
                        ON
                            c.parent_id = d.id
                ),

                Ancestors AS (
                    SELECT
                        id,
                        id AS descendant_id,
                        0 AS level
                    FROM
                        categories
                    UNION ALL
                    SELECT
                        c.parent_id,
                        a.descendant_id,
                        a.level + 1
                    FROM
                        Ancestors a
                            INNER JOIN
                        categories c
                        ON
                            a.id = c.id
                    WHERE
                        c.parent_id IS NOT NULL
                ),

                AncestorNames AS (
                    SELECT
                        a.descendant_id,
                        GROUP_CONCAT(c.name ORDER BY a.level DESC SEPARATOR ', ') AS ancestor_names
                    FROM Ancestors a
                        INNER JOIN categories c ON a.id = c.id
                    GROUP BY a.descendant_id
                ),
                TotalExpenses AS (
                    SELECT
                        c.id AS category_id,
                        COALESCE(SUM(t.amount), 0) AS total_expenses
                    FROM categories c
                        LEFT JOIN Descendants d ON c.id = d.ancestor_id
                        LEFT JOIN transactions t ON t.category_id = d.id AND t.action = 2
                    GROUP BY c.id
                )
            SELECT
                c.id,
                c.name,
                IFNULL(an.ancestor_names, c.name) AS parent_names,
                te.total_expenses as balance,
                (select count(ts.id) from transactions ts where ts.category_id = c.id) as transactions_count
            FROM categories c
                LEFT JOIN AncestorNames an ON c.id = an.descendant_id
                LEFT JOIN TotalExpenses te ON c.id = te.category_id
            ORDER BY c.id;
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
