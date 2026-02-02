<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationToDeductionLogsTable extends Migration
{
    public function up()
    {
        Schema::table('deduction_logs', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('processed_at');
            $table->string('evidence_path', 255)->nullable()->after('status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('evidence_path');
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            $table->index(['status', 'month', 'year']);
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('deduction_logs', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['status', 'month', 'year']);
            $table->dropColumn(['status', 'evidence_path', 'verified_by', 'verified_at']);
        });
    }
}
