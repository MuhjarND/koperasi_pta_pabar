<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVerificationColumnsToCashEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('evidence_path');
            $table->unsignedBigInteger('verified_by')->nullable()->after('status');
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            $table->index('status');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('cash_entries')
            ->whereNull('status')
            ->update(['status' => 'approved']);
    }

    public function down()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'verified_by', 'verified_at']);
        });
    }
}
