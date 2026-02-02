<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidencePathToCashEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->string('evidence_path', 255)->nullable()->after('category');
        });
    }

    public function down()
    {
        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropColumn('evidence_path');
        });
    }
}
