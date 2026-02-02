<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToCashEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('category');
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}
