<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\TestCase as FeatureTestCase;

/**
 * The suite must never run against a real database: with a cached config,
 * RefreshDatabase wipes whatever database the cache names.
 */
class TestDatabaseGuardTest extends TestCase
{
    public function test_sqlite_in_memory_is_a_test_database(): void
    {
        $this->assertTrue(FeatureTestCase::isTestDatabase(':memory:'));
    }

    public function test_a_database_named_for_testing_is_allowed(): void
    {
        $this->assertTrue(FeatureTestCase::isTestDatabase('pilot_academy_testing'));
    }

    public function test_the_real_database_is_refused(): void
    {
        $this->assertFalse(FeatureTestCase::isTestDatabase('pilot_academy'));
        $this->assertFalse(FeatureTestCase::isTestDatabase('/var/www/html/database/database.sqlite'));
        $this->assertFalse(FeatureTestCase::isTestDatabase(null));
    }
}
