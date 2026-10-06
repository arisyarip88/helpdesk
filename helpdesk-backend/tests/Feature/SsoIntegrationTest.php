<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Services\SsoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SsoIntegrationTest extends TestCase
{
    protected SsoService $ssoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
        $this->ssoService = app(SsoService::class);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('roles');

        Schema::create('departments', function (Blueprint $table): void {
            $table->string('kode', 20)->primary();
            $table->string('nama');
            $table->string('ketua')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 50)->unique();
            $table->foreignId('role_id')->nullable();
            $table->string('department_id', 20)->nullable();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('tlp', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_sso_login_url_generation(): void
    {
        config([
            'services.sso.base_url' => 'https://devsso.unpam.ac.id',
            'services.sso.domain' => 'devhelpdesk.unpam.ac.id',
        ]);

        $url = $this->ssoService->getLoginUrl();
        $this->assertEquals('https://devsso.unpam.ac.id/login?domain=devhelpdesk.unpam.ac.id', $url);
    }

    public function test_sync_user_from_sso_hrms_payload(): void
    {
        $payload = [
            'username' => '100001',
            'nik_pegawai' => '198012340001',
            'source' => 'HRMS',
            'hrms' => [
                'id_pegawai' => '100001',
                'nama' => 'Kaprodi TI',
                'email' => 'kaproditi@unpam.ac.id',
                'no_hp' => '081234567890',
                'status_aktif' => 1,
                'penugasan' => [
                    [
                        'id_institusi' => 1,
                        'nama_institusi' => 'Universitas Pamulang',
                        'nik_pegawai' => '198012340001',
                        'nama_pekerjaan' => 'DOSEN',
                        'homebase' => true,
                        'jabatan' => 'Ketua',
                        'tipe_lembaga' => 'AKADEMIK',
                        'kd_lembaga' => 'FAK',
                        'nama_lembaga' => 'Fakultas Ilmu Komputer',
                        'kd_sub_lembaga' => '55201',
                        'nama_sub_lembaga' => 'Teknik Informatika',
                        'status_aktif' => 1,
                    ],
                ],
            ],
            'mahasiswa' => null,
        ];

        $user = $this->ssoService->syncUserFromSso($payload);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'username' => '100001',
            'name' => 'Kaprodi TI',
            'email' => 'kaproditi@unpam.ac.id',
            'tlp' => '081234567890',
            'department_id' => '55201',
            'role_id' => 4,
        ]);

        // Cek bahwa department juga tersimpan di tabel departments
        $this->assertDatabaseHas('departments', [
            'kode' => '55201',
            'nama' => 'Teknik Informatika',
        ]);

        // Password tersimpan
        $this->assertNotEmpty($user->password);
    }

    public function test_sync_user_from_sso_mahasiswa_payload(): void
    {
        $payload = [
            'username' => '100000001',
            'nik_pegawai' => null,
            'source' => 'MAHASISWA',
            'hrms' => null,
            'mahasiswa' => [
                'Nim' => '100000001',
                'Nama' => 'Mahasiswa Contoh',
                'Kode_program_studi' => '55201',
                'Program_studi' => 'Teknik Informatika',
                'Email' => 'mhs100000001@student.unpam.ac.id',
                'Nomor_handphone' => '089876543210',
            ],
        ];

        $user = $this->ssoService->syncUserFromSso($payload);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'username' => '100000001',
            'name' => 'Mahasiswa Contoh',
            'email' => 'mhs100000001@student.unpam.ac.id',
            'tlp' => '089876543210',
            'department_id' => '55201',
            'role_id' => 4,
        ]);
    }

    public function test_api_callback_endpoint_verifies_token_and_redirects(): void
    {
        Http::fake([
            'https://devsso.unpam.ac.id/api/b2b/token-verify*' => Http::response([
                'username' => '100002',
                'nik_pegawai' => '198501012020',
                'source' => 'HRMS',
                'hrms' => [
                    'id_pegawai' => '100002',
                    'nama' => 'Pegawai Tendik',
                    'email' => 'tendik@unpam.ac.id',
                    'no_hp' => '081122334455',
                    'penugasan' => [
                        [
                            'kd_sub_lembaga' => 'BAAK',
                            'nama_sub_lembaga' => 'Biro Administrasi Akademik',
                            'homebase' => true,
                        ],
                    ],
                ],
                'mahasiswa' => null,
            ], 200),
        ]);

        $response = $this->get('/api/loginsso?token=dummy-jwt-token');

        $response->assertStatus(302);
        $this->assertStringContainsString('/auth/sso-callback?token=', $response->headers->get('Location'));

        $this->assertDatabaseHas('users', [
            'username' => '100002',
            'name' => 'Pegawai Tendik',
            'email' => 'tendik@unpam.ac.id',
            'tlp' => '081122334455',
            'department_id' => 'BAAK',
        ]);
    }

    public function test_api_post_loginsso_with_jwt_token(): void
    {
        Http::fake([
            'https://devsso.unpam.ac.id/api/b2b/token-verify*' => Http::response([
                'username' => '100001',
                'nik_pegawai' => '198012340001',
                'source' => 'HRMS',
                'hrms' => [
                    'id_pegawai' => '100001',
                    'nama' => 'Kaprodi TI',
                    'email' => 'kaproditi@unpam.ac.id',
                    'no_hp' => '081234567890',
                    'penugasan' => [
                        [
                            'kd_sub_lembaga' => '55201',
                            'nama_sub_lembaga' => 'Teknik Informatika',
                            'homebase' => true,
                        ],
                    ],
                ],
                'mahasiswa' => null,
            ], 200),
        ]);

        $response = $this->postJson('/api/loginsso', [
            'token' => 'valid-jwt-token',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'username',
                    'name',
                    'email',
                    'department_id',
                ],
                'access_token',
                'token_type',
            ]);
    }
}
