<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('direction', 10);
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->string('category', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'direction']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_entries');
    }
}
