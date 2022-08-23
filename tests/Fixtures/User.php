<?php


namespace Cruxinator\Attachments\Tests\Fixtures;


use Cruxinator\Attachments\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasAttachments;

    protected $fillable = ['name'];
}