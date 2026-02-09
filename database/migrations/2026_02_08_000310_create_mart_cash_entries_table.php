<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMartCashEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('mart_cash_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('direction', 10);
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->string('category', 100)->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'direction']);
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mart_cash_entries');
    }
}
