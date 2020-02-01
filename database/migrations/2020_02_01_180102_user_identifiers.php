<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserIdentifiers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('identifier')->unique()->nullable()->after('id');
            $table->dropUnique(['url']);
            $table->string('url')->nullable()->change();
            $table->string('email')->nullable()->after('url');
        });

        // Migrate the url value to identifier for existing users
        DB::update('UPDATE users SET identifier = url');

        // Make identifier not nullable after setting the identifier
        Schema::table('users', function (Blueprint $table) {
            $table->string('identifier')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('url')->nullable(false)->change();
            $table->dropColumn('identifier');
            $table->dropColumn('email');
        });
    }
}
