<?php

namespace Cruxinator\Attachments\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Lang;
use Cruxinator\Attachments\Contracts\AttachmentContract;
use Cruxinator\Attachments\Models\Attachment;

class DownloadController extends Controller
{
    /**
     * Attachment model
     *
     * @var AttachmentContract
     */
    protected $model;

    public function __construct(AttachmentContract $model)
    {
        $this->model = $model;
    }

    public function download($id, Request $request)
    {
        $disposition = ($disposition = $request->input('disposition')) === 'inline' ? $disposition : 'attachment';
        if ($file = $this->model->where('uuid', $id)->first()) {
            try {
                /** @var Attachment $file */
                $file->getContents();
                if (! $file->output($disposition)) {
                    abort(403, Lang::get('attachments::messages.errors.access_denied'));
                }
            } catch (FileNotFoundException $e) {
                abort(404, Lang::get('attachments::messages.errors.file_not_found'));
            }
        }
        abort(404, Lang::get('attachments::messages.errors.file_not_found'));
    }
}
