<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->bigInteger('event_id');
            $table->string('type', 30);
            $table->string('url', 512)->nullable();
            $table->string('source_url', 512)->nullable();
            $table->datetime('published')->nullable();
            $table->string('author_name', 512)->nullable();
            $table->string('author_photo', 512)->nullable();
            $table->string('author_url', 512)->nullable();
            $table->string('name', 512)->nullable();
            $table->text('content_html')->nullable();
            $table->text('content_text')->nullable();
            $table->text('photos')->nullable();
            $table->string('rsvp', 30)->nullable();
            $table->bigInteger('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('responses');
    }
}
