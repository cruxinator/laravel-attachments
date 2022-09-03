<?php

namespace Cruxinator\Attachments\Tests\ResponseModels;

use Cruxinator\Attachments\ResponseModels\DropZoneForm;
use Cruxinator\Attachments\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DropZoneFormTest extends TestCase
{
    use DatabaseTransactions;

    public function testDropZoneForm()
    {
        $form = new DropZoneForm('form');
        $this->assertEquals('form', $form->getDropZoneForm());
    }
}
