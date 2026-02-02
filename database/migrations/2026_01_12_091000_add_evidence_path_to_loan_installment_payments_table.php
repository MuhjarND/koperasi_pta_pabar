<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidencePathToLoanInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('loan_installment_payments', function (Blueprint $table) {
            $table->string('evidence_path', 255)->nullable()->after('note');
        });
    }

    public function down()
    {
        Schema::table('loan_installment_payments', function (Blueprint $table) {
            $table->dropColumn('evidence_path');
        });
    }
}
