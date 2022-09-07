<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Mockery as m;

class AttachmentFileHandlingTest extends TestCase
{
    use DatabaseTransactions;

    public function testOutput()
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        File::shouldReceive('get')->andReturn('foobar');

        $res = $att->output();
        $this->assertTrue($res instanceof Response);

        /** @var ResponseHeaderBag $headers */
        $headers = $res->headers;
        $this->assertEquals('image/png', $headers->get('Content-type'));
        $this->assertEquals(1020, $headers->get('Content-Length'));
        $this->assertEquals('bytes', $headers->get('Accept-Ranges'));
        $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, post-check=0, pre-check=0, private', $headers->get('Cache-Control'));
    }

    public function directoryEmptyProvider(): array
    {
        $result = [];
        $result['Null directory'] = [null, false, null, null];
        $result['Missing directory'] = ['missing', false, null, null];
        $result['Bad file listing'] = ['extant', true, 'foobar', false];
        $result['Empty file listing'] = ['extant', true, [], true];
        $result['Non-empty file listing'] = ['extant', true, ['rhu', 'barb'], false];

        return $result;
    }

    /**
     * @dataProvider directoryEmptyProvider
     *
     * @param string|null $dir
     * @param bool|null $expected
     * @throws \ReflectionException
     */
    public function testIsDirectoryEmpty(?string $dir, bool $exists, $fileList, ?bool $expected)
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        File::shouldReceive('exists')->andReturn($exists);
        File::shouldReceive('allFiles')->andReturn($fileList);

        $ref = new \ReflectionClass($att);
        $method = $ref->getMethod('isDirectoryEmpty');
        $method->setAccessible(true);

        $actual = $method->invoke($att, $dir);
        if (null === $expected) {
            $this->assertNull($actual);
        }
    }

    public function deleteEmptyDirectory(): array
    {
        $result = [];
        $result['Bottom dir not empty'] = [0];
        $result['Bottom dir empty'] = [1];
        $result['Bottom 2 dirs empty'] = [2];
        $result['Bottom 3 dirs empty'] = [3];

        return $result;
    }

    /**
     * @dataProvider deleteEmptyDirectory
     *
     * @param int $numEmpty
     * @throws \ReflectionException
     */
    public function testDeleteEmptyDirectory(int $numEmpty)
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $ref = new \ReflectionClass($att);
        $method = $ref->getMethod('deleteEmptyDirectory');
        $method->setAccessible(true);

        $emptyList = [];
        $nonEmptyList = ['rhu', 'barb'];

        File::shouldReceive('exists')->andReturn(true);
        $bottom = ('foo' . DIRECTORY_SEPARATOR . 'bar' . DIRECTORY_SEPARATOR . 'baz');
        $mid = ('foo' . DIRECTORY_SEPARATOR . 'bar');
        $fullBottom = storage_path('app'. DIRECTORY_SEPARATOR . $bottom);
        $fullMid = storage_path('app'. DIRECTORY_SEPARATOR . $mid);
        $fullTop = storage_path('app'. DIRECTORY_SEPARATOR . 'foo');

        if (0 < $numEmpty) {
            File::shouldReceive('allFiles')->withArgs([$fullBottom])->andReturn($emptyList);
        }

        if (2 == $numEmpty) {
            File::shouldReceive('allFiles')->withArgs([$fullMid])->andReturn($emptyList);
        }
        if (3 == $numEmpty) {
            File::shouldReceive('allFiles')->withArgs([$fullMid])->andReturn($emptyList);
            File::shouldReceive('allFiles')->withArgs([$fullTop])->andReturn($emptyList);
        }
        File::shouldReceive('deleteDirectory')->times($numEmpty);
        File::shouldReceive('allFiles')->andReturn($nonEmptyList);

        $res = $method->invoke($att, $bottom);
    }

    public function testDeleteAttachment()
    {
        File::shouldReceive('delete')->andReturn(true)->times(1);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('deleteDirectory')->andReturn(true)->times(3);
        File::shouldReceive('exists')->andReturn(true);

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = 'foo/bar/baz/lain-cyberia-mix.png';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $this->assertEquals('foo/bar/baz', $att->path);

        $att->delete();
    }
    
    public function testGetContentsFromNonLocal()
    {
        $att = new Attachment();
        $att->disk = 's3';
        $att->filepath = 'foo/bar/baz/lain-cyberia-mix.png';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $this->assertTrue($att->save());
        
        $disk = m::mock(FilesystemAdapter::class)->makePartial();
        $disk->shouldReceive('get')->withArgs(['/foo/bar/baz/lain-cyberia-mix.png'])->andReturn('foobar')->once();
        
        Storage::shouldReceive('disk')->andReturn($disk)->atLeast(1);
        
        $expected = 'foobar';
        $actual = $att->getContents();
        
        $this->assertEquals($expected, $actual);
    }
}
