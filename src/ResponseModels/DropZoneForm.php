<?php

namespace Cruxinator\Attachments\ResponseModels;

use Cruxinator\ResponseModel\ResponseModel;

class DropZoneForm extends ResponseModel
{
    protected $view = 'Attachments::dropzone.form';

    protected $nameOfForm = null;

    public function __construct(string $nameOfForm)
    {
        parent::__construct();
        $this->enableGetterToSnakeMethodMap();
        $this->nameOfForm = $nameOfForm;
    }

    public function getDropZoneForm(): string
    {
        return $this->nameOfForm;
    }
}
