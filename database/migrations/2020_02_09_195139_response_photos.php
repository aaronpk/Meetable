<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResponsePhotos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('response_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->bigInteger('response_id');
            $table->bigInteger('event_id');
            $table->integer('sort_order')->nullable();
            $table->string('source_url', 512)->nullable();
            $table->string('original_filename', 512)->nullable();
            $table->string('original_url', 512)->nullable();
            $table->string('full_url', 512)->nullable();
            $table->string('large_url', 512)->nullable();
            $table->string('square_url', 512)->nullable();
            $table->text('alt')->nullable();
            $table->boolean('approved')->default(0);
        });

        // Migrate all photo URLs, alt text, and order from the responses table
        $responses = DB::table('responses')->get();
        foreach($responses as $response) {
            if($response->photos) {
                $event = DB::table('events')->where('id', $response->event_id)->first();
                $photo_order = json_decode($event->photo_order, true);

                $photos = json_decode($response->photos, true);
                $photo_alt = json_decode($response->photo_alt, true);
                foreach($photos as $photo) {
                    DB::table('response_photos')->insert([
                        'created_at' => $response->created_at,
                        'updated_at' => $response->updated_at,
                        'response_id' => $response->id,
                        'event_id' => $response->event_id,
                        'original_url' => $photo,
                        'alt' => ($photo_alt[$photo] ?? null),
                        'sort_order' => (array_search($photo, $photo_order ?: []) ?: 0),
                        'approved' => $response->approved,
                    ]);
                }
            }
        }

        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('photo_alt');
            $table->dropColumn('photos');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('photo_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('response_photos');

        Schema::table('responses', function (Blueprint $table) {
            $table->text('photo_alt')->nullable();
            $table->text('photos')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->text('photo_order')->nullable();
        });
    }
}
