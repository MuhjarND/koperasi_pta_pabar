<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransferEvidenceToLoansTable extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('transfer_evidence_path')->nullable()->after('pdf_path');
            $table->timestamp('transfered_at')->nullable()->after('transfer_evidence_path');
            $table->unsignedBigInteger('transfered_by')->nullable()->after('transfered_at');
        });

        DB::table('loans')
            ->where('status', 'approved_chairman')
            ->whereNull('transfered_at')
            ->update([
                'transfered_at' => DB::raw('chairman_approved_at'),
            ]);
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['transfer_evidence_path', 'transfered_at', 'transfered_by']);
        });
    }
}
