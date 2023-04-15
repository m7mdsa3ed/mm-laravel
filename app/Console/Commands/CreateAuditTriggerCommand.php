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
        $events = explode(',', $this->option('events'));

        // TODO handle $events

        $statement = $this->createAfterUpdateAuditTableTriggerSql('audit', $this->argument('tableName'));

        DB::unprepared($statement);
    }

    private function createAfterUpdateAuditTableTriggerSql(string $auditTableName, string $auditableTableName): string
    {
        $tableColumns = $this->tableColumns($auditableTableName);

        $oldValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'OLD');

        $newValues = $this->createMySqlJsonObjectForAudit($tableColumns, 'NEW');

        return "
            drop trigger if exists {$auditableTableName}_auditor;

            create trigger {$auditableTableName}_auditor
            after update on $auditableTableName for each row
            begin
                insert into $auditTableName(`before`, `after`, `user_id`, `table_name`) 
                values (json_object($oldValues), json_object($newValues), @userId, '$auditableTableName');
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
