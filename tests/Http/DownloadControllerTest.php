<?php

namespace Cruxinator\Attachments\Tests\Http;

use Cruxinator\Attachments\Http\Controllers\DownloadController;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testDownloadBadId()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File Not Found');

        $req = m::mock(Request::class);
        $req->allows('input')->withArgs(['disposition'])->andReturns('inline');

        $model = new Attachment();

        $controller = new DownloadController($model);

        $result = $controller->download('no-id', $req);
    }

    public function testDownloadKaboom()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File Not Found');

        $req = m::mock(Request::class);
        $req->allows('input')->withArgs(['disposition'])->andReturns('inline');

        $att = m::mock(Attachment::class);
        $att->expects('output')->andThrows(FileNotFoundException::class)->once();
        $att->allows('getContents')->andReturns('');

        $model = m::mock(Attachment::class)->makePartial();
        $model->allows('where->first')->andReturn($att)->once();

        $controller = new DownloadController($model);

        $result = $controller->download('no-id', $req);
    }

    public function testDownloadAccessDenied()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Access Denied');

        $req = m::mock(Request::class);
        $req->allows('input')->withArgs(['disposition'])->andReturns('inline');

        $att = m::mock(Attachment::class);
        $att->expects('output')->andReturn(false)->once();
        $att->allows('getContents')->andReturns('');

        $model = m::mock(Attachment::class)->makePartial();
        $model->allows('where->first')->andReturn($att)->once();

        $controller = new DownloadController($model);

        $result = $controller->download('no-id', $req);
    }
}
