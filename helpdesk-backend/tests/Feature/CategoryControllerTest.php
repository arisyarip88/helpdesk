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
