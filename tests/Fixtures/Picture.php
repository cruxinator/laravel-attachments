<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

class Picture extends Media
{
    public static $singleTableSubclasses = [ResizablePicture::class];
}
