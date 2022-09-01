<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Picture;
use Cruxinator\Attachments\Tests\Fixtures\Video;

class Media extends Attachment
{
    public static $singleTableSubclasses = [
        Picture::class,
        Video::class,
    ];
}
