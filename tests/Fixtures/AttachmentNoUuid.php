<?php

namespace Cruxinator\Attachments\Tests\Fixtures;

use Cruxinator\Attachments\Models\Attachment;

class AttachmentNoUuid extends Attachment
{
    public function getUuidAttribute()
    {
        return '';
    }
}
