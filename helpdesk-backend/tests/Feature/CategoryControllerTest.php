<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    public function test_role_one_can_create_update_list_and_delete_categories(): void
    {
        $this->createCategoryTables();
        DB::table('departments')->insert(['kode' => 'D1', 'nama' => 'Unit 1']);
        $user = new User;
        $user->role_id = 1;

        $createResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/categories', ['name' => 'IT Support', 'department_id' => 'D1'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'IT Support')
            ->assertJsonPath('data.department.nama', 'Unit 1');

        $categoryId = $createResponse->json('data.id');
        $this->putJson('/api/categories/'.$categoryId, ['name' => 'IT Services', 'department_id' => 'D1'])
            ->assertOk()
            ->assertJsonPath('data.name', 'IT Services');

        $this->getJson('/api/categories?search=Services')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'IT Services');

        $this->deleteJson('/api/categories/'.$categoryId)->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_rejects_category_for_a_department_that_does_not_exist(): void
    {
        $this->createCategoryTables();
        $user = new User;
        $user->role_id = 1;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/categories', ['name' => 'IT Support', 'department_id' => 'NOPE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('department_id');
    }

    public function test_role_three_can_manage_categories_only_in_its_department(): void
    {
        $this->createCategoryTables();
        DB::table('departments')->insert([
            ['kode' => 'D1', 'nama' => 'Unit 1', 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'D2', 'nama' => 'Unit 2', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('categories')->insert([
            ['id' => 1, 'name' => 'Kategori Unit 1', 'department_id' => 'D1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Kategori Unit 2', 'department_id' => 'D2', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $staff = new User;
        $staff->id = 33;
        $staff->role_id = 3;
        $staff->department_id = 'D1';

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.department_id', 'D1');

        $createResponse = $this->postJson('/api/categories', ['name' => 'Kategori Baru'])
            ->assertCreated()
            ->assertJsonPath('data.department_id', 'D1');
        $createdCategoryId = $createResponse->json('data.id');

        $this->postJson('/api/categories', ['name' => 'Kategori Unit Lain', 'department_id' => 'D2'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('department_id');

        $this->putJson('/api/categories/1', ['name' => 'Nama Diperbarui'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Diperbarui')
            ->assertJsonPath('data.department_id', 'D1');

        $this->putJson('/api/categories/1', ['name' => 'Pindah Unit', 'department_id' => 'D2'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('department_id');

        $this->getJson('/api/categories/2')->assertForbidden();
        $this->putJson('/api/categories/2', ['name' => 'Tidak Boleh'])->assertForbidden();
        $this->deleteJson('/api/categories/2')->assertForbidden();

        $this->deleteJson('/api/categories/'.$createdCategoryId)->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $createdCategoryId]);
        $this->assertDatabaseHas('categories', ['id' => 2, 'department_id' => 'D2']);
    }

    private function createCategoryTables(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');

        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode', 20)->primary();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('department_id', 20);
            $table->timestamps();
        });
    }
}
