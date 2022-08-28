<?php

namespace Cruxinator\Attachments\Models;

use Cruxinator\Attachments\Traits\HasAttachments;
use Exception;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class ResizablePicture extends Picture
{
    use HasAttachments;

    /**
     * @throws Exception
     */
    public function ofSize(int $x, int $y, bool $inverse = false, bool $grayScale = false, int $twist = 0, int $aspect = 0): ?Picture
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->attachment($this->getSizeKey($x, $y, $inverse, $grayScale, $twist, $aspect))
            ?? $this->buildResize($x, $y, $inverse, $grayScale, $twist, $aspect);
    }

    /**
     * @throws Exception
     */
    public function ofProfile(string $profilerName = null, bool $inverse = false, bool $grayScale = false): ?Picture
    {
        $size = empty($profilerName) ? $this->getXy() : config('image.sizes.'.$profilerName);
        if (! is_array($size) || ! array_key_exists('width', $size)) {
            throw new InvalidArgumentException(
                "An Attempt to load profile" . var_export($profilerName) .
                " didn't yield a valid sizes array. received data ". var_export($size)
            );
        }

        return $this->ofSize($size["width"], $size["height"], $inverse, $grayScale, $size['rotation'] ?? 0, $size['aspect'] ?? 0);
    }

    protected function getSizeKey(int $x, int $y, bool $inverse, bool $grayScale = false, int $twist = 0, int $aspect = 0): string
    {
        $key = $x.'x'.$y.'_'.$inverse."_".$grayScale;
        if (0 !== $twist || 0 !== $aspect) {
            $key .= '_'.$twist . '_' . $aspect;
        }

        return $key;
    }

    private function getXy()
    {
        $self = $this;

        return $this->getMetadata(
            'image.size',
            function () use ($self) {
                $src = imagecreatefromstring($this->getContents());
                $x = imagesx($src);
                $y = imagesy($src);
                imagedestroy($src);
                $imgSize = ['width' => $x, 'height' => $y];
                $self->metadata['image.size'] = $imgSize;
                $self->save();

                return $self->metadata['image.size'];
            }
        );
    }

    /**
     * @throws Exception
     */
    private function buildResize(int $width, int $height, bool $inverse = false, bool $grayScale = false, int $twist = 0, int $aspect = 0): ?Picture
    {
        $src = imagecreatefromstring($this->getContents());
        $oldWidth = imagesx($src);
        $oldHeight = imagesy($src);
        $r = floatval($width / $height);
        $sourceAspect = floatval($oldWidth / $oldHeight);
        if (0 !== $aspect) {
            if ($width / $height > $sourceAspect) {
                $newWidth = $height * $sourceAspect;
                $newHeight = $height;
            } else {
                $newHeight = $width / $sourceAspect;
                $newWidth = $width;
            }
        } else {
            if (floatval($width / $height) > $r) {
                $newWidth = $height * $r;
                $newHeight = $height;
            } else {
                $newHeight = $width / $r;
                $newWidth = $width;
            }
        }
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefill($dst, 0, 0, $transparent);
        imagecolortransparent($dst, $transparent);
        imagesavealpha($dst, true);
        imageantialias($dst, true);
        imagealphablending($src, true);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($src), imagesy($src));
        imagealphablending($src, true);
        imagealphablending($dst, true);
        $inverse && $this->negative($dst);
        $grayScale && $this->grayscale($dst);
        $fp = fopen('php://memory', 'r+');
        if (0 !== $twist) {
            $dst = imagerotate($dst, 90 * $twist, 0);
        }
        imagepng($dst, $fp);
        rewind($fp);
        $key = $this->getSizeKey($width, $height, $inverse, $grayScale, $twist, $aspect);
        $attachment = $this->attachToModel(
            $fp,
            [
                'key' => $key,
                'type' => Picture::class,
                'filename' => $this->filename,
                'disk' => $this->disk,
            ]
        );
        $attachment->key = $key;
        $attachment->save();
        imagedestroy($src);
        imagedestroy($dst);
        fclose($fp);
        assert($attachment instanceof Picture);

        return $attachment;
    }

    private function negative($img): ResizablePicture
    {
        if (false === imagefilter($img, IMG_FILTER_NEGATE)) {
            throw new RuntimeException('Failed to negate the image');
        }

        return $this;
    }

    private function grayscale($img): ResizablePicture
    {
        if (false === imagefilter($img, IMG_FILTER_GRAYSCALE)) {
            throw new RuntimeException('Failed to grayscale the image');
        }

        return $this;
    }

    public static function keyAttachment(Model $model, string $key): ResizablePicture
    {
        $att = $model->attachment($key);

        return $att;
    }

    public static function ffmpegBase(): string
    {
        $conf = config('attachments.ffmpeg');

        if (0 < strlen($conf) && '/' == $conf[0]) {
            return $conf;
        }

        $path = base_path(config('attachments.ffmpeg'));
        if ('\\' == DIRECTORY_SEPARATOR) {
            $path = str_replace('/', '\\', $path);
        }

        return $path;
    }
}
