<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEditFieldsToCashEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->text('edit_note')->nullable()->after('description');
            $table->unsignedBigInteger('edited_by')->nullable()->after('verified_by');
            $table->timestamp('edited_at')->nullable()->after('verified_at');

            $table->index('edited_by');
            $table->foreign('edited_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropIndex(['edited_by']);
            $table->dropColumn(['edit_note', 'edited_by', 'edited_at']);
        });
    }
}

