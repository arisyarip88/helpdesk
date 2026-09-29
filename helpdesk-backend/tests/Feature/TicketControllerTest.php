<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    public function test_user_ticket_create_stores_category_and_exposes_its_department(): void
    {
        $this->createUserTicketTables();
        $user = new User;
        $user->id = 7;
        $user->role_id = 4;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets', [
                'user_id' => 7,
                'category_id' => 12,
                'judul' => 'Tidak bisa login',
                'deskripsi' => 'Gagal mengakses akun',
                'prioritas' => 'medium',
            ])
            ->assertCreated()
            ->assertJsonPath('data.category_id', 12)
            ->assertJsonPath('data.department.kode', 'D2');

        $this->assertDatabaseHas('tickets', [
            'category_id' => 12,
        ]);
    }

    public function test_user_ticket_edit_updates_category_and_derived_department(): void
    {
        $this->createUserTicketTables();
        DB::table('tickets')->insert([
            'id' => 1,
            'nomor_tiket' => 'TK-1',
            'user_id' => 7,
            'category_id' => 11,
            'judul' => 'Tiket lama',
            'deskripsi' => 'Deskripsi',
            'prioritas' => 'medium',
            'status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = new User;
        $user->id = 7;
        $user->role_id = 4;

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tickets/1', ['category_id' => 12])
            ->assertOk()
            ->assertJsonPath('data.category_id', 12)
            ->assertJsonPath('data.department.kode', 'D2');

        $this->assertDatabaseHas('tickets', [
            'id' => 1,
            'category_id' => 12,
        ]);
    }

    public function test_role_three_cannot_change_ticket_category(): void
    {
        $this->createUserTicketTables();
        DB::table('tickets')->insert([
            'id' => 1,
            'nomor_tiket' => 'TK-1',
            'user_id' => 7,
            'category_id' => 11,
            'judul' => 'Tiket lama',
            'deskripsi' => 'Deskripsi',
            'prioritas' => 'medium',
            'status_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $staff = new User;
        $staff->id = 33;
        $staff->role_id = 3;

        $this->actingAs($staff, 'sanctum')
            ->putJson('/api/tickets/1', ['category_id' => 12])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => 1, 'category_id' => 11]);
    }

    public function test_overdue_filter_returns_all_tickets_that_breached_the_configured_limit(): void
    {
        $this->createTicketListTables();
        $this->travelTo('2026-09-27 10:00:00');
        DB::table('ticket_handling_settings')->insert([
            'id' => 1,
            'max_hours' => 30,
            'alert_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tickets')->insert([
            $this->ticketRow(1, 1, '2026-09-26 03:59:00'),
            $this->ticketRow(2, 2, '2026-09-26 04:01:00'),
            $this->ticketRow(3, 4, '2026-09-25 00:00:00'),
            $this->ticketRow(4, 5, '2026-09-27 09:00:00'),
        ]);
        DB::table('tickets')->where('id', 3)->update([
            'terselesaikan_pada' => '2026-09-26 07:00:00',
        ]);
        $user = new User;
        $user->role_id = 1;

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets?overdue=1&per_page=50')
            ->assertOk()
            ->assertJsonPath('overdue_count', 2)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.nomor_tiket', 'TK-1')
            ->assertJsonPath('data.data.1.nomor_tiket', 'TK-3');
    }

    public function test_bulk_completion_sets_finish_time_and_resets_rating(): void
    {
        $this->createBulkStatusTables();
        $this->travelTo('2026-09-27 10:00:00');
        DB::table('tickets')->insert([
            'id' => 1,
            'status_id' => 3,
            'rating' => 5,
            'terselesaikan_pada' => '2026-09-26 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = new User;
        $user->role_id = 1;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/bulk-status', [
                'ids' => [1],
                'status_id' => 4,
            ])
            ->assertOk();

        $ticket = DB::table('tickets')->find(1);
        $this->assertSame('2026-09-27 10:00:00', $ticket->terselesaikan_pada);
        $this->assertNull($ticket->rating);
    }

    public function test_role_three_is_recorded_as_resolver_for_individual_completion(): void
    {
        $this->createBulkStatusTables();
        DB::table('tickets')->insert([
            'id' => 1,
            'status_id' => 3,
            'rating' => null,
            'resolved_by' => null,
            'terselesaikan_pada' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = new User;
        $user->id = 33;
        $user->role_id = 3;

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tickets/1', ['status_id' => 4])
            ->assertOk();

        $this->assertSame(33, (int) DB::table('tickets')->value('resolved_by'));
    }

    public function test_role_three_is_recorded_as_resolver_for_bulk_completion(): void
    {
        $this->createBulkStatusTables();
        DB::table('tickets')->insert([
            'id' => 1,
            'status_id' => 3,
            'rating' => null,
            'resolved_by' => null,
            'terselesaikan_pada' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = new User;
        $user->id = 34;
        $user->role_id = 3;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/bulk-status', [
                'ids' => [1],
                'status_id' => 4,
            ])
            ->assertOk();

        $this->assertSame(34, (int) DB::table('tickets')->value('resolved_by'));
    }

    public function test_bulk_reopening_resets_finish_time_and_rating(): void
    {
        $this->createBulkStatusTables();
        DB::table('tickets')->insert([
            'id' => 1,
            'status_id' => 4,
            'rating' => 5,
            'terselesaikan_pada' => '2026-09-26 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = new User;
        $user->role_id = 1;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/bulk-action', [
                'ids' => [1],
                'action' => 'change_status',
                'value' => 2,
            ])
            ->assertOk();

        $ticket = DB::table('tickets')->find(1);
        $this->assertNull($ticket->terselesaikan_pada);
        $this->assertNull($ticket->rating);
    }

    private function createBulkStatusTables(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('statuses');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode')->primary();
            $table->string('nama');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id');
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('status_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('terselesaikan_pada')->nullable();
            $table->timestamps();
        });

        DB::table('statuses')->insert([['id' => 2], ['id' => 4]]);
        DB::table('departments')->insert(['kode' => 'D1', 'nama' => 'Unit 1']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Umum', 'department_id' => 'D1']);
    }

    private function createUserTicketTables(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('statuses');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('department_id')->nullable();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode', 20)->primary();
            $table->string('nama');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id', 20);
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_tiket');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('prioritas')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('lampiran')->nullable();
            $table->timestamp('terselesaikan_pada')->nullable();
            $table->timestamps();
        });

        DB::table('statuses')->insert(['id' => 1]);
        DB::table('users')->insert(['id' => 7, 'role_id' => 4]);
        DB::table('departments')->insert([
            ['kode' => 'D1', 'nama' => 'Unit 1'],
            ['kode' => 'D2', 'nama' => 'Unit 2'],
        ]);
        DB::table('categories')->insert([
            ['id' => 11, 'name' => 'Hardware', 'department_id' => 'D1'],
            ['id' => 12, 'name' => 'Akun', 'department_id' => 'D2'],
        ]);
    }

    private function createTicketListTables(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('ticket_handling_settings');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode')->primary();
            $table->string('nama');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_tiket');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('prioritas')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->timestamp('terselesaikan_pada')->nullable();
            $table->timestamps();
        });
        Schema::create('ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
        Schema::create('ticket_handling_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('max_hours')->default(24);
            $table->string('alert_mode')->default('automatic');
            $table->timestamps();
        });

        DB::table('departments')->insert(['kode' => 'D1', 'nama' => 'Unit 1']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Umum', 'department_id' => 'D1']);
        DB::table('statuses')->insert(array_map(
            fn (int $id): array => ['id' => $id],
            [1, 2, 3, 4, 5]
        ));
    }

    private function ticketRow(int $id, int $statusId, string $createdAt): array
    {
        return [
            'id' => $id,
            'nomor_tiket' => 'TK-'.$id,
            'user_id' => null,
            'category_id' => 1,
            'judul' => 'Uji SLA '.$id,
            'deskripsi' => 'Test ticket',
            'prioritas' => 'medium',
            'status_id' => $statusId,
            'terselesaikan_pada' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
