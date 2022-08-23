<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 9:01 PM.
 */
namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Tests\Connections\CloneInMemoryPDO;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as Schema;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    protected static $isBooted = false;

    public function register()
    {
    }

    public function boot()
    {
        $path = __DIR__ . '/database/migrations';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $this->loadMigrationsFrom($path);
    }

    protected function loadMigrationsFrom($path)
    {
        $src = DB::connection('testbench-master')->getPdo();
        $dst = DB::connection('testbench')->getPdo();

        if (!Schema::connection('testbench-master')->hasTable('migrations')) {
            $migrator = $this->app->make('migrator');
            $migrationRepository = $migrator->getRepository();
            $migrationRepository->setSource('testbench-master');
            $migrationRepository->createRepository();
            $migrator->run($path);
        }

        CloneInMemoryPDO::clone($src, $dst);
    }
}