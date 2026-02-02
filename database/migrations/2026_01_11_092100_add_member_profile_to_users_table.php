<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberProfileToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_no', 30)->nullable()->after('role');
            $table->string('nip', 30)->nullable()->after('member_no');
            $table->string('unit_kerja', 120)->nullable()->after('nip');
            $table->string('phone', 30)->nullable()->after('unit_kerja');
            $table->string('address', 255)->nullable()->after('phone');
            $table->string('status', 20)->default('active')->after('address');
            $table->index('member_no');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['member_no']);
            $table->dropIndex(['status']);
            $table->dropColumn(['member_no', 'nip', 'unit_kerja', 'phone', 'address', 'status']);
        });
    }
}
