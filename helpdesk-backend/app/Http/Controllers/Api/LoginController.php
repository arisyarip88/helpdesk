<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class B2bApiController extends Controller
{
    /**
     * Memanggil API Eksternal B2B menggunakan Header api-key
     */
    public function loginB2b(Request $request)
    {
        // 1. Validasi Input
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

        // 2. Ambil URL dan API Key dari config
        $apiUrl = config('services.b2b_primary.url', 'http://sdsadsa.com');
        $apiKey = config('services.b2b_primary.api_key', 'wee23e23e23e23');

        // 3. Kirim Request ke API B2B sesuai spesifikasi Swagger (Header: api-key)
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'api-key' => $apiKey,              // Sesuai dengan Name: api-key pada gambar
                    'Accept'  => 'application/json',
                ])
                ->post($apiUrl . '/login', [          // Sesuaikan endpoint path jika ada (misal: /login atau /auth/login)
                    'username' => $request->username,
                    'password' => $request->password,
                ]);

            // 4. Jika Request Berhasil (Status 2xx)
            if ($response->successful()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Akses API B2B Berhasil',
                    'data'    => $response->json()
                ], 200);
            }

            // 5. Jika API merespons dengan status error (401, 403, 500, dll)
            return response()->json([
                'status'        => 'error',
                'message'       => 'Otorisasi API B2B gagal atau kredensial salah',
                'error_details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            // 6. Penanganan jika server API B2B tidak dapat dijangkau / timeout
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke server API B2B: ' . $e->getMessage()
            ], 500);
        }
    }
}