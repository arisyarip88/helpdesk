<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoService
{
    /**
     * Mendapatkan URL login SSO UNPAM untuk redirect browser.
     */
    public function getLoginUrl(?string $domain = null): string
    {
        $baseUrl = config('services.sso.base_url', 'https://devsso.unpam.ac.id');
        $configuredDomain = $domain ?: config('services.sso.domain', 'devhelpdesk.unpam.ac.id');

        // Pastikan skema (http/https) dibuang sesuai panduan SSO
        $cleanDomain = preg_replace('#^https?://#', '', rtrim($configuredDomain, '/'));

        return rtrim($baseUrl, '/').'/login?domain='.urlencode($cleanDomain);
    }

    /**
     * Memverifikasi JWT SSO server-to-server ke endpoint GET /api/b2b/token-verify.
     *
     * @return array{success: bool, status: int, data?: array, message?: string}
     */
    public function verifyToken(string $token): array
    {
        $baseUrl = config('services.sso.base_url', 'https://devsso.unpam.ac.id');
        $verifyUrl = rtrim($baseUrl, '/').'/api/b2b/token-verify';

        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->timeout(15)->get($verifyUrl, [
                'token' => $token,
            ]);

            if ($response->status() === 200) {
                return [
                    'success' => true,
                    'status' => 200,
                    'data' => $response->json(),
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'status' => 401,
                    'message' => 'Token SSO tidak valid, kedaluwarsa, atau profil tidak ditemukan.',
                ];
            }

            Log::warning('SSO verification failed with non-200 status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Layanan verifikasi SSO mengembalikan status '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Exception during SSO token verification: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 502,
                'message' => 'Gagal menghubungi server SSO: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Menyimpan atau memperbarui user dan unit/departemen ke database lokal dari payload SSO.
     * Field yang disimpan: username, password, nama, tlp, email, kode unit (department_id).
     */
    public function syncUserFromSso(array $payload, ?string $rawPassword = null): User
    {
        $source = strtoupper((string) ($payload['source'] ?? ''));
        $username = (string) ($payload['username'] ?? data_get($payload, 'mahasiswa.Nim') ?? '');

        if (empty($username)) {
            throw new \InvalidArgumentException('Username tidak ditemukan dalam respons SSO.');
        }

        $name = null;
        $email = null;
        $tlp = null;
        $kodeUnit = null;
        $namaUnit = null;

        if ($source === 'HRMS' || (! empty($payload['hrms']) && empty($payload['mahasiswa']))) {
            $hrms = $payload['hrms'] ?? [];
            $name = (string) ($hrms['nama'] ?? $username);
            $email = $hrms['email'] ?? null;
            $tlp = $hrms['no_hp'] ?? null;

            // Cari penugasan homebase terlebih dahulu, jika tidak ada gunakan penugasan pertama
            $penugasanList = $hrms['penugasan'] ?? [];
            $primaryPenugasan = collect($penugasanList)->firstWhere('homebase', true) ?? ($penugasanList[0] ?? null);

            if ($primaryPenugasan) {
                $kodeUnit = data_get($primaryPenugasan, 'kd_sub_lembaga') ?: data_get($primaryPenugasan, 'kd_lembaga');
                $namaUnit = data_get($primaryPenugasan, 'nama_sub_lembaga') ?: data_get($primaryPenugasan, 'nama_lembaga') ?: 'Unit Pegawai';
            }
        } elseif ($source === 'MAHASISWA' || (! empty($payload['mahasiswa']) && empty($payload['hrms']))) {
            $mhs = $payload['mahasiswa'] ?? [];
            $name = (string) ($mhs['Nama'] ?? $username);
            $email = $mhs['Email'] ?? null;
            $tlp = $mhs['Nomor_handphone'] ?? $mhs['Telepon'] ?? $mhs['Nomor_telepon'] ?? null;
            $kodeUnit = $mhs['Kode_program_studi'] ?? null;
            $namaUnit = $mhs['Program_studi'] ?? 'Mahasiswa';
        } else {
            // Fallback generik jika source tidak spesifik
            $name = data_get($payload, 'name') ?: data_get($payload, 'nama') ?: $username;
            $email = data_get($payload, 'email');
            $tlp = data_get($payload, 'no_hp') ?: data_get($payload, 'tlp');
            $kodeUnit = data_get($payload, 'kode_unit') ?: data_get($payload, 'department_id');
            $namaUnit = data_get($payload, 'nama_unit');
        }

        // 1. Simpan Kode Unit / Department jika ada ke tabel departments agar relasi valid
        if (! empty($kodeUnit)) {
            $kodeUnit = substr((string) $kodeUnit, 0, 20);
            $namaUnit = substr((string) ($namaUnit ?: $kodeUnit), 0, 255);

            Department::firstOrCreate(
                ['kode' => $kodeUnit],
                ['nama' => $namaUnit]
            );
        } else {
            $kodeUnit = null;
        }

        // 2. Normalisasi field string
        $name = substr((string) ($name ?: $username), 0, 255);
        $tlp = $tlp ? substr((string) $tlp, 0, 20) : null;

        // 3. Cari user yang sudah ada di DB lokal
        $user = User::where('username', $username)->first();

        // 4. Pastikan email tidak kosong (karena users.email NOT NULL)
        if (empty($email)) {
            $email = $user ? $user->email : "{$username}@unpam.ac.id";
        }
        $email = substr((string) $email, 0, 255);

        // Jika email yang didapat bertabrakan dengan user lain di DB
        $conflictUser = User::where('email', $email)->where('username', '!=', $username)->first();
        if ($conflictUser) {
            $email = "{$username}.sso@unpam.ac.id";
        }

        // 5. Update atau Create User lokal (username, password, nama, tlp, email, kode unit)
        if ($user) {
            $updateData = [
                'name' => $name,
                'email' => $email,
                'tlp' => $tlp ?? $user->tlp,
            ];

            if ($kodeUnit) {
                $updateData['department_id'] = $kodeUnit;
            }

            if ($rawPassword) {
                $updateData['password'] = Hash::make($rawPassword);
            }

            $user->update($updateData);
        } else {
            $user = User::create([
                'username' => substr($username, 0, 50),
                'name' => $name,
                'email' => $email,
                'tlp' => $tlp,
                'department_id' => $kodeUnit,
                'role_id' => 4, // Role 4: User Pengguna / Client (Dosen/Mahasiswa/Pegawai)
                'password' => Hash::make($rawPassword ?: Str::random(32)),
            ]);
        }

        return $user;
    }

    /**
     * Alur login Sandbox SSO UNPAM (sesuai Bab 8 Panduan Integrasi).
     *
     * @return array{success: bool, status: int, data?: array, user?: User, jwt?: string, message?: string}
     */
    public function loginSandbox(string $username, string $sandboxPassword, ?string $domain = null): array
    {
        $baseUrl = config('services.sso.base_url', 'https://devsso.unpam.ac.id');
        $configuredDomain = $domain ?: config('services.sso.domain', 'devhelpdesk.unpam.ac.id');
        $cleanDomain = preg_replace('#^https?://#', '', rtrim($configuredDomain, '/'));

        try {
            // 1. POST /api/sandbox/login
            $loginRes = Http::withHeaders(['Accept' => 'application/json'])
                ->post(rtrim($baseUrl, '/').'/api/sandbox/login', [
                    'username' => $username,
                    'password' => $sandboxPassword,
                    'domain' => $cleanDomain,
                ]);

            if (! $loginRes->successful()) {
                return [
                    'success' => false,
                    'status' => $loginRes->status(),
                    'message' => 'Gagal autentikasi sandbox SSO: '.($loginRes->json('message') ?? 'Kredensial sandbox tidak valid'),
                ];
            }

            $confirmationToken = $loginRes->json('confirmation_token');
            if (empty($confirmationToken)) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Token konfirmasi sandbox tidak diterima.',
                ];
            }

            // 2. POST /api/sandbox/confirm (SSO akan mengembalikan HTTP 302 ke callback dengan parameter token)
            $confirmRes = Http::withoutRedirecting()
                ->withHeaders(['Accept' => 'application/json'])
                ->post(rtrim($baseUrl, '/').'/api/sandbox/confirm', [
                    'domain' => $cleanDomain,
                    'confirmation_token' => $confirmationToken,
                ]);

            $location = $confirmRes->header('Location');
            $jwt = null;

            if ($location) {
                $query = parse_url($location, PHP_URL_QUERY);
                parse_str((string) $query, $params);
                $jwt = $params['token'] ?? null;
            }

            if (! $jwt) {
                $jwt = $confirmRes->json('token') ?? $confirmRes->json('jwt');
            }

            if (! $jwt) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Gagal mendapatkan JWT token dari konfirmasi sandbox.',
                ];
            }

            // 3. Verifikasi JWT melalui endpoint /api/b2b/token-verify
            $verify = $this->verifyToken($jwt);
            if (! $verify['success']) {
                return $verify;
            }

            // 4. Sinkronisasi user ke DB lokal
            $user = $this->syncUserFromSso($verify['data'], $sandboxPassword);

            return [
                'success' => true,
                'status' => 200,
                'data' => $verify['data'],
                'user' => $user,
                'jwt' => $jwt,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Terjadi kesalahan pada sandbox login: '.$e->getMessage(),
            ];
        }
    }
}
