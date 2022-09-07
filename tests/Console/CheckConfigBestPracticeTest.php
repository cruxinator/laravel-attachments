<?php

namespace Cruxinator\Attachments\Tests\Console;

use Cruxinator\Attachments\Tests\Fixtures\Media;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Cruxinator\Attachments\Tests\Fixtures\Video;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

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


        Artisan::call('attachments:check_config', ['--expert' => true]);
    }

    public function modelAndStiProvider(): array
    {
        $result = [];
        $result['Attachment, empty submodels'] = [Attachment::class, [], false];
        $result['Attachment, nonempty submodels'] = [Attachment::class, [Media::class], false];
        $result['Non-attachment, empty submodels'] = [Media::class, [], false];
        $result['Non-attachment, nonempty submodels'] = [Media::class, [Video::class], true];

        return $result;
    }

    /**
     * @dataProvider modelAndStiProvider
     * @param string $attClass
     * @param array $submodels
     * @param bool $shouldWarn
     */
    public function testCheckBothModelAndStiUsed(string $attClass, array $submodels, bool $shouldWarn)
    {
        config(['attachments.attachment_model' => $attClass]);
        config(['attachments.attachment_sub_models' => $submodels]);

        $res = $this->artisan('attachments:check_config');
        if ($shouldWarn) {
            $expected = Lang::get('attachments::messages.console.check_warn_inherit_and_sti');

            $res->expectsOutput($expected);
        }
        $final = $res->run();
        $this->assertEquals(0, $final);
    }
}
