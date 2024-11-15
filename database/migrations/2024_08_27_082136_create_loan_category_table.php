<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanCategoryTable extends Migration
{
    public function up()
    {
        Schema::create('loan_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id');
            $table->string('category_name');
            $table->enum('category_type', ['weekly', 'daily', 'monthly']);
            $table->integer('duration');
            $table->integer('interest_rate');
            $table->enum('status', ['active', 'inactive']);
              $table->datetime('added_on')->useCurrent();
            $table->datetime('updated_on')->useCurrentOnUpdate()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_category');
    }
}
