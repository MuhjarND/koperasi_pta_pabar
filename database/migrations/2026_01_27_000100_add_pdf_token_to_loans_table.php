<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPdfTokenToLoansTable extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('pdf_token', 64)->nullable()->after('pdf_path');
            $table->index('pdf_token');
        });
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['pdf_token']);
            $table->dropColumn('pdf_token');
        });
    }
}
