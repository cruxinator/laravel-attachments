<?php

namespace Cruxinator\Attachments\Http\Controllers;

use Carbon\Carbon;
use Cruxinator\Attachments\Contracts\AttachmentContract;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Lang;

class ShareController extends Controller
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

    public function download($token, Request $request)
    {
        $disposition = null;

        try {
            $data = json_decode(Crypt::decryptString($token), null, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException $e) {
            abort(404, Lang::get('attachments::messages.errors.file_not_found'));
        }

        $id = $data->id;
        $expire = $data->expire;

        if (Carbon::createFromTimestamp($expire)->isPast()) {
            abort(403, Lang::get('attachments::messages.errors.expired'));
        }

        if (property_exists($data, 'disposition')) {
            $disposition = $data->disposition === 'inline' ? $data->disposition : 'attachment';
        }

        if ($file = $this->model->where('uuid', $id)->first()) {
            try {
                /** @var AttachmentContract $file */
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
