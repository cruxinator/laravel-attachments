<?php

namespace Cruxinator\Attachments\Tests\ResponseModels;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\TestCase;
use Cruxinator\Attachments\ResponseModels\DropZoneForm;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;

class DropZoneFormTest extends TestCase
{
    use DatabaseTransactions;

    public function testDropZoneForm()
    {
        $form = new DropZoneForm('form');
        $this->assertEquals('form', $form->getDropZoneForm());
    }
}
