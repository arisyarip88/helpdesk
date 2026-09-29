<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketAnalyticsControllerTest extends TestCase
{
    public function test_rating_ranking_aggregates_multiple_ratings_for_each_resolver(): void
    {
        $this->createRatingTables();
        DB::table('departments')->insert(['kode' => 'D1', 'nama' => 'Unit 1']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Umum', 'department_id' => 'D1']);
        DB::table('users')->insert([
            ['id' => 10, 'role_id' => 3, 'department_id' => 'D1', 'name' => 'Petugas A'],
            ['id' => 11, 'role_id' => 3, 'department_id' => 'D1', 'name' => 'Petugas B'],
            ['id' => 12, 'role_id' => 2, 'department_id' => 'D1', 'name' => 'Admin'],
        ]);
        DB::table('tickets')->insert([
            $this->ratedTicket(1, 10, 5),
            $this->ratedTicket(2, 10, 4),
            $this->ratedTicket(3, 11, 5),
            $this->ratedTicket(4, 12, 1),
        ]);
        $admin = new User;
        $admin->id = 99;
        $admin->role_id = 1;

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/analytics/rating-ranking')
            ->assertOk()
            ->assertJsonPath('data.users.0.user_id', 11)
            ->assertJsonPath('data.users.1.user_id', 10)
            ->assertJsonPath('data.users.1.average_rating', 4.5)
            ->assertJsonPath('data.users.1.rating_count', 2);
    }

    private function createRatingTables(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');

        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode')->primary();
            $table->string('nama');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('department_id')->nullable();
            $table->string('name');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id');
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('terselesaikan_pada')->nullable();
        });
    }

    private function ratedTicket(int $id, int $resolverId, int $rating): array
    {
        return [
            'id' => $id,
            'category_id' => 1,
            'resolved_by' => $resolverId,
            'status_id' => 4,
            'rating' => $rating,
            'created_at' => '2026-09-25 10:00:00',
            'terselesaikan_pada' => '2026-09-25 12:00:00',
        ];
    }
}
