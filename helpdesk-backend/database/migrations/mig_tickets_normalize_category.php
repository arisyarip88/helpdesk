<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'department_id')) {
            return;
        }

        DB::transaction(function (): void {
            $tickets = DB::table('tickets')
                ->whereNull('category_id')
                ->get(['id', 'department_id']);

            foreach ($tickets as $ticket) {
                if (! $ticket->department_id) {
                    throw new RuntimeException('Cannot migrate ticket '.$ticket->id.' without a department.');
                }

                $categoryId = DB::table('categories')->where([
                    'name' => 'Umum',
                    'department_id' => $ticket->department_id,
                ])->value('id');

                if (! $categoryId) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => 'Umum',
                        'department_id' => $ticket->department_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update(['category_id' => $categoryId]);
            }
        });

        foreach (Schema::getForeignKeys('tickets') as $foreignKey) {
            if (in_array('department_id', $foreignKey['columns'], true)) {
                Schema::table('tickets', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'department_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('department_id', 20)->nullable()->after('category_id');
        });

        DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->update(['tickets.department_id' => DB::raw('categories.department_id')]);

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreign('department_id', 'dept')
                ->references('kode')
                ->on('departments')
                ->cascadeOnDelete();
        });
    }
};
