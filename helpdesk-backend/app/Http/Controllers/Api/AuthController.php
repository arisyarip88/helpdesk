<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;






class AuthController extends Controller
{
    /**
     * Login langsung menggunakan Database Lokal.
     */
    public function login(Request $request)
    {
        // 1. Validasi Input Username & Password
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Cari User Langsung di Database Lokal
        $user = User::where('username', $request->username)->first();

        // 3. Cek Keberadaan User & Match Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username atau password salah pada database lokal'
            ], 401);
        }

        // 4. Load Relasi Role & Department
        $user->load(['role', 'department']);

        // 5. Generate Bearer Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Response Success
        return response()->json([
            'status'       => 'success',
            'message'      => 'Login database lokal berhasil',
            'data'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ], 200);
    }

    /**
     * Logout & Revoke Token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil'
        ], 200);
    }

    /**
     * Get Current Authenticated User Data
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()->load(['role', 'department'])
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role');

        return response()->json([
           
            'user' => $user,
            'role' => $user->role ? $user->role->name : null, // Mengambil 'name' dari tabel roles (misal: 'admin')
        ]);
    }



    //LOGIN SSO
    public function loginSso(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

        // Step 1: Cek API 1
        $key1=env('B2B_API_KEY_HRMS');
        $api1Data = $this->checkExternalApi(env('B2B_HRMS_URL'),$key1,$username, $password);
        if ($api1Data) {
            $user = $this->syncUser($username, $password, $api1Data, 'API_1');
            return $this->respondWithToken($user, 'Login berhasil via API 1');
        }

        // Step 2: Cek API 2 (Ubah URL/endpoint sesuai API 2 milikmu)
        $key2=env('B2B_API_KEY_MHS');
        $api2Data = $this->checkExternalApi(env('B2B_MHS_URL'),$key2, $username, $password);
        if ($api2Data) {
            $user = $this->syncUser($username, $password, $api2Data, 'API_2');
            return $this->respondWithToken($user, 'Login berhasil via API 2');
        }

        // Step 3: Cek Database Lokal jika API 1 & API 2 gagal
        $user = User::where('username', $username)->first();
        if ($user && Hash::check($password, $user->password)) {
            return $this->respondWithToken($user, 'Login berhasil via DB Lokal');
        }

        // Step 4: Jika semua cara gagal
        return response()->json([
            'status'  => 'error',
            'message' => 'Username atau password salah',
        ], 401);

        


    }

    /**
     * Helper untuk HTTP Request ke API External
     */
    private function checkExternalApi(string $url,string $key, string $username, string $password): ?array
    {
        try {
            $response = Http::withHeaders([
                'api-key'      => $key,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'username' => $username,
                'password' => $password,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Log error jika API down/timeout
        }

        return null;
    }

    /**
     * Update atau Create data User ke DB Lokal
     */
    // private function syncUser(string $username, string $password, array $apiResponse, string $source): User
    // {
    //     // Sesuaikan mapping field dari response API kamu
    //     $userData = $apiResponse['data'] ?? $apiResponse;
    //         // Tangkap kode dan nama sub lembaga menggunakan data_get
    //     $kdSubLembaga   = data_get($userData, 'penugasan.0.kd_sub_lembaga', 'DEFAULT');
    //     $namaSubLembaga = data_get($userData, 'penugasan.0.nama_sub_lembaga', 'General');

    //     return User::updateOrCreate(
    //         ['username' => $username],
    //         [
    //             'name'     => $userData['nama'] ?? $username,
    //             'email'    => $userData['email'] ?? "{$username}@example.com",
    //             'tlp'=>$userData['no_hp'],
    //             'password' => Hash::make($password), // Sync password lokal
    //             'role_id'=>'4',
    //             'department_id'=>$kdSubLembaga ,
    //         ]
    //     );

    //     //login dari db
        
    // }

    public function register(Request $request)
    {
        // 1. Validasi Input Registrasi
        $validator = Validator::make($request->all(), [
            'username'      => 'required|string|max:50|unique:users,username',
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'password'      => 'required', 'confirmed', // Butuh input password_confirmation
            'department_id' => 'required|string|max:20|exists:departments,kode', // Memastikan KODE jurusan/unit terdaftar
            'tlp'           => 'nullable|string|max:20',
            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pendaftaran gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Ambil ID Default untuk Role (misal: 'User' / 'Mahasiswa') dan Status (misal: 'Active')
            // Sesuaikan nama 'User' / 'Active' dengan isi data pada tabel roles & statuses Anda
       

            // 3. Simpan User Baru
            $user = User::create([
                'username'      => $request->username,
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'role_id'       => '1',
                'status_id'     => '1',
                'department_id' => $request->department_id, // Berisi kode VARCHAR(20)
                'tlp'           => $request->tlp,
            ]);

            // 4. Buat Access Token (Laravel Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            // 5. Muat Relasi untuk Respons
            $user->load(['role', 'status', 'department']);

            return response()->json([
                'success'      => true,
                'message'      => 'Registrasi berhasil',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'data'         => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan registrasi pengguna',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function syncUser(string $username, string $password, array $apiResponse, string $source): User
{
    // Sesuaikan mapping field dari response API
    $userData = $apiResponse['data'] ?? $apiResponse;

    // Cari user berdasarkan username
    $user = User::where('username', $username)->first();

    if ($user) {    
        // Jika user SUDAH ADA: hanya update password
        $user->update([
            'password' => Hash::make($password),
        ]);

        return $user;
    }

    // Tangkap kode dan nama sub lembaga menggunakan data_get untuk user BARU
    $kdSubLembaga   = data_get($userData, 'penugasan.0.kd_sub_lembaga', 'DEFAULT');
    //$namaSubLembaga = data_get($userData, 'penugasan.0.nama_sub_lembaga', 'General');
    $kdSubLembaga = $user_data['penugasan'][0]['kd_sub_lembaga'] ?? null;
    $email=$user_data['email']?? null;

    $kdSubLembaga = data_get($userData, 'penugasan.0.kd_sub_lembaga');
    // $nama = data_get($userData, 'nama');
    // $email = data_get($userData, 'email');
   
      
    // Jika user BELUM ADA: buat user baru dengan data lengkap
    return User::create([
        'username'      => $username,
        'name'          => $userData['nama'] ?? $username,
        'email'         => $userData['email']??null ,
        'tlp'           => $userData['no_hp'] ?? null,
        'password'      => Hash::make($password),
        'role_id'       => 4,
        'department_id' =>$kdSubLembaga,
    ]);
}

    /**
     * Helper Response Token (Sanctum/Passport)
     */
    private function respondWithToken(User $user, string $message): JsonResponse
    {
        // Jika menggunakan Laravel Sanctum:
        $token = $user->createToken('sso-token')->plainTextToken;
         $user->load(['role', 'department']);

        return response()->json([
             'status'       => 'success',
            'message'      => $message,
            'data'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ]);

       
    }


    
}