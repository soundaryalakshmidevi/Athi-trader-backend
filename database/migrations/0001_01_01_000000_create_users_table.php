<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('user_name');
            $table->string('aadhar_number');
            $table->string('address');
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('pincode');
            $table->string('district');
            $table->string('password');
            $table->enum('user_type', ['admin', 'employee', 'user']);
            $table->enum('status', ['active', 'inactive']);
            $table->string('mobile_number');
            $table->string('email')->nullable()->unique();

            $table->string('alter_mobile_number')->nullable();
            $table->longText('profile_photo')->nullable();
            $table->longText('sign_photo')->nullable();
             $table->longText('nominee_photo')->nullable();
            $table->longText('nominee_sign')->nullable();
            $table->string('ref_name')->nullable();
            $table->string('ref_user_id')->nullable();
            $table->longText('ref_sign_photo')->nullable();
            $table->string('ref_aadhar_number')->nullable();
            $table->string('qualification')->nullable();
            $table->string('designation')->nullable();
            // $table->date('updated_on')->nullable();
            $table->string('added_by');
            $table->string('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
