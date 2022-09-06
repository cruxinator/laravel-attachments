<?php

namespace Cruxinator\Attachments\Traits;

use Cruxinator\Attachments\Contracts\AttachmentContract;
use Cruxinator\Attachments\Models\Attachment;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Trait HasAttachments
 *
 * @property-read Attachment[]|Collection attachments
 * @mixin Model
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, Attachment::ATTACHABLE_NAME);
    }

    /**
     * Find an attachment by key
     *
     * @param string $key
     *
     * @return Attachment|null
     */
    public function attachment(string $key): ?Attachment
    {
        //return $this->attachments->firstWhere('key', $key);
        return $this->attachments()->where('key', $key)->first();
    }

    /**
     * @param UploadedFile|string $fileOrPath
     * @param array               $options Set attachment options : title, description, key, disk
     *
     * @return Attachment|null
     * @throws Exception
     */
    public function attachToModel($fileOrPath, array $options = []): ?Attachment
    {
        if (empty($fileOrPath)) {
            throw new Exception('Attached file is required');
        }

        $attributes = Arr::only($options, config('attachments.attributes'));

        if (! empty($attributes['key']) && $attachment = $this->attachments()->where('key', $attributes['key'])->first()) {
            $attachment->delete();
        }

        /** @var Attachment $attachment */
        $attachment = app($options['type'] ?? AttachmentContract::class);
        $attachment->fill($attributes);
        $attachment->filepath = ! empty($attributes['filepath']) ? $attributes['filepath'] : null;
        if (array_key_exists('key', $attributes)) {
            $attachment->key = $attributes['key'];
        }
        if (array_key_exists('group', $attributes)) {
            $attachment->group = $attributes['group'];
        }
        if (array_key_exists('type', $attributes)) {
            $attachment->type = $attributes['type'];
        }

        if (is_resource($fileOrPath)) {
            if (empty($options['filename'])) {
                throw new Exception('Attaching a resource requires options["filename"] to be set');
            }

            $attachment->fromStream($fileOrPath, $options['filename']);
        } elseif ($fileOrPath instanceof UploadedFile) {
            $attachment->fromPost($fileOrPath);
        } else {
            $attachment->fromFile($fileOrPath);
        }
        //$attachment->attachable()->associate($this);

        if ($attachment = $this->attachments()->save($attachment)) {
            return $attachment;
        }

        return null;
    }
}
