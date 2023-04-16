<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAuditTriggerCommand extends Command
{
    protected $signature = 'make:audit {tableName} {--events=}';

    protected $description = 'Command description';

    private $auditTableName = 'audits';

    public function handle(): void
    {
        $targetTableName = $this->argument('tableName');

        $events = explode(',', $this->option('events'));

        $statement = '';

        foreach ($events as $event) {
            $statement .= match ($event) {
                'insert' => $this->createOnUpdateAuditTableTriggerSql($targetTableName),
                'update' => $this->createOnInsertAuditTableTriggerSql($targetTableName),
                'delete' => $this->createOnDeleteAuditTableTriggerSql($targetTableName),
            };
        }

        DB::unprepared($statement);
    }

    private function createOnUpdateAuditTableTriggerSql(string $auditableTableName): string
    {
        return $this->createTriggerSql("{$auditableTableName}_after_update_auditor", 'update', $auditableTableName);
    }

    private function createOnInsertAuditTableTriggerSql(string $auditableTableName): string
    {
        return $this->createTriggerSql("{$auditableTableName}_insert_auditor", 'insert', $auditableTableName);
    }

    private function createOnDeleteAuditTableTriggerSql(string $auditableTableName): string
    {
        return $this->createTriggerSql("{$auditableTableName}_delete_auditor", 'delete', $auditableTableName);
    }

    private function tableColumns(string $tableName): array
    {
        $results = DB::select("show columns from $tableName");

        return array_column($results, 'Field');
    }

    private function createMySqlJsonObjectForAudit(array $cols, string $type): string
    {
        $output = [];

        foreach ($cols as $col) {
            $output[] = "\"$col\"";
            $output[] = "$type.$col";
        }

        return implode(', ', $output);
    }

    private function createTriggerSql(string $triggerName, string $triggerType, string $triggerOnTable): string
    {
        $insertSql = $this->createInsertSql($triggerOnTable, $triggerType);

        return "
            drop trigger if exists $triggerName;
            create trigger $triggerName after $triggerType on $triggerOnTable for each row begin $insertSql end;
        ";
    }

    private function createInsertSql(string $tableName, string $action): string
    {
        $auditTableName = $this->auditTableName;

        $tableColumns = $this->tableColumns($tableName);

        $beforeObject = $action == 'insert' ? null : $this->createMySqlJsonObjectForAudit($tableColumns, 'OLD');

        $afterObject = $action == 'delete' ? null : $this->createMySqlJsonObjectForAudit($tableColumns, 'NEW');

        $values = [
            'action' => "'$action'",
            'before' => $beforeObject ? "json_object($beforeObject)" : 'null',
            'after' => $afterObject ? "json_object($afterObject)" : 'null',
            'user_id' => '@userId',
            'ip' => '@ip',
            'url' => '@url',
            'table_name' => "'$tableName'",
        ];

        $cols = implode(', ', array_map(fn ($col) => "`$col`", array_keys($values)));

        $value = implode(', ', array_values($values));

        return "insert into $auditTableName($cols) values ($value);";
    }
}
