<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

use Cruxinator\Attachments\Models\Attachment;

class Media extends Attachment
{
    public static $singleTableSubclasses = [
        Picture::class,
        Video::class,
    ];
}
