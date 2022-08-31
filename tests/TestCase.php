<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 1:21 PM.
 */

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\AttachmentsServiceProvider;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Archive;
use Cruxinator\Attachments\Tests\Fixtures\Document;
use Cruxinator\Attachments\Tests\Fixtures\Media;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            AttachmentsServiceProvider::class,
            /*\Orchestra\Database\ConsoleServiceProvider::class,*/];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @throws \ReflectionException
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Brute-force set app namespace
        $reflec = new \ReflectionClass($app);
        $prop = $reflec->getProperty('namespace');
        $prop->setAccessible(true);
        $prop->setValue($app, 'Cruxinator\\Orchestra\\');
        $app['config']->set('attachments.uuid_provider', Attachment::class.'@uuid_v4_base36');
        $app['config']->set('attachments.attachment_sub_models', [
            Media::class,
            Document::class,
            Archive::class,
        ]);

        // spawn config defaults for routes
        $app['config']->set('attachments.routes', [
            'publish' => true,
            'prefix' => 'attachments',
            'middleware' => 'web',
            'pattern' => '/{id}/{name}',
            'shared_pattern' => '/shared/{token}',
            'dropzone' => [
                'upload_pattern' => '/dropzone',
                'delete_pattern' => '/dropzone/{id}',
            ],
        ]);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('database.connections.testbench-master', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('attachment_sub_models',[
            Media::class,
            Document::class,
            Archive::class,
        ]);
        /*
                if (env('database.default', false) === false) {
            $app['config']->set('database.default', 'test');

            $app['config']->set('database.connections.test', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
        */
    }

    public function setUp(): void
    {
        parent::setUp();
        /*
                parent::setUp();
        $this->artisan('migrate', ['--database' => 'test']);
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'test']);
        });
        */
        $this->loadMigrationsFrom(realpath(__DIR__ . '/database/migrations'));
        date_default_timezone_set('UTC');
    }

    protected function assertSeeShim($result, $expected)
    {
        if (method_exists($result, 'assertSee')) {
            $result->assertSee($expected);
        } else {
            $this->assertContains($expected, $result->response->getOriginalContent());
        }
    }

    protected static function resetMetadataProvider($provider)
    {
        $reset = function () {
            self::$isBooted = false;
            self::$afterExtract = null;
            self::$afterUnify = null;
            self::$afterVerify = null;
            self::$afterImplement = null;
        };

        return call_user_func($reset->bindTo($provider, get_class($provider)));
    }

    protected function tearDown(): void
    {
        $config = app('config');
        $router = app('router');
        parent::tearDown();
        app()->instance('config', $config);
        app()->instance('router', $router);
    }
}
