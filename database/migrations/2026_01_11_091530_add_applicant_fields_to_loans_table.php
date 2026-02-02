<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicantFieldsToLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('member_no', 30)->nullable()->after('user_id');
            $table->string('applicant_name', 150)->nullable()->after('member_no');
            $table->string('nip', 30)->nullable()->after('applicant_name');
            $table->string('unit_kerja', 120)->nullable()->after('nip');
            $table->string('phone', 30)->nullable()->after('unit_kerja');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['member_no', 'applicant_name', 'nip', 'unit_kerja', 'phone']);
        });
    }
}
