<?php

namespace Cruxinator\Attachments\Tests\Http;

use Cruxinator\Attachments\Http\Controllers\DropzoneController;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Mockery as m;

class DropzoneControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUpload()
    {
        config(['attachments.attributes' => ['title', 'description', 'key', 'disk', 'filepath', 'group']]);
        $file = $this->setUpMockUpload();

        $input = ['title' => 'title', 'description' => 'description', 'key' => 'secret', 'disk' => 'local'];

        $req = m::mock(Request::class);
        $req->expects('input')->andReturns($input)->twice();
        $req->expects('file')->andReturn($file)->once();

        $model = new Attachment();

        $controller = new DropzoneController($model);

        $expected = [
            'key' => 'secret',
            'filename' => 'lain-cyberia-mix.png',
            'filesize' => 10240,
            'filetype' => 'image/png',
        ];

        /** @var Response $result */
        $actual = $controller->post($req);

        // Verify attachment landed
        $nuAtt = Attachment::where('key', 'secret')->firstOrFail();

        // verify output
        $expected['uuid'] = $nuAtt->uuid;
        $expected['url'] = $nuAtt->url;
        $expected['url_inline'] = $nuAtt->url_inline;

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return UploadedFile
     */
    protected function setUpMockUpload(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = m::mock(UploadedFile::class)->makePartial();
        $file->allows('getSize')->andReturns(10240)->once();
        $file->allows('getMimeType')->andReturns('image/png')->once();
        $file->allows('getClientOriginalName')->andReturns('lain-cyberia-mix.png')->once();
        File::shouldReceive('copy')->andReturn(true)->once();
        File::shouldReceive('extension')->andReturn('png')->atLeast(1);
        File::shouldReceive('isDirectory')->andReturn(true)->once();

        return $file;
    }

    public function testDeleteSuccessful()
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->save();

        $req = m::mock(Request::class);
        $uuid = $att->uuid;

        $model = new Attachment();

        $controller = new DropzoneController($model);

        /** @var Response $result */
        $result = $controller->delete($uuid, $req);
        $this->assertEquals(204, $result->getStatusCode());

        $nuAtt = Attachment::where('uuid', $uuid)->first();
        $this->assertNull($nuAtt, 'Attachment not deleted');
    }

    public function testDeleteDeniedNotUnhooked()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->attachable()->associate($foo);
        $att->save();

        $req = m::mock(Request::class);
        $uuid = $att->uuid;

        $model = new Attachment();

        $controller = new DropzoneController($model);

        /** @var Response $result */
        $result = $controller->delete($uuid, $req);
        $this->assertEquals(422, $result->getStatusCode());
        $this->assertEquals('Delete Denied', $result->original);
    }

    public function testDeleteDeniedBadCSRF()
    {
        Session::regenerateToken();
        $this->assertNotNull(csrf_token(), 'CSRF token should not be null');

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->save();

        $req = m::mock(Request::class);
        $uuid = $att->uuid;

        $model = new Attachment();

        $controller = new DropzoneController($model);

        /** @var Response $result */
        $result = $controller->delete($uuid, $req);
        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('Delete Denied', $result->original);
    }
}
