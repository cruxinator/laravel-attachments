<?php

namespace Cruxinator\Attachments\Models;

abstract class Media extends Attachment
{
    public static $singleTableSubclasses = [
        Picture::class,
        Video::class,
    ];

    abstract public function getHtml(): string;
}
