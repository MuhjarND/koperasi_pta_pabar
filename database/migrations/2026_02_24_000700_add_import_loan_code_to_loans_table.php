<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportLoanCodeToLoansTable extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('import_loan_code', 100)->nullable()->after('pdf_token');
            $table->unique('import_loan_code');
        });
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropUnique(['import_loan_code']);
            $table->dropColumn('import_loan_code');
        });
    }
}
