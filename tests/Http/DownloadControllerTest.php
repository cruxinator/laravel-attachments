<?php

namespace Cruxinator\Attachments\Tests\Http;

use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Http\Controllers\DownloadController;
use Mockery as m;
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
}