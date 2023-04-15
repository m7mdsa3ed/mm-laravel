<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAuditTriggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:audit {tableName} {--events=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** Execute the console command. */
    public function handle(): void
    {
        $auditTableName = 'audits';

        $targetTableName = $this->argument('tableName');

        $events = explode(',', $this->option('events'));

        $statement = '';

        foreach ($events as $event) {
            $statement .= match ($event) {
                'insert' => $this->createAfterUpdateAuditTableTriggerSql($auditTableName, $targetTableName),
                'update' => $this->createInsertAuditTableTriggerSql($auditTableName, $targetTableName),
                'delete' => $this->createDeleteAuditTableTriggerSql($auditTableName, $targetTableName),
            };
        }

        DB::unprepared($statement);
    }

    private function createAfterUpdateAuditTableTriggerSql(string $auditTableName, string $auditableTableName): string
    {
        $tableColumns = $this->tableColumns($auditableTableName);

        $oldValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'OLD');

        $newValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'NEW');

        return "
            drop trigger if exists {$auditableTableName}_after_update_auditor;

            create trigger {$auditableTableName}_after_update_auditor
            after update on $auditableTableName for each row
            begin
                insert into $auditTableName(`before`, `after`, `user_id`, `table_name`, `action`) 
                values (json_object($oldValues), json_object($newValues), @userId, '$auditableTableName', 'update');
            end;
        ";
    }

    private function createInsertAuditTableTriggerSql(string $auditTableName, string $auditableTableName): string
    {
        $tableColumns = $this->tableColumns($auditableTableName);

        $newValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'NEW');

        return "
            drop trigger if exists {$auditableTableName}_insert_auditor;

            create trigger {$auditableTableName}_insert_auditor
            after insert on $auditableTableName for each row
            begin
                insert into $auditTableName(`before`, `after`, `user_id`, `table_name`, `action`) 
                values (null, json_object($newValues), @userId, '$auditableTableName', 'insert');
            end;
        ";
    }

    private function createDeleteAuditTableTriggerSql(string $auditTableName, string $auditableTableName): string
    {
        $tableColumns = $this->tableColumns($auditableTableName);

        $newValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'OLD');

        return "
            drop trigger if exists {$auditableTableName}_delete_auditor;

            create trigger {$auditableTableName}_delete_auditor
            after delete on $auditableTableName for each row
            begin
                insert into $auditTableName(`before`, `after`, `user_id`, `table_name`, `action`) 
                values (json_object($newValues), null, @userId, '$auditableTableName', 'delete');
            end;
        ";
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
}
