<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('history', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('table_name'); // VARCHAR for table name
            $table->json('modified_data'); // JSON for modified data
            $table->date('modified_on'); // DATE for modified date 
            $table->unsignedInteger('modified_by'); // ID of the user who modified

            // You can add foreign key constraints here if needed
            // $table->foreign('modified_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('history');
    }
}
