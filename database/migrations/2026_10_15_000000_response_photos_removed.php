<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether the photos of a response were removed by hand, so they aren't added back when
 * the webmention is sent again.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->boolean('photos_removed')->default(false);
        });
    }

    public function down()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('photos_removed');
        });
    }
};
