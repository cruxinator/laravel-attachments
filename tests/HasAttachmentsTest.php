<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Picture;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;

class HasAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    public function testAttachNullToModel()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Attached file is required');

        $foo->attachToModel(null);
    }

    public function suppliedProvider(): array
    {
        $result = [];
        $result['Key'] = ['key', 'putemhigh'];
        $result['Group'] = ['group', 'takemeaway'];
        $result['Type'] = ['type', Picture::class];

        return $result;
    }

    /**
     * @dataProvider suppliedProvider
     */
    public function testSuppliedOptions(string $field, string $value)
    {
        $file = base_path('../../../../tests/resources/PNG_transparency_demonstration_1.png');
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $main = File::get($file);
        File::shouldReceive('get')->andReturn($main);
        File::shouldReceive('extension')->andReturn('png');
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('size')->andReturn(30720);
        File::shouldReceive('mimeType')->andReturn('image/png');
        File::shouldReceive('basename')->andReturn('PNG_transparency_demonstration_1.png');
        File::shouldReceive('copy')->andReturn(true);

        config(['attachments.attributes' => ['key', 'group', 'type']]);

        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $upload = $file;
        $options = [$field => $value];
        if ('type' !== $field) {
            $options['type'] = Attachment::class;
        }

        $res = $foo->attachToModel($upload, $options);
        $this->assertEquals($value, $res->{$field});
    }
    
    public function testAttachmentMethod()
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
        
        $key = $att->refresh()->key;
        
        $nuAtt = $foo->attachment($key);
        $this->assertTrue($nuAtt instanceof Attachment);
        $this->assertEquals($att->getKey(), $nuAtt->getKey());
        
        $this->assertNull($foo->attachment('name'));
    }
    
}
