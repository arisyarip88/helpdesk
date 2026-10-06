<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected SsoService $ssoService
    ) {}

    /**
     * Login langsung menggunakan Database Lokal.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah pada database lokal',
            ], 401);
        }

        return $this->respondWithToken($user, 'Login database lokal berhasil');
    }

    /**
     * Logout & Revoke Token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ], 200);
    }

    /**
     * Get Current Authenticated User Data
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load(['role', 'department']),
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'user' => $user,
            'role' => $user->role ? $user->role->name : null,
        ]);
    }

    /**
     * Mendapatkan URL SSO login untuk redirect browser.
     */
    public function ssoUrl(Request $request): JsonResponse
    {
        $domain = $request->query('domain');
        $url = $this->ssoService->getLoginUrl($domain);

        return response()->json([
            'status' => 'success',
            'url' => $url,
        ]);
    }

    /**
     * Callback SSO yang dipanggil browser setelah login di satu.unpam.ac.id
     * GET /api/loginsso?token=<JWT>
     */
    public function handleSsoCallback(Request $request): JsonResponse|RedirectResponse
    {
        $token = $request->query('token');
        $frontendUrl = config('services.sso.frontend_url', 'http://localhost:3000');

        if (empty($token) || ! is_string($token)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Callback SSO tidak lengkap (parameter token tidak ditemukan)',
                ], 400);
            }

            return redirect()->away($frontendUrl.'/?error=sso_missing_token')
                ->header('Referrer-Policy', 'no-referrer');
        }

        $result = $this->ssoService->verifyToken($token);

        if (! $result['success']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Verifikasi SSO gagal',
                ], $result['status'] ?? 401);
            }

            $errorCode = ($result['status'] === 401) ? 'sso_unauthorized' : 'sso_server_error';

            return redirect()->away($frontendUrl.'/?error='.$errorCode)
                ->header('Referrer-Policy', 'no-referrer');
        }

        // Simpan data profil (username, password, nama, tlp, email, kode unit) ke DB lokal
        $user = $this->ssoService->syncUserFromSso($result['data']);
        $user->load(['role', 'department']);

        // Buat access token lokal Sanctum
        $localToken = $user->createToken('auth_token')->plainTextToken;

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login SSO berhasil',
                'access_token' => $localToken,
                'token_type' => 'Bearer',
                'data' => $user,
            ], 200);
        }

        // Arahkan ke callback frontend dengan URL bersih dari JWT SSO
        $redirectUrl = $frontendUrl.'/auth/sso-callback?token='.urlencode($localToken).'&role='.($user->role_id ?? 4);

        return redirect()->away($redirectUrl)
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Login SSO via API (bisa menerima JWT token dari frontend atau username/password sandbox/lokal).
     * POST /api/loginsso atau POST /api/loginSso
     */
    public function loginSso(Request $request): JsonResponse
    {
        // 1. Jika request mengirim 'token' JWT SSO
        if ($request->filled('token')) {
            $result = $this->ssoService->verifyToken($request->token);

            if (! $result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Verifikasi token SSO gagal',
                ], $result['status'] ?? 401);
            }

            $user = $this->ssoService->syncUserFromSso($result['data']);

            return $this->respondWithToken($user, 'Login SSO berhasil');
        }

        // 2. Jika request mengirim username & password
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter tidak lengkap',
                'errors' => $validator->errors(),
            ], 422);
        }

        $username = $request->username;
        $password = $request->password;

        // Coba login Sandbox SSO jika password sandbox / dev diberikan
        if (in_array($username, ['100001', '100002', '100000001']) || $request->boolean('sandbox')) {
            $sandboxResult = $this->ssoService->loginSandbox($username, $password);
            if ($sandboxResult['success'] && isset($sandboxResult['user'])) {
                return $this->respondWithToken($sandboxResult['user'], 'Login SSO Sandbox berhasil');
            }
        }

        // Cek database lokal jika bukan/gagal sandbox
        $user = User::where('username', $username)->first();
        if ($user && Hash::check($password, $user->password)) {
            return $this->respondWithToken($user, 'Login database lokal berhasil');
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Username atau password salah',
        ], 401);
    }

    /**
     * Registrasi pengguna baru.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
            'department_id' => 'required|string|max:20|exists:departments,kode',
            'tlp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pendaftaran gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 4,
                'department_id' => $request->department_id,
                'tlp' => $request->tlp,
            ]);

            return $this->respondWithToken($user, 'Registrasi berhasil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan registrasi pengguna',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper Response Token Sanctum
     */
    private function respondWithToken(User $user, string $message): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(['role', 'department']);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
}
