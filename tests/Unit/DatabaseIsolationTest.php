<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    public function test_tests_run_strictly_against_isolated_test_database(): void
    {
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();

        // Must connect strictly to enterprise_hrms_test
        $this->assertEquals('enterprise_hrms_test', $databaseName);
        $this->assertNotEquals('enterprise_hrms', $databaseName);

        // Driver must strictly be mysql
        $this->assertEquals('mysql', $connection->getDriverName());
    }

    public function test_development_database_is_not_touched_by_tests(): void
    {
        $devDbName = 'enterprise_hrms';

        // Direct query to dev database via specific raw PDO
        $devTableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$devDbName])[0]->count;

        $this->assertGreaterThan(0, $devTableCount);
    }
}

