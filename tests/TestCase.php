<?php

namespace VendorName\Skeleton\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'test']);
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'test']);
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        if (env('database.default', false) === false) {
            $app['config']->set('database.default', 'test');

            $app['config']->set('database.connections.test', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            \VendorName\Skeleton\SkeletonServiceProvider::class,
        ];
    }
}
