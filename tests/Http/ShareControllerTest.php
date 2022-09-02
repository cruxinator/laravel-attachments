<?php

namespace Cruxinator\Attachments\Tests\Http;

use Cruxinator\Attachments\Http\Controllers\ShareController;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShareControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
    }

    public function testBadToken()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File Not Found');

        $request = m::mock(Request::class);

        $att = new Attachment();
        $foo = new ShareController($att);

        $token = 'ac';

        $res = $foo->download($token, $request);
    }

    public function testExpiredToken()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Token Expired');

        $stamp = Carbon::now()->addHours(-5)->timestamp;

        $payload = ['id' => 1, 'expire' => $stamp];

        $token = Crypt::encryptString(json_encode($payload));

        $request = m::mock(Request::class);

        $att = new Attachment();
        $foo = new ShareController($att);

        $res = $foo->download($token, $request);
    }

    public function testGoodTokenMissingRecord()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File Not Found');

        $stamp = Carbon::now()->addHours(+5)->timestamp;

        $payload = ['id' => 1, 'expire' => $stamp];

        $token = Crypt::encryptString(json_encode($payload));

        $request = m::mock(Request::class);

        $att = new Attachment();
        $foo = new ShareController($att);

        $res = $foo->download($token, $request);
    }

    public function testGoodTokenWeirdDispositionFail()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Access Denied');

        $stamp = Carbon::now()->addHours(+5)->timestamp;

        $payload = ['id' => 1, 'expire' => $stamp, 'disposition' => 'foobar'];

        $token = Crypt::encryptString(json_encode($payload));

        $request = m::mock(Request::class);

        $att = m::mock(Attachment::class);
        $att->expects('output')->withArgs(['attachment'])->andReturn(false);

        $model = m::mock(Attachment::class)->makePartial();
        $model->allows('where->first')->andReturn($att)->once();

        $att = new Attachment();
        $foo = new ShareController($model);

        $res = $foo->download($token, $request);
    }

    public function testGoodTokenWeirdDispositionKaboom()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File Not Found');

        $stamp = Carbon::now()->addHours(+5)->timestamp;

        $payload = ['id' => 1, 'expire' => $stamp, 'disposition' => 'foobar'];

        $token = Crypt::encryptString(json_encode($payload));

        $request = m::mock(Request::class);

        $att = m::mock(Attachment::class);
        $att->expects('output')->withArgs(['attachment'])->andThrows(FileNotFoundException::class);

        $model = m::mock(Attachment::class)->makePartial();
        $model->allows('where->first')->andReturn($att)->once();

        $att = new Attachment();
        $foo = new ShareController($model);

        $res = $foo->download($token, $request);
    }
}
