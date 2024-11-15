<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginTable extends Migration
{
    public function up()
    {
        Schema::create('logins', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('user_name');
            $table->string('password');
            $table->string('security_password')->nullable();
            $table->enum('status', ['active', 'inactive']);
            $table->string('email')->nullable()->unique();
            $table->string('mobile_number');
            $table->date('added_on')->nullable();
            $table->date('updated_on')->nullable();
            $table->string('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->enum('user_type', ['admin', 'employee', 'user']);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();


        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
