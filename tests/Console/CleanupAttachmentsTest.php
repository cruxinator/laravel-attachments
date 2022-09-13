<?php

namespace Cruxinator\Attachments\Tests\Console;

use Cruxinator\Attachments\Console\Commands\CleanUpAttachments;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\PendingCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class CleanupAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCleanup()
    {
        $version = Application::VERSION;

        // Don't run this test on L5.6
        if (false !== strpos($version, '5.6.')) {
            $this->assertTrue(true);

            return;
        }

        config(['attachments.behaviors.cascade_delete' => true]);

        File::shouldReceive('delete')->andReturn(true)->times(1);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('deleteDirectory')->andReturn(true);
        File::shouldReceive('exists')->andReturn(true);

        $now = now();
        Carbon::setTestNow($now);

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $this->assertTrue($att->save());
        $id = $att->getKey();

        $now = now()->addMinutes(1440);
        Carbon::setTestNow($now);

        /** @var PendingCommand $res */
        $res = $this->artisan('attachments:cleanup')
            ->expectsQuestion('Clean up expired attachments?', true)
            ->expectsOutput('Expired attachments deleted')
            ->assertExitCode(0)->run();

        $this->assertNull(Attachment::find($id));

        // do follow up test, verify it takes the nothing-to-do route
        $this->artisan('attachments:cleanup')
            ->expectsQuestion('Clean up expired attachments?', true)
            ->expectsOutput('No expired attachments found')
            ->assertExitCode(0)->run();
    }

    public function testCheckSinceDefaultValue()
    {
        /** @var CleanUpAttachments\ $foo */
        $foo = App::make(CleanupAttachments::class);

        /** @var InputDefinition $def */
        $def = $foo->getDefinition();
        /** @var InputOption $option */
        $option = $def->getOption('since');
        $this->assertEquals(1440, $option->getDefault());
        $this->assertEquals('s', $option->getShortcut());
    }
}
