<?php

use Cruxinator\Attachments\Models\Attachment;

return [
    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Determine whether or not to automatically define attachments routes.
    | Used for local storage only as other storage should define their public URL.
    |
    */
    'routes' => [
        'publish' => true,
        'prefix' => 'attachments',
        'middleware' => 'web',
        'pattern' => '/{id}/{name}',
        'shared_pattern' => '/shared/{token}',
        'dropzone' => [
            'upload_pattern' => '/dropzone',
            'delete_pattern' => '/dropzone/{id}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | Attachment model used
    |
    */
    'attachment_model' => env('ATTACHMENTS_MODEL', Attachment::class),

    /*
    |--------------------------------------------------------------------------
    | Inheritance models
    |--------------------------------------------------------------------------
    |
    | class list of direct children of Packages\Attachments\Models\Attachment
    |
    */
    'attachment_sub_models' => [
        Cruxinator\Attachments\Models\Media::class,
        Cruxinator\Attachments\Models\Document::class,
        Cruxinator\Attachments\Models\Archive::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uuid
    |--------------------------------------------------------------------------
    |
    | Default attachment model uses an UUID column. You can define your own UUID
    | generator here : a global function name or a static class method in the form :
    | App\Namespace\ClassName@method
    |
    */
    'uuid_provider' => Attachment::class.'@uuid_v4_base36',

    /*
    |--------------------------------------------------------------------------
    | Behaviors
    |--------------------------------------------------------------------------
    |
    | Configurable behaviors :
    | - Concrete files can be delete when the database entry is deleted
    | - Dropzone delete can check for CSRF token match (set on upload)
    |
    */
    'behaviors' => [
        'cascade_delete' => env('ATTACHMENTS_CASCADE_DELETE', true),
        'dropzone_check_csrf' => env('ATTACHMENTS_DROPZONE_CHECK_CSRF', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Declare the attachment model attributes
    |--------------------------------------------------------------------------
    |
    | This allow to extend the attachment model with new columns
    | `dropzone_attributes` holds the public fields returned after a successful upload via DropzoneController
    |
    */
    'attributes' => ['title', 'description', 'key', 'disk', 'filepath', 'group'],

    'dropzone_attributes' => ['uuid', 'url', 'url_inline', 'filename', 'filetype', 'filesize', 'title', 'description', 'key', 'group'],

    /*
    |--------------------------------------------------------------------------
    | Attachment Storage Directory
    |--------------------------------------------------------------------------
    |
    | Defines the directory prefix where new attachment files are stored
    |
    */
    'storage_directory' => [
        'prefix' => rtrim(env('ATTACHMENTS_STORAGE_DIRECTORY_PREFIX', 'attachments'), '/'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Attachment Storage Default FileSystem
    |--------------------------------------------------------------------------
    |
    | Defines the storage disk default driver where new attachment files are stored
    |
    */
    'storage_default_filesystem' => env('ATTACHMENTS_STORAGE_DEFAULT_FILESYSTEM'),
    /*
    |--------------------------------------------------------------------------
    | Database configuration
    |--------------------------------------------------------------------------
    |
    | Allows to set the database connection name for the module's models
    |
    */
    'database' => [
        'connection' => env('ATTACHMENTS_DATABASE_CONNECTION'),
    ],

    'proxied_adapters' => [

    ],
];
