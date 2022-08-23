<?php

namespace Cruxinator\Attachments\Contracts;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface AttachmentContract
 *
 * @method where($column, $operator = null, $value = null, $boolean = 'and'): AttachmentContract
 * @method whereNull($key): AttachmentContract
 */
interface AttachmentContract
{
    public function save();

    public function fill(array $only);

    public function delete();

    public function getUrlAttribute(): string;

    public function getTemporaryUrl(Carbon $expire, bool $inline = false): string;

    public function fromStream($stream, string $filename, ?string $disk = null): ?self;

    public function fromPost(UploadedFile $uploadedFile, ?string $disk = null): ?self;

    public function fromFile(string $filePath, ?string $disk = null): ?self;

    public function getMetadata(string $key, $default = null);
}
