<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->index();
            $table->string('uuid', 64)->index();
            $table->string('disk', 32);
            $table->string('filepath', 512);
            $table->string('filename', 255);
            $table->string('filetype', 512);
            $table->unsignedInteger('filesize');
            $table->string('key', 64)->nullable();
            $table->string('group')->nullable();
            $table->string('title', 92)->nullable();
            $table->text('description')->nullable();
            $table->string('preview_url', 512)->nullable();
            $table->morphs('attachable');
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
