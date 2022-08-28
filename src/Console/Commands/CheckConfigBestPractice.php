<?php

namespace Cruxinator\Attachments\Console\Commands;

use Cruxinator\Attachments\Models\Attachment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\InputOption;

class CheckConfigBestPractice extends Command
{
    protected $signature = 'attachments:check_config';

    public function __construct()
    {
        parent::__construct();

        $this->setDescription(Lang::get('attachments::messages.console.check_description'));

        $this->getDefinition()->addOption(
            new InputOption(
                'expert',
                'e',
                InputOption::VALUE_OPTIONAL,
                Lang::get('attachments::messages.console.check_option_expert')
            )
        );
    }

    public function handle()
    {
        if ($this->hasOption('expert')) {
            $this->info(Lang::get('attachments::messages.console.check_option_expert'));
        } else {
            $this->checkOnlyModelOrStiUsed();
        }
        $this->checkStorageDiskExists();
        $this->checkAttachmentModel();
        $this->checkAttachmentSubModel();
    }

    private function checkStorageDiskExists()
    {
        $configEntry = Config::get('attachments.storage_default_filesystem');
        if (null === $configEntry) {
            return null;
        }
        //TODO: better improve on this.
        try {
            Storage::disk($configEntry);
        } catch (Exception $e) {
            $this->warn(
                sprintf(
                    Lang::get('attachments::messages.console.check_warn_disk_not_found'),
                    $configEntry
                )
            );
        }
    }

    private function checkAttachmentSubModel()
    {
        $configEntry = 'attachments.attachment_sub_models';
        foreach (Config::get($configEntry) as $subModel) {
            $this->checkClassExists($subModel);
            $this->checkChildClass($subModel, $configEntry);
        }
    }

    private function checkAttachmentModel()
    {
        $configEntry = 'attachments.attachment_model';
        $attachmentModel = Config::get($configEntry);
        $this->checkClassExists($attachmentModel);
        if ($attachmentModel !== Attachment::class) {
            $this->checkSubClass($attachmentModel, $configEntry);
        }
    }

    private function checkChildClass($class, $configEntry)
    {
        $attachmentClass = Config::get('attachments.attachment_model');
        if (get_parent_class($class) !== $attachmentClass) {
            $this->warn(
                sprintf(
                    Lang::get('attachments::messages.console.check_warn_should_be_child'),
                    $class,
                    $configEntry,
                    $attachmentClass
                )
            );
        }
    }

    private function checkSubClass($class, $configEntry)
    {
        if (! is_subclass_of($class, Attachment::class)) {
            $this->warn(
                sprintf(
                    Lang::get('attachments::messages.console.check_warn_should_inherit'),
                    $class,
                    $configEntry,
                    Attachment::class
                )
            );
        }
    }

    private function checkClassExists($className)
    {
        if (! class_exists($className)) {
            $this->warn(
                sprintf(
                    Lang::get('attachments::messages.console.check_warn_missing_class'),
                    $className
                )
            );
        }
    }

    private function checkOnlyModelOrStiUsed()
    {
        if (Config::get('attachments.attachment_model') !== Attachment::class &&
            ! empty(Config::get('attachments.attachment_sub_models'))
        ) {
            $this->warn(Lang::get('attachments::messages.console.check_warn_inherit_and_sti'));
        }
    }
}
