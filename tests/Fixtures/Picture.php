<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

use Cruxinator\Attachments\Tests\Fixtures\ResizablePicture;

class Picture extends Media
{
    public static $singleTableSubclasses = [ResizablePicture::class];
}
