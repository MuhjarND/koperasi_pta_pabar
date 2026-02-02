<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToLoanInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('loan_installment_payments', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('evidence_path');
            $table->boolean('is_settlement')->default(false)->after('status');
            $table->unsignedBigInteger('validated_by')->nullable()->after('created_by');
            $table->timestamp('validated_at')->nullable()->after('validated_by');

            $table->index(['loan_id', 'status']);
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('loan_installment_payments', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['loan_id', 'status']);
            $table->dropColumn(['status', 'is_settlement', 'validated_by', 'validated_at']);
        });
    }
}
