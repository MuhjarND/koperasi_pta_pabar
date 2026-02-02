<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('loan_installment_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedInteger('installment_no');
            $table->date('paid_at');
            $table->decimal('amount_principal', 14, 2);
            $table->decimal('amount_fee', 14, 2);
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'installment_no']);
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_installment_payments');
    }
}
