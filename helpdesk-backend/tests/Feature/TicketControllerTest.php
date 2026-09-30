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

    public function test_chat_notifications_return_latest_messages_within_role_three_department(): void
    {
        $this->createTicketListTables();
        DB::table('departments')->insert(['kode' => 'D2', 'nama' => 'Unit 2']);
        DB::table('categories')->insert(['id' => 2, 'name' => 'Lainnya', 'department_id' => 'D2']);
        DB::table('users')->insert([
            ['id' => 7, 'name' => 'Pelapor', 'role_id' => 4, 'department_id' => null],
            ['id' => 33, 'name' => 'Petugas', 'role_id' => 3, 'department_id' => 'D1'],
        ]);
        DB::table('tickets')->insert([
            $this->ticketRow(1, 1, '2026-09-26 03:59:00'),
            array_merge($this->ticketRow(2, 1, '2026-09-26 03:59:00'), ['category_id' => 2]),
        ]);
        DB::table('ticket_messages')->insert([
            [
                'id' => 1,
                'ticket_id' => 1,
                'user_id' => 7,
                'message' => 'Pesan lama',
                'created_at' => '2026-09-26 04:00:00',
                'updated_at' => '2026-09-26 04:00:00',
            ],
            [
                'id' => 2,
                'ticket_id' => 1,
                'user_id' => 7,
                'message' => 'Pesan terbaru',
                'created_at' => '2026-09-26 04:01:00',
                'updated_at' => '2026-09-26 04:01:00',
            ],
            [
                'id' => 3,
                'ticket_id' => 2,
                'user_id' => 7,
                'message' => 'Pesan unit lain',
                'created_at' => '2026-09-26 04:02:00',
                'updated_at' => '2026-09-26 04:02:00',
            ],
        ]);

        $staff = new User;
        $staff->id = 33;
        $staff->role_id = 3;
        $staff->department_id = 'D1';

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/tickets/chat-notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('data.0.message', 'Pesan terbaru')
            ->assertJsonPath('data.0.user.role_id', 4)
            ->assertJsonPath('data.0.ticket.id', 1);

        $this->getJson('/api/tickets?new_messages=1&new_message_ticket_ids=1&per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', 1);
    }

    public function test_role_three_ticket_stats_include_new_tickets_from_its_department_only(): void
    {
        $this->createTicketListTables();
        DB::table('categories')->insert(['id' => 2, 'name' => 'Unit lain', 'department_id' => 'D2']);
        DB::table('tickets')->insert([
            $this->ticketRow(1, 1, '2026-09-26 03:59:00'),
            array_merge($this->ticketRow(2, 1, '2026-09-26 03:59:00'), ['category_id' => 2]),
        ]);
        $staff = new User;
        $staff->id = 33;
        $staff->role_id = 3;
        $staff->department_id = 'D1';

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/tickets/stats')
            ->assertOk()
            ->assertJsonPath('data.open', 1)
            ->assertJsonPath('data.total', 1);
    }

    public function test_role_four_can_only_delete_the_latest_message_on_their_ticket(): void
    {
        $this->createTicketListTables();
        DB::table('tickets')->insert(
            array_merge($this->ticketRow(1, 1, '2026-09-26 03:59:00'), ['user_id' => 7])
        );
        DB::table('ticket_messages')->insert([
            [
                'id' => 1,
                'ticket_id' => 1,
                'user_id' => 7,
                'message' => 'Pesan lama',
                'created_at' => '2026-09-26 04:00:00',
                'updated_at' => '2026-09-26 04:00:00',
            ],
            [
                'id' => 2,
                'ticket_id' => 1,
                'user_id' => 7,
                'message' => 'Pesan terakhir',
                'created_at' => '2026-09-26 04:01:00',
                'updated_at' => '2026-09-26 04:01:00',
            ],
        ]);
        $user = new User;
        $user->id = 7;
        $user->role_id = 4;

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tickets/1/messages/1')
            ->assertForbidden();

        $this->deleteJson('/api/tickets/1/messages')
            ->assertForbidden();

        $this->deleteJson('/api/tickets/1/messages/2')
            ->assertOk();

        $this->assertDatabaseHas('ticket_messages', ['id' => 1]);
        $this->assertDatabaseMissing('ticket_messages', ['id' => 2]);
    }

    public function test_ticket_status_notifications_are_read_independently_by_each_recipient(): void
    {
        $this->createUserTicketTables();
        DB::table('statuses')->insert(['id' => 2, 'name' => 'In Progress']);
        DB::table('users')->insert([
            ['id' => 11, 'name' => 'Admin pengubah', 'role_id' => 1, 'department_id' => null],
            ['id' => 12, 'name' => 'Admin lain', 'role_id' => 2, 'department_id' => null],
            ['id' => 33, 'name' => 'Petugas unit', 'role_id' => 3, 'department_id' => 'D1'],
            ['id' => 34, 'name' => 'Petugas lain', 'role_id' => 3, 'department_id' => 'D2'],
        ]);
        DB::table('tickets')->insert([
            'id' => 1,
            'nomor_tiket' => 'TK-1',
            'user_id' => 7,
            'category_id' => 11,
            'judul' => 'Uji notifikasi',
            'deskripsi' => 'Deskripsi',
            'prioritas' => 'medium',
            'status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin = new User;
        $admin->id = 11;
        $admin->role_id = 1;

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/tickets/1', ['status_id' => 2])
            ->assertOk();

        $this->assertDatabaseHas('ticket_status_notifications', ['recipient_id' => 12, 'ticket_id' => 1]);
        $this->assertDatabaseHas('ticket_status_notifications', ['recipient_id' => 33, 'ticket_id' => 1]);
        $this->assertDatabaseHas('ticket_status_notifications', ['recipient_id' => 7, 'ticket_id' => 1]);
        $this->assertDatabaseMissing('ticket_status_notifications', ['recipient_id' => 34, 'ticket_id' => 1]);
        $this->assertDatabaseMissing('ticket_status_notifications', ['recipient_id' => 11, 'ticket_id' => 1]);

        $adminRecipient = new User;
        $adminRecipient->id = 12;
        $adminRecipient->role_id = 2;
        $notificationId = DB::table('ticket_status_notifications')
            ->where('recipient_id', 12)
            ->value('id');

        $this->actingAs($adminRecipient, 'sanctum')
            ->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $reporter = new User;
        $reporter->id = 7;
        $reporter->role_id = 4;
        $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/ticket-status-notifications/{$notificationId}/read")
            ->assertNotFound();

        $departmentStaff = new User;
        $departmentStaff->id = 33;
        $departmentStaff->role_id = 3;
        $departmentStaff->department_id = 'D1';
        $this->actingAs($departmentStaff, 'sanctum')
            ->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $otherDepartmentStaff = new User;
        $otherDepartmentStaff->id = 34;
        $otherDepartmentStaff->role_id = 3;
        $otherDepartmentStaff->department_id = 'D2';
        $this->actingAs($otherDepartmentStaff, 'sanctum')
            ->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($adminRecipient, 'sanctum')
            ->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson("/api/ticket-status-notifications/{$notificationId}/read")
            ->assertOk();

        $this->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($reporter, 'sanctum')
            ->getJson('/api/ticket-status-notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
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
        Schema::dropIfExists('ticket_status_notifications');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('statuses');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('department_id')->nullable();
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
        $this->createStatusNotificationTable();
    }

    private function createUserTicketTables(): void
    {
        Schema::dropIfExists('ticket_status_notifications');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('statuses');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
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
        $this->createStatusNotificationTable();
    }

    private function createTicketListTables(): void
    {
        Schema::dropIfExists('ticket_status_notifications');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('ticket_handling_settings');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
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
            $table->string('name')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('department_id')->nullable();
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
        $this->createStatusNotificationTable();
    }

    private function createStatusNotificationTable(): void
    {
        Schema::create('ticket_status_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('previous_status_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
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
