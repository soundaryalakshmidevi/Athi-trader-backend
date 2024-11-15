<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('loan_id', 250)->collation('utf8mb4_unicode_ci');
            $table->string('user_id', 255)->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('employee_id', 255)->collation('utf8mb4_unicode_ci')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->bigInteger('loan_amount')->nullable();
            $table->enum('loan_category', ['weekly', 'daily', 'monthly'])->collation('utf8mb4_unicode_ci');
            $table->date('loan_date')->nullable();
            $table->bigInteger('total_amount')->nullable();
            $table->longText('image')->nullable()->collation('utf8mb4_unicode_ci')->nullable();
            $table->enum('status', ['pending', 'inprogress', 'completed', 'cancelled'])->collation('utf8mb4_unicode_ci')->nullable();
            $table->date('loan_closed_date')->nullable();
            $table->decimal('next_amount', 10, 0)->nullable();
            $table->decimal('pending_amount', 10, 0)->nullable();
            $table->decimal('due_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->date('paid_on')->nullable();
            $table->string('collection_by', 255)->nullable()->collation('utf8mb4_unicode_ci')->nullable();
            $table->enum('payment_status', ['unpaid', 'pending', 'paid'])->default('unpaid')->collation('utf8mb4_unicode_ci')->nullable();
            $table->timestamps()->nullable();

            $table->index('loan_id')->nullable();
            $table->index('category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction');
    }
}
