<?php


namespace Cruxinator\Attachments\Models;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Cruxinator\Attachments\Models\Attachment;

abstract class Media extends Attachment
{
    public static $singleTableSubclasses = [
        Picture::class,
        Video::class
    ];
    public abstract function getHtml():string;
}