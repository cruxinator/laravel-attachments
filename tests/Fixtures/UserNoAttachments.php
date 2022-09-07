<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class UserNoAttachments extends Model
{
    protected $fillable = ['name'];

    protected $table = 'users';
}
