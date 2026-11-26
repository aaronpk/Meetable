<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the columns the event listings filter and sort on. Until now the events
 * table had no index besides its primary key, so every listing and every lookup of an
 * event by its key scanned the whole table.
 *
 * The key is not unique because recurring event templates have no key.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('key');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('sort_date');
            $table->index('parent_id');
            $table->index('created_from_template_event_id');
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['sort_date']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['created_from_template_event_id']);
        });
    }
};
