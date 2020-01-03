<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResponseModeration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->boolean('approved')->default(0);
            $table->bigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
        });
        DB::table('responses')->update([
            'approved' => 1,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('approved');
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_at');
        });
    }
}
