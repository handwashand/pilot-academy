<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything but a test database.
     *
     * phpunit.xml points the suite at SQLite in memory, but a cached config
     * (`php artisan optimize`, which DEPLOY.md runs on every update) ignores
     * phpunit.xml entirely. The suite then runs against whatever database the
     * cache names, and RefreshDatabase's migrate:fresh wipes it — while every
     * test still passes. Rehearsed on 2026-09-10: a marker table in a
     * stand-in "production" database was gone after the second run.
     *
     * This runs before the traits, not after parent::setUp(): by then
     * RefreshDatabase has already migrated, and the damage is done.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! static::isTestDatabase($database)) {
            $this->fail(
                "Refusing to run tests against the '{$database}' database ({$connection}).\n"
                ."Expected SQLite ':memory:' or a database whose name contains 'test'.\n\n"
                ."The usual cause is a cached config. Run `php artisan config:clear` and try again.\n"
            );
        }

        return parent::setUpTraits();
    }

    public static function isTestDatabase(?string $database): bool
    {
        return $database === ':memory:' || str_contains((string) $database, 'test');
    }
}
