<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Picture;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Sftp\SftpAdapter;

class AttachmentTest extends TestCase
{
    use DatabaseTransactions;

    public function localStorageProvider(): array
    {
        $result = [];
        $result['Local disk, local driver'] = ['local', 'local', null, true];
        $result['Storage disk, local driver'] = ['storage', 'local', null, true];
        $result['Local disk, sftp driver'] = ['local', 'sftp', null, false];
        $result['Gubbins disk, sftp driver'] = ['gubbins', 'sftp', null, false];
        $result['Local-alias disk, aliased-to-local driver'] = ['local-alias', 'alias', 'local', true];
        $result['Local-alias disk, aliased-to-local-powered driver'] = ['local-alias', 'alias', 'storage', true];
        $result['Gubbins-alias disk, aliased-to-sftp driver'] = ['gubbins-alias', 'alias', 'gubbins', false];

        return $result;
    }

    /**
     * @dataProvider localStorageProvider
     *
     * @param string $selectedDisk
     * @param string $driver
     * @param string|null $aliasDisk
     * @param bool $expected
     */
    public function testIsLocalStorage(string $selectedDisk, string $driver, ?string $aliasDisk, bool $expected)
    {
        $filesystems = [
            'local' => [
                'driver' => 'local',
                'root' => public_path(),
            ],
            'storage' => [
                'driver' => 'local',
                'root' => base_path('storage/app'),
            ],
            'gubbins' => [
                'driver' => 'sftp',
            ],
            'local-alias' => [
                'driver' => 'alias',
                'target' => $aliasDisk,
            ],
            'gubbins-alias' => [
                'driver' => 'alias',
                'target' => $aliasDisk,
            ],
        ];
        if ('local' == $selectedDisk) {
            $filesystems['local']['driver'] = $driver;
        }
        config(['filesystems.disks' => $filesystems]);

        $att = new Attachment();
        $att->disk = $selectedDisk;

        $ref = new \ReflectionClass($att);
        $method = $ref->getMethod('isLocalStorage');
        $method->setAccessible(true);

        $actual = $method->invoke($att);
        $this->assertEquals($expected, $actual);
    }

    public function proxyAdapterProvider(): array
    {
        $result = [];
        $result['Nothing proxied, local adapter'] = [[], Local::class, false];
        $result['Nothing proxied, sftp adapter'] = [[], SftpAdapter::class, false];
        $result['Local adapter proxied, local adapter'] = [[Local::class], Local::class, true];
        $result['Local adapter proxied, sftp adapter'] = [[Local::class], SftpAdapter::class, false];
        $result['Sftp adapter proxied, local adapter'] = [[SftpAdapter::class], Local::class, false];
        $result['Sftp adapter proxied, sftp adapter'] = [[SftpAdapter::class], SftpAdapter::class, true];

        return $result;
    }

    /**
     * @dataProvider proxyAdapterProvider
     *
     * @param array $proxyList
     * @param string $adapterClass
     * @param bool $expected
     */
    public function testIsAdapterProxied(array $proxyList, string $adapterClass, bool $expected)
    {
        $filesystems = [
            'local' => [
                'driver' => 'local',
                'root' => public_path(),
            ],
            'gubbins' => [
                'driver' => 'sftp',
            ],
        ];
        config(['filesystems.disks' => $filesystems]);
        config(['attachments.proxied_adapters' => $proxyList]);
        $lookup = [Local::class => 'local', SftpAdapter::class => 'gubbins'];

        $att = new Attachment();
        $att->disk = $lookup[$adapterClass];

        $ref = new \ReflectionClass($att);
        $method = $ref->getMethod('isProxiedAdapter');
        $method->setAccessible(true);

        $actual = $method->invoke($att);
        $this->assertEquals($expected, $actual);
    }

    public function resetTypeProvider(): array
    {
        $result = [];
        $result['Existing attachment'] = ['model', 'existing', null];
        $result['Existing key'] = ['key', 'existing', null];
        $result['Nonexistent attachment'] = ['model', 'missing', ModelNotFoundException::class];
        $result['Nonexistent key'] = ['key', 'missing', ModelNotFoundException::class];

        return $result;
    }

    /**
     * @dataProvider resetTypeProvider
     * @param string $inputType
     * @param string $inputClass
     * @param string|null $expectedException
     */
    public function testResetTypeFromExistingAttachment(string $inputType, string $inputClass, ?string $expectedException)
    {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());
        $att->refresh();
        $this->assertTrue($att->exists);

        $att->refresh();
        $this->assertEquals(Attachment::class, $att->type);

        $foo = null;
        switch ($inputType) {
            case 'model':
                switch ($inputClass) {
                    case 'existing':
                        $foo = $att;

                        break;
                    default:
                        $foo = app(Attachment::class);
                }

                break;
            case 'key':
                switch ($inputClass) {
                    case 'existing':
                        $foo = $att->getKey();

                        break;
                    default:
                        $foo = -1;
                }

                break;
        }

        if ('existing' == $inputClass) {
            if ($foo instanceof Attachment) {
                $fooId = $foo->getKey();
            } else {
                $fooId = $foo;
            }
            // check attachment exists
            $direct = Attachment::find($fooId);
            $this->assertNotNull($direct, 'Direct attachment-exist check failed when attachment should already exist');
        }

        $nuAtt = Picture::fromAttachment($foo);
        $this->assertEquals(Picture::class, $nuAtt->type);
    }

    public function testGetInGroupScope()
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

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'Three Legged Alien';
        $att->attachable()->associate($foo);
        $att->save();

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'Three Legged Alien';
        $att->attachable()->associate($foo);
        $att->save();

        $this->assertEquals(3, $foo->attachments()->count());
        $this->assertEquals(1, $foo->attachments()->inGroup('aybabtu')->count());
    }

    public function testSaveEmptyMetadata()
    {
        $meta = ['dz_session_key' => 'dropzone'];

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $att->group = 'aybabtu';
        $att->metadata = $meta;
        $att->save();

        $this->assertEquals('dropzone', $att->getMetadata('dz_session_key'));
        $meta = $att->getMetadata();
        unset($meta['dz_session_key']);
        $att->metadata = $meta;
        $this->assertTrue($att->save());
        $att->refresh();

        $this->assertNull($att->getMetadata('dz_session_key'), 'Dropzone key should be null');
    }

    public function testAttachNullUuid()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $result = Attachment::attach(null, $foo);
        $this->assertNull($result);
    }

    public function testAttachExistingUuid()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $att->group = 'aybabtu';
        $att->save();

        $this->assertEquals(0, $foo->attachments()->count(), 'Should have no attachments initially');
        $res = Attachment::attach($att->uuid, $foo);
        $this->assertNotNull($res);
        $this->assertEquals(1, $foo->attachments()->count(), 'Should have exactly 1 attachment after attaching');
    }

    /**
     * @throws Exception
     */
    public function testClearDropzoneKeyOnAttach()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $att->group = 'aybabtu';
        $att->save();

        $meta = ['dz_session_key' => 'dropzone'];
        $att->metadata = $meta;
        $this->assertTrue($att->save());
        $this->assertNotNull($att->refresh()->getMetadata('dz_session_key'), 'Dropzone key should be set in metadata');

        $this->assertEquals(0, $foo->attachments()->count(), 'Should have no attachments initially');
        $res = Attachment::attach($att->uuid, $foo);
        $this->assertNotNull($res);
        $this->assertEquals(1, $foo->attachments()->count(), 'Should have exactly 1 attachment after attaching');

        $att->refresh();
        $this->assertNull($att->getMetadata('dz_session_key'), 'Dropzone key should be cleared by attachment');
    }

    public function testOutputPrevented()
    {
        $closure = function () {
            return false;
        };
        Attachment::outputting($closure);

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->save();

        $this->assertFalse($att->output());
    }

    public function testAttachToDirectGood()
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
        $att->save();

        $att->attachedTo = $foo;

        $nuParent = $att->attachedTo()->firstOrFail();
        $this->assertEquals($foo->getKey(), $nuParent->getKey());
    }

    public function testAttachToDirectBad()
    {
        $this->expectExceptionMessage('Attached model must use HasAttachments trait');

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->save();

        $att->attachedTo = $att;
    }
    
    public function testUuidGeneration()
    {
        $actual = Attachment::uuid_v4_base36();
        $this->assertEquals(25, strlen($actual), 'Uuid has unexpected length');
    }
}
