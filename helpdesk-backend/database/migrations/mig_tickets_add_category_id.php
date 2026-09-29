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
        if (! Schema::hasColumn('tickets', 'category_id')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('categories')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'category_id')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('category_id');
            });
        }
    }
};
