<?php

namespace Cruxinator\Attachments\Contracts;

use Cruxinator\Attachments\Models\ResizablePicture;

interface IPreviewable
{
    function getPreviewAttribute():?ResizablePicture;
}