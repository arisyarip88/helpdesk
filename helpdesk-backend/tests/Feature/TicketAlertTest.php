<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketAlertTest extends TestCase
{
    public function test_returns_401_when_settings_are_requested_without_authentication(): void
    {
        $this->getJson('/api/ticket-handling-settings')->assertUnauthorized();
    }

    public function test_returns_403_when_role_three_updates_handling_settings(): void
    {
        $user = new User;
        $user->role_id = 3;

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/ticket-handling-settings', ['max_hours' => 48])
            ->assertForbidden();
    }

    public function test_rejects_a_handling_time_below_one_hour(): void
    {
        $user = new User;
        $user->role_id = 1;

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/ticket-handling-settings', ['max_hours' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_hours');
    }

    public function test_role_three_receives_automatic_alerts_for_overdue_tickets(): void
    {
        $this->createAlertTables();
        $this->travelTo('2026-09-27 10:00:00');
        DB::table('ticket_handling_settings')->insert([
            'id' => 1,
            'max_hours' => 30,
            'alert_mode' => 'automatic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertOpenTicket('D1', '2026-09-26 03:59:00');
        $user = new User;
        $user->role_id = 3;
        $user->department_id = 'D1';

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/ticket-warnings')
            ->assertOk()
            ->assertJsonPath('alert_mode', 'automatic')
            ->assertJsonPath('data.0.ticket_number', 'TK-1')
            ->assertJsonPath('data.0.is_overdue', true);
    }

    public function test_manual_mode_only_shows_admin_sent_warnings_to_role_three(): void
    {
        $this->createAlertTables();
        DB::table('ticket_handling_settings')->insert([
            'id' => 1,
            'max_hours' => 30,
            'alert_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertOpenTicket('D1', '2026-09-25 00:00:00');

        $admin = new User;
        $admin->role_id = 1;
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tickets/1/warnings')
            ->assertCreated();

        $departmentUser = new User;
        $departmentUser->role_id = 3;
        $departmentUser->department_id = 'D1';

        $this->actingAs($departmentUser, 'sanctum')
            ->getJson('/api/ticket-warnings')
            ->assertOk()
            ->assertJsonPath('alert_mode', 'manual')
            ->assertJsonPath('data.0.message', 'Tiket ini belum diselesaikan. Mohon segera ditindaklanjuti.')
            ->assertJsonPath('data.0.is_overdue', false);
    }

    public function test_manual_warning_is_rejected_and_hidden_before_the_sla_deadline(): void
    {
        $this->createAlertTables();
        $this->travelTo('2026-09-28 10:00:00');
        DB::table('ticket_handling_settings')->insert([
            'id' => 1,
            'max_hours' => 30,
            'alert_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertOpenTicket('D1', '2026-09-27 12:00:00');
        $admin = new User;
        $admin->role_id = 1;

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tickets/1/warnings')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan hanya dapat dikirim setelah tiket melewati batas waktu penanganan.');

        $departmentUser = new User;
        $departmentUser->role_id = 3;
        $departmentUser->department_id = 'D1';
        $this->actingAs($departmentUser, 'sanctum')
            ->getJson('/api/ticket-warnings')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_send_bulk_warnings_in_manual_mode(): void
    {
        $this->createAlertTables();
        DB::table('ticket_handling_settings')->insert([
            'id' => 1,
            'max_hours' => 30,
            'alert_mode' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->travelTo('2026-09-28 10:00:00');
        $this->insertOpenTicket('D1', '2026-09-27 03:59:00');
        $admin = new User;
        $admin->role_id = 1;

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tickets/bulk-action', [
                'ids' => [1],
                'action' => 'send_warning',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Peringatan dikirim untuk 1 tiket yang belum selesai.');

        $this->assertSame(
            'Tiket ini belum diselesaikan. Mohon segera ditindaklanjuti.',
            DB::table('ticket_warnings')->value('message')
        );
    }

    private function createAlertTables(): void
    {
        Schema::dropIfExists('ticket_warnings');
        Schema::dropIfExists('ticket_handling_settings');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('statuses');

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('department_id', 20);
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->string('nomor_tiket', 30);
            $table->string('judul');
            $table->timestamp('terselesaikan_pada')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
        Schema::create('ticket_handling_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('max_hours')->default(24);
            $table->string('alert_mode', 20)->default('automatic');
            $table->timestamps();
        });
        Schema::create('ticket_warnings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        DB::table('categories')->insert([
            'id' => 1,
            'name' => 'Kategori Tes',
            'department_id' => 'D1',
        ]);
    }

    private function insertOpenTicket(string $departmentId, string $createdAt): void
    {
        DB::table('tickets')->insert([
            'id' => 1,
            'category_id' => 1,
            'status_id' => 2,
            'nomor_tiket' => 'TK-1',
            'judul' => 'Tiket pengujian',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
