<?php


namespace Cruxinator\Attachments\Tests\Console;

use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class MigrateAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    public function testMigrateSameArguments()
    {
        $from = 'local';
        $to = 'local';

        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_error_missing'])->andReturn('')->once();
        Lang::shouldReceive('get')->andReturn('');

        $res = Artisan::call('attachments:migrate', ['from' => $from, 'to' => $to]);
    }

    public function testMigrateBothDisksMissing()
    {
        $from = 'jam';
        $to = 'spoon';

        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_error_from'])->andReturn('')->once();
        Lang::shouldReceive('get')->andReturn('');

        $res = Artisan::call('attachments:migrate', ['from' => $from, 'to' => $to]);
    }

    public function testMigrateToDiskMissing()
    {
        $from = 'local';
        $to = 'spoon';

        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_error_to'])->andReturn('')->once();
        Lang::shouldReceive('get')->andReturn('');

        $res = Artisan::call('attachments:migrate', ['from' => $from, 'to' => $to]);
    }

    public function testMigrateFromDiskMissingDot()
    {
        $from = 'local';
        $to = 'public';

        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_invalid_from'])->andReturn('')->once();
        Lang::shouldReceive('get')->andReturn('');

        Storage::shouldReceive('disk->has')->andThrow(\Exception::class);
        
        $res = Artisan::call('attachments:migrate', ['from' => $from, 'to' => $to]);
    }
}
