<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class B2bClientController extends Controller
{
    public function getData(): JsonResponse
    {
        // Langsung mengambil nilai dari file .env
        $baseUrl  ='http://devhrms.sasmitagroup.org';
        $apiKey   = 'KgbOkpB0Li1ZMrWYG81uDuzKbSCT4pg1fLcSqVtE7xGIH9FhALuW4kPtD5OxtumptW9b4wHIJ3Rv50MjLuUvmbQziVxAk6wY';
         //$apiKey   = '3ScAWtoIWsm5BjE7gtrkcGlIlQWX5zU2J9KjgQzb6v8rv2JEr7P4kHDHbtfzdSFVjR3Bia6y66afMjxFEI16Iiq8dT8PfzEG';
        $username = '00671';
        $password = 'devByPass';

        //    $username ='01809';
        // $password ='sofyan1234';

        // Mengirim HTTP POST Request ke API B2B
        $response = Http::withHeaders([
            'api-key'    => $apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/api/v2/login-penugasan", [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            echo $response;
            exit();
            return response()->json([
                'status' => 'success',
                'data'   => $response->json(),
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Gagal terhubung ke API B2B dosen',
            'details' => $response->json() ?? $response->body(),
        ], $response->status());
    }

     public function getMhs(): JsonResponse
    {
        // Langsung mengambil nilai dari file .env
        $baseUrl  ='https://satu.unpam.ac.id';
        $apiKey   = 'NHIzbjRCbDAwRA==';
         //$apiKey   = '3ScAWtoIWsm5BjE7gtrkcGlIlQWX5zU2J9KjgQzb6v8rv2JEr7P4kHDHbtfzdSFVjR3Bia6y66afMjxFEI16Iiq8dT8PfzEG';
        $username = '231015200163';
        $password = '231015200163unpam#18011992';

        //    $username ='01809';
        // $password ='sofyan1234';

        // Mengirim HTTP POST Request ke API B2B
        $response = Http::withHeaders([
            'api-key'    => $apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/api/b2b/login", [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            echo $response;
            exit();
            return response()->json([
                'status' => 'success',
                'data'   => $response->json(),
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Gagal terhubung ke API B2B mahasiswa',
            'details' => $response->json() ?? $response->body(),
        ], $response->status());
    }
}