<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            
            // Relasi ke tabel roles
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            
            // Relasi ke tabel statuses
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();

            // Wajib VARCHAR(20) agar sesuai dengan kolom 'kode' di tabel departments
            $table->string('department_id', 20)->nullable();
            
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('tlp', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->timestamps();

            // Foreign Key ke kolom 'kode' pada tabel departments
            $table->foreign('department_id')
                  ->references('kode')
                  ->on('departments')
                  ->onUpdate('cascade')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};