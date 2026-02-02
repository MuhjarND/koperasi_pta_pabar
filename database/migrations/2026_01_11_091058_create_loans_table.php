<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('term_months');
            $table->text('purpose');
            $table->string('status', 30)->default('submitted');
            $table->foreignId('sekretaris_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('sekretaris_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('bendahara_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('bendahara_note')->nullable();
            $table->timestamp('treasurer_approved_at')->nullable();
            $table->foreignId('ketua_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ketua_note')->nullable();
            $table->timestamp('chairman_approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->index('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
}
