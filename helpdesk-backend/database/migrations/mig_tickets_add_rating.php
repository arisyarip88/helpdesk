<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tickets', 'rating')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedTinyInteger('rating')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'rating')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }
    }
};