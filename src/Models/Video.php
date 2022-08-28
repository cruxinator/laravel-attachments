<?php

namespace Cruxinator\Attachments\Models;

use App\Helpers\BC;
use App\Utils\GifTranscodeFilter;
use App\Utils\PixelFormatFilter;
use App\Utils\VideoMuteFilter;
use Cruxinator\Attachments\Contracts\IPreviewable;
use Cruxinator\Attachments\Traits\HasAttachments;
use Cruxinator\Package\Strings\MyStr;
use Cruxinator\TemporaryDirectory\TemporaryDirectory;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\File;

class Video extends Media implements IPreviewable
{
    use HasAttachments;

    public static $singleTableSubclasses = [];

    public function getHtml(): string
    {
        return view(
            'www._partials._video',
            ['video_url' => $this->url, 'width' => '60px', 'height' => '60px', 'autoplay' => true, 'muted' => true, 'loop' => true]
        );
    }

    public function getPreviewAttribute(): ?ResizablePicture
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->attachment("preview.image") ?? $this->generatePreview(); //TODO: sandi, here we need a null coelecence to a default "image not avaliable" image.
    }

    public function storeVideo()
    {
        $tempDir = new TemporaryDirectory();
        $originalFile = $tempDir->path("original." . $this->filename);
        File::put($originalFile, $this->getContents());

        $ffmpegResource = $this->getFFMpeg();
        $raw = $ffmpegResource->open($originalFile);

        if (MyStr::endsWith(strtolower($originalFile), '.gif')) {
            $raw->addFilter(new GifTranscodeFilter());
        } else {
            $raw->addFilter(new PixelFormatFilter());
        }
        $format = new X264('libmp3lame');
        $transcodedFile = $tempDir->path($this->filename);
        $image = $raw->save($format, $transcodedFile);
        $this->attachToModel($originalFile, [
            'key' => 'original',
            'type' => Video::class,
            'filename' => $this->filename,
            'disk' => $this->disk,
        ]);
        $this->putFile($transcodedFile);
        $this->setStored();
    }

    protected function setStored()
    {
        $meta = $this->metadata;
        $this->metadata['image.size'] = true;
        $this->metadata = $meta;
    }

    public function getIsStoredAttribute(): bool
    {
        return $this->getMetadata('is_stored', false);
    }

    protected function generatePreview(): ?ResizablePicture
    {
        $tempDir = new TemporaryDirectory();
        $tempDir->create();
        $path = $tempDir->path("old." . $this->filename);
        File::put($path, $this->getContents());
        $ffmpegResource = $this->getFFMpeg();
        $raw = $ffmpegResource->open($path);
        $raw->addFilter(new VideoMuteFilter());
        if (MyStr::endsWith(strtolower($path), '.gif')) {
            $raw->addFilter(new GifTranscodeFilter());
        } else {
            $raw->addFilter(new PixelFormatFilter());
        }
        $nuFilename = str_replace('.mp4', '.jpg', $this->filename);
        $newPath = $tempDir->path($nuFilename);
        $frame = $raw->frame(TimeCode::fromSeconds(0));
        $frame->save($newPath);

        $preview = $this->attachToModel($newPath, [
            'key' => 'preview.image',
            'type' => ResizablePicture::class,
            'filename' => $nuFilename,
            'disk' => $this->disk,
        ]);
        if ($preview != null) {
            assert($preview instanceof ResizablePicture, "the preview should be a resizablePicture");
            $tempDir->delete();

            return $preview;
        }
        //TODO: sandi we need to log an error at this point to indicate that somthing went wrong. includeing the $tempDir (because it still exists)
        return null;
    }

    public function getPreview(): ?Attachment
    {
        /** @var Picture|null $att */
        $att = $this->attachment('preview.image');

        if (null !== $att) {
            return $att;
        }

        return $this->generatePreview();
    }

    /**
     * @return FFMpeg
     */
    public function getFFMpeg(): FFMpeg
    {
        $stem = BC::ffmpegBase();
        $ffMpegBinary = DIRECTORY_SEPARATOR == '/' ? 'ffmpeg' : 'ffmpeg.exe';
        $ffProbeBinary = DIRECTORY_SEPARATOR == '/' ? 'ffprobe' : 'ffprobe.exe';

        try {
            $ffmpegResource = FFMpeg::create([
                'ffmpeg.binaries' => $stem.$ffMpegBinary,
                'ffprobe.binaries' => $stem.$ffProbeBinary,
                'timeout' => 120,
                'threads' => 2,
            ]);
        } catch (ExecutableNotFoundException $e) {
            $ffprobe = $stem.'ffprobe';
            $msg = $ffprobe.' not found.  If on CI, time to update APP_FFMPEG_BINARY_PATH in env section?'.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString();

            throw new ExecutableNotFoundException($msg, 0, $e);
        }

        return $ffmpegResource;
    }
}
