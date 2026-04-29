<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    const SSO_TTL_SECONDS = 60;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'codigo' => 'required|string',
            'mail'   => 'required|email',
        ]);

        $codigo        = trim($request->input('codigo'));
        $mail          = strtolower(trim($request->input('mail')));
        $institutionId = (int) $request->header('X-Institution-ID');
        $token         = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Se requiere Authorization Bearer token.',
                'errors'  => null,
            ], 401);
        }

        $alumno = DB::table('alumnos')
            ->where('Aleatorio', $codigo)
            ->first();

        if (!$alumno) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Credenciales inválidas.',
                'errors'  => null,
            ], 401);
        }

        $mailAlumno   = strtolower(trim($alumno->Mail_Reponsable ?? ''));
        $mailCoincide = $mailAlumno !== '' && $mailAlumno === $mail;

        if (!$mailCoincide) {
            $familia = DB::table('familias_asociadas')
                ->where('ID_Alumno', $alumno->ID)
                ->whereRaw('LOWER(TRIM(Mail_Reponsable)) = ?', [$mail])
                ->first();

            $mailCoincide = !is_null($familia);
        }

        if (!$mailCoincide) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Credenciales inválidas.',
                'errors'  => null,
            ], 401);
        }

        $ssoCode = Str::random(48);

        Cache::put('sso_' . $ssoCode, [
            'student_id'     => $alumno->ID,
            'mail'           => $request->input('mail'),
            'institution_id' => $institutionId,
            'token'          => $token,
        ], self::SSO_TTL_SECONDS);

        $redirectUrl = env('SSO_REDIRECT_URL', 'https://geoacademico.com.ar/geoAcademico2/gestion/aulas/') . '?sso_code=' . $ssoCode;

        return response()->json([
            'success' => true,
            'data'    => [
                'sso_code'     => $ssoCode,
                'redirect_url' => $redirectUrl,
                'expires_in'   => self::SSO_TTL_SECONDS,
            ],
            'message' => 'Login exitoso. Usá redirect_url para abrir el WebView.',
            'errors'  => null,
        ], 200);
    }

    public function sso(string $code): JsonResponse
    {
        // Cache::pull() lee y borra en un solo paso (uso único garantizado)
        $data = Cache::pull('sso_' . $code);

        if (!$data) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Código inválido o expirado.',
                'errors'  => null,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Autenticación SSO exitosa.',
            'errors'  => null,
        ], 200);
    }
}
