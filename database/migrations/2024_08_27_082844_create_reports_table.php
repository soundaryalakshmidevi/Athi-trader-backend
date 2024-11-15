<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('report', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('file_name'); // VARCHAR for file name 
            $table->unsignedBigInteger('generated_by'); // BIGINT for user ID who generated the report

            // You can add foreign key constraints here if needed
            // $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('report');
    }
}
