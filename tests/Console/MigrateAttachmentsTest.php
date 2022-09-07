<?php

namespace Cruxinator\Attachments\Tests\Console;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Mockery as m;

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

    public function testMigrateTwoAttachmentsBothExistingFiles()
    {
        $from = 'local';
        $to = 's3';

        $att1 = new Attachment();
        $att1->disk = 'local';
        $att1->filepath = '';
        $att1->filename = '';
        $att1->filetype = '';
        $att1->filesize = 0;
        $att1->group = 'aybabtu';
        $att1->save();

        $att2 = new Attachment();
        $att2->disk = 'local';
        $att2->filepath = '';
        $att2->filename = '';
        $att2->filetype = '';
        $att2->filesize = 0;
        $att2->group = 'aybabtu';
        $att2->save();

        $local = m::mock(FilesystemAdapter::class)->makePartial();
        $local->expects('exists')->andReturns(true)->twice();
        $local->allows('has')->andReturns(true);
        $local->allows('get')->andReturns('HAMMERTIME');

        $s3 = m::mock(FilesystemAdapter::class)->makePartial();
        $s3->allows('has')->andReturns(true);
        $s3->allows('put')->andReturns(true);

        Storage::shouldReceive('disk')->withArgs(['local'])->andReturn($local);
        Storage::shouldReceive('disk')->withArgs(['s3'])->andReturn($s3);

        $res = $this->artisan('attachments:migrate', ['from' => $from, 'to' => $to])->run();
        $this->assertEquals(0, $res);

        $att1->refresh();
        $this->assertEquals('s3', $att1->disk, 'First attachment disk not updated');
        $att2->refresh();
        $this->assertEquals('s3', $att2->disk, 'Second attachment disk not updated');
    }
}
