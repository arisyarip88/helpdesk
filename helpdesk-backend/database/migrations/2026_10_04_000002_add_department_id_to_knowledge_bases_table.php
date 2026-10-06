<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('knowledge_bases', 'department_id')) {
            return;
        }

        Schema::table('knowledge_bases', function (Blueprint $table) {
            // NULL = knowledge umum (semua unit)
            $table->string('department_id', 20)->nullable()->after('is_active');
            $table->foreign('department_id')
                ->references('kode')
                ->on('departments')
                ->onUpdate('cascade')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('knowledge_bases', 'department_id')) {
            return;
        }

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
