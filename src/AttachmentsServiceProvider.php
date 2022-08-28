<?php

namespace Cruxinator\Attachments;

use Cruxinator\Attachments\Console\Commands\CheckConfigBestPractice;
use Cruxinator\Attachments\Console\Commands\CleanupAttachments;
use Cruxinator\Attachments\Console\Commands\MigrateAttachments;
use Cruxinator\Attachments\Contracts\AttachmentContract;
use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Package\Package;
use Cruxinator\Package\PackageServiceProvider;

class AttachmentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-attachments')
            ->hasMigration('create_crux_attachments_table')
            ->hasTranslations()
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasCommands(
                [
                    CheckConfigBestPractice::class,
                    CleanupAttachments::class,
                    MigrateAttachments::class,
                    ]
            );
        // Bind Model to Interface
        $this->app->bind(
            AttachmentContract::class,
            $this->app['config']->get('attachments.attachment_model') ?? Attachment::class
        );
    }

    public function bootingPackage()
    {
    }
}
