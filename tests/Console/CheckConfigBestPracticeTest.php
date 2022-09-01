<?php

namespace Cruxinator\Attachments\Tests\Console;

use Cruxinator\Attachments\Tests\TestCase;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckConfigBestPracticeTest extends TestCase
{
    use DatabaseTransactions;

    public function testCharacteriseOutput()
    {
        Artisan::call('attachments:check_config');

        $output = Artisan::output();
        $this->assertEquals('', $output);
    }

    public function testMissingDefaultFilesystem()
    {
        config(['attachments.storage_default_filesystem' => 'puffy']);

        Storage::shouldReceive('disk')->withArgs(['puffy'])->andThrow(\InvalidArgumentException::class)->once();

        Artisan::call('attachments:check_config');

        $output = Artisan::output();
        $this->assertEquals('', $output);
    }

    public function testDefaultModelNotAttachmentSubclass()
    {
        config(['attachments.attachment_model' => User::class]);

        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.check_description'])->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.check_option_expert'])
            ->andReturn('check')->twice();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.check_warn_should_inherit'])->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.cleanup_description'])->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.cleanup_option_since'])
            ->andReturn('since')->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_description'])
            ->andReturn('migrate')->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_option_from'])
            ->andReturn('from')->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.migrate_option_to'])
            ->andReturn('to')->once();
        Lang::shouldReceive('get')->withArgs(['attachments::messages.console.check_warn_should_be_child'])->times(3);


        Artisan::call('attachments:check_config');
    }
}
