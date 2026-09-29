<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketCategoryNormalizationTest extends TestCase
{
    public function test_migration_assigns_general_categories_before_dropping_department_id(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id', 20);
            $table->timestamps();
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('department_id', 20);
            $table->unsignedBigInteger('category_id')->nullable();
        });
        DB::table('categories')->insert([
            'id' => 9,
            'name' => 'Hardware',
            'department_id' => 'D2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tickets')->insert([
            ['id' => 1, 'department_id' => 'D1', 'category_id' => null],
            ['id' => 2, 'department_id' => 'D2', 'category_id' => 9],
        ]);

        $migration = require base_path('database/migrations/mig_tickets_normalize_category.php');
        $migration->up();

        $this->assertFalse(Schema::hasColumn('tickets', 'department_id'));
        $this->assertSame(9, (int) DB::table('tickets')->where('id', 2)->value('category_id'));
        $generalCategoryId = DB::table('categories')
            ->where('name', 'Umum')
            ->where('department_id', 'D1')
            ->value('id');
        $this->assertSame((int) $generalCategoryId, (int) DB::table('tickets')->where('id', 1)->value('category_id'));
    }
}
