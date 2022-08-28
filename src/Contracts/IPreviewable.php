<?php

namespace Cruxinator\Attachments\Contracts;

use Cruxinator\Attachments\Models\ResizablePicture;

interface IPreviewable
{
    public function getPreviewAttribute(): ?ResizablePicture;
}
