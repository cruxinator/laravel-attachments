<?php

namespace Cruxinator\Attachments\Models;

use Carbon\Carbon;
use Closure;
use Cruxinator\Attachments\Contracts\AttachmentContract;
use Cruxinator\Attachments\Traits\HasAttachments;
use Cruxinator\SingleTableInheritance\SingleTableInheritanceTrait;
use Cruxinator\SingleTableInheritance\Strings\MyStr;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\File as FileHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property int id
 * @property string uuid
 * @property int attachable_id
 * @property string attachable_type
 * @property string disk
 * @property string filepath     the full path on storage disk
 * @property string filename
 * @property string filetype
 * @property int filesize
 * @property string key          must be unique across a model's attachments pool
 * @property string group        allows to group attachments
 * @property string title
 * @property string description
 * @property string preview_url
 * @property array metadata
 * @property string extension    the file extension (read-only mutator)
 * @property string path         the file directory (read-only mutator)
 * @property string url          the public URL from the storage (read-only mutator)
 * @property string url_inline   the public URL from the storage with inline switch (read-only mutator)
 * @property string proxy_url          the public URL using app as proxy (read-only mutator)
 * @property string proxy_url_inline   the public URL using app as proxy with inline switch (read-only mutator)
 * @method Builder where($column, $operator = null, $value = null, $boolean = 'and'): AttachmentContract
 * @method whereNull($key): AttachmentContract
 * @method static Builder|Attachment newModelQuery()
 * @method static Builder|Attachment newQuery()
 * @method static Builder|Attachment query()
 */
class Attachment extends Model implements AttachmentContract
{
    // NB: When disk is "local", we use the storage path
    use SingleTableInheritanceTrait;

    protected $table = 'attachments';

    protected $guarded = ['filepath'];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $observables = ['outputting'];

    #region "Single Table Inheritance"
    protected static $singleTableTypeField = 'type';

    protected static function getSingleTableSubclasses()
    {
        if (static::class !== self::class) {
            return [];
        }

        return Config::get('attachments.attachment_sub_models');
    }

    #endregion


    //region "constructors"

    /**
     * Shortcut method to bind an attachment to a model
     *
     * @param string|null $uuid
     * @param Model|HasAttachments $model a model that uses HasAttachment
     * @param array $options filter options based on configuration key `attachments.attributes`
     *
     * @return Attachment|null
     * @throws Exception
     */
    public static function attach(?string $uuid, Model $model, $options = []): ?self
    {
        $traits = class_uses_recursive($model);
        if (! array_key_exists(HasAttachments::class, $traits)) {
            throw new Exception('Supplied Model must use Cruxinator\Attachments\Traits\HasAttachments trait');
        }

        /** @var Attachment $attachment */
        $attachment = self::where('uuid', $uuid)->first();

        if (! $attachment) {
            return null;
        }

        // The dz_session_key is set by the proposed DropzoneController for security check
        if ($attachment->getMetadata('dz_session_key')) {
            $meta = $attachment->getMetadata();

            unset($meta['dz_session_key']);

            // Brute force over ignorance to clear dropzone session key
            $attachment->setAttribute('metadata', $meta);
            $attachment->save();
        }

        $options = Arr::only($options, config('attachments.attributes'));

        $attachment->fill($options);

        if ($found = $model->attachments()->where('key', '=', $attachment->key)->first()) {
            $found->delete();
        }

        return $attachment->attachedTo()->associate($model)->save() ? $attachment->refresh() : null;
    }

    /**
     * Creates a file object from a file an uploaded file.
     *
     * @param UploadedFile|null $uploadedFile source file
     * @param string|null $disk target storage disk
     *
     * @return $this|null
     * @throws FileNotFoundException
     */
    public function fromPost(?UploadedFile $uploadedFile, ?string $disk = null): ?AttachmentContract
    {
        if ($uploadedFile === null) {
            return null;
        }

        $this->disk = $this->populateDisk($disk);
        $this->filename = $uploadedFile->getClientOriginalName();
        $this->filesize = method_exists($uploadedFile, 'getSize') ? $uploadedFile->getSize() : $uploadedFile->getClientSize();
        $this->filetype = $uploadedFile->getMimeType();
        $this->filepath = $this->buildFilepath();
        $this->putFile($uploadedFile->getRealPath(), $this->filepath);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk.
     *
     * @param string|null $filePath source file
     * @param string|null $disk target storage disk
     *
     * @return $this|null
     * @throws FileNotFoundException
     */
    public function fromFile(?string $filePath, ?string $disk = null): ?AttachmentContract
    {
        if ($filePath === null) {
            return null;
        }

        $disk = $this->populateDisk($disk);

        $this->disk = $disk;
        $this->filename = File::baseName($filePath);
        $this->filesize = File::size($filePath);
        $this->filetype = File::mimeType($filePath);
        $this->filepath = $this->buildFilepath();
        $this->putFile($filePath, $this->filepath);

        return $this;
    }

    /**
     * Creates a file object from a stream
     *
     * @param resource $stream source stream
     * @param string $filename the resource filename
     * @param string|null $disk target storage disk
     *
     * @return $this|null
     */
    public function fromStream($stream, string $filename, ?string $disk = null): ?AttachmentContract
    {
        if ($stream === null) {
            return null;
        }

        $this->disk = $this->populateDisk($disk);

        //$driver = Storage::disk($this->disk);
        $this->filename = $filename;
        $this->filepath = $this->buildFilepath();

        $this->putStream($stream, $this->filepath);
        if ($this->isLocalStorage()) {
            $dest = $this->getLocalRootPath().'/';
            $this->filesize = FileHelper::size($dest.$this->filepath);
            $this->filetype = FileHelper::mimeType($dest.$this->filepath);
        } else {
            $driver = Storage::disk($this->disk);
            $this->filesize = $driver->size($this->filepath);
            $this->filetype = $driver->mimeType($this->filepath);
        }

        return $this;
    }

    protected function getDefaultStorageDriver()
    {
        return config('attachments.storage_default_filesystem') ?? Storage::getDefaultDriver();
    }

    protected function populateDisk(?string $disk): string
    {
        return $this->disk ?: ($disk ?: $this->getDefaultStorageDriver());
    }

    protected function buildFilepath(): string
    {
        return $this->filepath ?: ($this->getStorageDirectory().$this->getPartitionDirectory().$this->getDiskName());
    }

    //endregion

    //region "Model handling"

    public const ATTACHABLE_NAME = 'attachable';

    protected $fillable = ['disk', 'path', 'filepath', 'filename', 'filetype', 'filesize', 'key', 'group', 'metadata'];

    /** @var array */
    protected $metadata = [];

    public function attachedTo(): MorphTo
    {
        return $this->morphTo(self::ATTACHABLE_NAME);
    }

    public function attachable(): MorphTo
    {
        Log::warning(' use of obsolete relationship '.__METHOD__);

        return $this->attachedTo();
    }

    /**
     * Scope a query to only include attachments within the given group.
     *
     * @param Builder $query
     * @param string|null $groupName
     * @return Builder
     */
    public function scopeInGroup($query, ?string $groupName)
    {
        return $query->where('group', '=', $groupName);
    }

    public function setAttachedToAttribute(Model $model)
    {
        $traits = class_uses_recursive($model);
        if (! in_array(HasAttachments::class, $traits)) {
            throw new \Exception('Attached model must use HasAttachments trait');
        }
        $this->attachedTo()->associate($model);
    }

    /**
     * Register an outputting model event with the dispatcher.
     *
     * @param Closure|string $callback
     *
     * @return void
     */
    public static function outputting($callback)
    {
        static::registerModelEvent('outputting', $callback);
    }

    /**
     * Setup behaviors
     */
    protected static function boot()
    {
        parent::boot();

        if (config('attachments.behaviors.cascade_delete')) {
            static::deleted(function (self $attachment): void {
                $attachment->deleteFile();
            });
        }

        static::creating(function (self $attachment) {
            if (empty($attachment->uuid)) {
                throw new Exception('Failed to generate a UUID value');
            }

            if (empty($attachment->key)) {
                $attachment->key = uniqid();
            }
        });
    }

    /**
     * @return false|mixed
     * @throws Exception
     * @noinspection PhpMissingReturnTypeInspection type is defined by the dynamically generated uuid method.
     */
    public function getUuidAttribute()
    {
        if (! empty($this->attributes['uuid'])) {
            return $this->attributes['uuid'];
        }

        $generator = config('attachments.uuid_provider');

        if (strpos($generator, '@') !== false) {
            $generator = explode('@', $generator, 2);
        }

        if (! is_array($generator) && function_exists($generator)) {
            return $this->uuid = call_user_func($generator);
        }

        if (is_callable($generator)) {
            return $this->uuid = forward_static_call($generator);
        }

        throw new Exception('Missing UUID provider configuration for attachments');
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getExtensionAttribute(): string
    {
        return $this->getExtension();
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getPathAttribute()
    {
        return pathinfo($this->filepath, PATHINFO_DIRNAME);
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getUrlAttribute(): string
    {
        if ($this->useProxy()) {
            return $this->proxy_url;
        }

        return Storage::disk($this->disk)->url($this->filepath);
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getUrlInlineAttribute(): string
    {
        if ($this->useProxy()) {
            return $this->proxy_url_inline;
        }

        return Storage::disk($this->disk)->url($this->filepath);
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getProxyUrlAttribute(): string
    {
        return route('attachments.download', [
            'id' => $this->uuid,
            'name' => $this->extension ?
                MyStr::slug(substr($this->filename, 0, -1 * strlen($this->extension) - 1)).'.'.$this->extension :
                MyStr::slug($this->filename),
        ]);
    }

    /** @noinspection PhpUnused Attribute used by laravel dynamically*/
    public function getProxyUrlInlineAttribute(): string
    {
        return route('attachments.download', [
            'id' => $this->uuid,
            'name' => $this->extension ?
                MyStr::slug(substr($this->filename, 0, -1 * strlen($this->extension) - 1)).'.'.$this->extension :
                MyStr::slug($this->filename),
            'disposition' => 'inline',
        ]);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        return array_merge($attributes, [
            'url' => $this->url,
            'url_inline' => $this->url_inline,
        ]);
    }

    protected function useProxy(): bool
    {
        return $this->isLocalStorage() || $this->isProxiedAdapter();
    }
    //endregion

    //region "File handling"

    public function output($disposition = 'inline')
    {
        if ($this->fireModelEvent('outputting') === false) {
            return false;
        }

        $headers = [
            'Content-type' => $this->filetype,
            'Content-Disposition' => $disposition.'; filename="'.$this->filename.'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $this->filesize,
        ];

        return response($this->getContents(), 200, $headers);
    }

    /**
     * Get file contents from storage device.
     */
    public function getContents()
    {
        return $this->storageCommand('get', $this->filepath);
    }

    /**
     * Get a metadata value by key with dot notation
     *
     * @param string $key The metadata key, supports dot notation
     * @param mixed $default The default value to return if key is not found
     *
     * @return mixed
     * @noinspection PhpMissingReturnTypeInspection return type defined based on return type from Arr:get;
     */
    public function getMetadata(string $key = null, $default = null)
    {
        // TODO: Figure out why $this->metadata keeps returning empty
        $meta = $this->getAttribute('metadata');
        if (is_null($key)) {
            return $meta ?? [];
        }

        return Arr::get($meta, $key, $default);
    }

    /**
     * Saves a filestream
     *
     * @param resource $stream a resource to read content from
     * @param string|null $filePath A storage file path to save to.
     *
     * @return bool
     * @throws FileNotFoundException
     */
    public function putStream($stream, string $filePath = null): bool
    {
        $filePath = $this->unpackFilepath($filePath);

        if (! $this->isLocalStorage()) {
            return Storage::disk($this->disk)->putStream($filePath, $stream);
        }

        $destinationPath = $this->getLocalRootPath().'/'.pathinfo($filePath, PATHINFO_DIRNAME).'/';

        if ($this->checkPath($destinationPath)) {
            $this->checkError();
        }

        rewind($stream);

        return FileHelper::put($destinationPath.basename($filePath), stream_get_contents($stream));
    }

    /**
     * Saves a file
     *
     * @param string $sourcePath An absolute local path to a file name to read from.
     * @param string|null $filePath A storage file path to save to.
     *
     * @return bool
     * @throws FileNotFoundException
     */
    public function putFile(string $sourcePath, string $filePath = null): bool
    {
        $filePath = $this->unpackFilepath($filePath);

        if (! $this->isLocalStorage()) {
            return $this->copyToStorage($sourcePath, $filePath);
        }

        $destinationPath = $this->getLocalRootPath().'/'.pathinfo($filePath, PATHINFO_DIRNAME).'/';
        $destinationPath = str_replace('/', DIRECTORY_SEPARATOR, $destinationPath);

        if ($this->checkPath($destinationPath)) {
            $this->checkError();
        }

        return FileHelper::copy($sourcePath, $destinationPath.basename($filePath));
    }

    /**
     * @return void
     */
    protected function deleteFile(): void
    {
        $this->storageCommand('delete', $this->filepath);
        $this->deleteEmptyDirectory($this->path);
    }

    /**
     * Generates a disk name from the supplied file name.
     * @return string
     */
    protected function getDiskName(): string
    {
        if ($this->filepath !== null) {
            return $this->filepath;
        }

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', $this->uuid);

        return $this->filepath = $ext !== null ? $name.'.'.$ext : $name;
    }

    /**
     * Returns the file extension.
     * @return string
     */
    public function getExtension(): string
    {
        return FileHelper::extension($this->filename);
    }

    /**
     * Generate a temporary url at which the current file can be downloaded until $expire
     *
     * @param Carbon $expire
     * @param bool $inline
     *
     * @return string
     */
    public function getTemporaryUrl(Carbon $expire, bool $inline = false): string
    {
        $payload = Crypt::encryptString(collect([
            'id' => $this->uuid,
            'expire' => $expire->getTimestamp(),
            'shared_at' => Carbon::now()->getTimestamp(),
            'disposition' => $inline ? 'inline' : 'attachment',
        ])->toJson());

        return route('attachments.download-shared', ['token' => $payload]);
    }

    /**
     * Generates a partition for the file.
     * return /ABC/DE1/234 for an name of ABCDE1234.
     *
     * @return string
     */
    protected function getPartitionDirectory(): string
    {
        return implode('/', array_slice(str_split($this->uuid, 3), 0, 3)).'/';
    }

    /**
     * Define the internal storage path, override this method to define.
     *
     * @return string
     */
    protected function getStorageDirectory(): string
    {
        return config('attachments.storage_directory.prefix', 'attachments').'/';
    }

    /**
     * If working with local storage, determine the absolute local path.
     *
     * @return string
     */
    protected function getLocalRootPath(): string
    {
        return storage_path().DIRECTORY_SEPARATOR.'app';
    }

    /**
     * Returns true if the storage adapter is proxied in config.
     *
     * @return bool
     */
    protected function isProxiedAdapter(): bool
    {
        $adapter = Storage::disk($this->disk)->getDriver()->getAdapter();
        $proxyList = config('attachments.proxied_adapters', []);

        foreach ($proxyList as $type) {
            if ($adapter instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the storage engine is local.
     *
     * @return bool
     */
    protected function isLocalStorage(): bool
    {
        $disk = $this->disk;
        $options = config('filesystems.disks');
        // dig out driver for selected disk
        $choice = $options[$disk];
        // unpack alias driver if found
        if ('alias' == $choice['driver']) {
            $targ = $choice['target'];
            $choice = $options[$targ];
        }

        return $choice['driver'] == 'local';

        // Cleaned-up code which for moment has to stay disconnected, due to test blowups
        //$adapter = Storage::disk($this->disk)->getDriver()->getAdapter();
        //return $adapter instanceof Local;
    }

    /**
     * Returns true if a directory contains no files.
     *
     * @param string|null $dir the directory path
     *
     * @return bool
     */
    protected function isDirectoryEmpty(?string $dir): ?bool
    {
        if (! $dir || ! $this->storageCommand('exists', $dir)) {
            return null;
        }

        $files = $this->storageCommand('allFiles', $dir);

        return (is_countable($files) ? count($files) : 0) === 0;
    }

    /**
     * Copy the local file to Storage
     *
     * @param string $localPath
     * @param string $storagePath
     *
     * @return bool
     * @throws FileNotFoundException
     */
    protected function copyToStorage(string $localPath, string $storagePath): bool
    {
        return Storage::disk($this->disk)->put($storagePath, FileHelper::get($localPath));
    }

    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     *
     * @param string|null $dir the directory path
     *
     * @return void
     */
    protected function deleteEmptyDirectory(string $dir = null): void
    {
        if (! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);

        $dir = dirname($dir);

        if (! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);

        $dir = dirname($dir);

        if (! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);
    }

    /**
     * Calls a method against File or Storage depending on local storage.
     * This allows local storage outside the storage/app folder and is
     * also good for performance. For local storage, *every* argument
     * is prefixed with the local root path.
     *
     * @param string $string the command string
     * @param string $filepath the path on storage
     *
     * @return mixed
     */
    protected function storageCommand(string $string, string $filepath)
    {
        $args = func_get_args();
        $command = array_shift($args);

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function ($value) use ($path) {
                $value = str_replace('/', DIRECTORY_SEPARATOR, $value);

                return $path.DIRECTORY_SEPARATOR.$value;
            }, $args);
        } else {
            if (substr($filepath, 0, 1) !== '/') {
                $args[0] = $filepath = '/'.$filepath;
            }

            $interface = Storage::disk($this->disk);
        }

        return forward_static_call_array([$interface, $command], $args);
    }
    //endregion

    //region "Database Handling"
    public function getConnectionName()
    {
        return config('attachments.database.connection') ?? $this->connection;
    }
    //endregion

    //region "Reset Type"
    /**
     * @param Attachment|int $oldAttach
     * @return \Illuminate\Database\Eloquent\Collection|Model|Attachment|Attachment[]|null
     */
    public static function fromAttachment($oldAttach): self
    {
        if ($oldAttach instanceof Attachment) {
            $keyName = $oldAttach->getKeyName();
            $oldKey = $oldAttach->getKey();
        } else {
            $keyName = app(Attachment::class)->getKeyName();
            $oldKey = $oldAttach;
        }

        Attachment::where($keyName, '=', $oldKey)->update(['type' => static::class]);

        return Attachment::findOrFail($oldKey);
    }
    //endregion

    public static function uuid_v4_base36()
    {
        //return uniqid();
        return self::str_base_convert(sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        ), 16, 36);
    }

    private static function str_base_convert($str, $fromBase = 10, $toBase = 36)
    {
        $str = trim($str);

        if (intval($fromBase) != 10) {
            $len = strlen($str);
            $q = 0;
            for ($i = 0; $i < $len; $i++) {
                $r = base_convert($str[$i], $fromBase, 10);
                $q = bcadd(bcmul($q, $fromBase), $r);
            }
        } else {
            $q = $str;
        }

        if (intval($toBase) != 10) {
            $s = '';
            while (bccomp($q, '0', 0) > 0) {
                $r = intval(bcmod($q, $toBase));
                $s = base_convert($r, 10, $toBase).$s;
                $q = bcdiv($q, $toBase, 0);
            }
        } else {
            $s = $q;
        }

        return $s;
    }

    /**
     * @param $destinationPath
     * @return bool
     */
    protected function checkPath($destinationPath): bool
    {
        return ! FileHelper::isDirectory($destinationPath) &&
            ! FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
            ! FileHelper::isDirectory($destinationPath);
    }

    protected function checkError(): void
    {
        $error = error_get_last();
        if (null !== $error) {
            trigger_error($error['message'], E_USER_WARNING);
        }
    }

    /**
     * @param string|null $filePath
     * @return string|null
     */
    protected function unpackFilepath(?string $filePath): ?string
    {
        if (! $filePath) {
            $filePath = $this->filepath;
        }

        return $filePath;
    }
}
