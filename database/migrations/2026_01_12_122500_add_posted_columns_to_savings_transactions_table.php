<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostedColumnsToSavingsTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable()->after('note');
            $table->unsignedBigInteger('posted_by')->nullable()->after('posted_at');
            $table->index('posted_at');
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropForeign(['posted_by']);
            $table->dropIndex(['posted_at']);
            $table->dropColumn(['posted_at', 'posted_by']);
        });
    }
}
