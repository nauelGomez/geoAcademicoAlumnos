<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SetTenantConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 1. Obtenemos el ID del colegio desde el header (o parámetro get por si acaso)
        $institutionId = $request->header('X-Institution-ID') ?: $request->input('institution_id');

        if (!$institutionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Falta el header X-Institution-ID con el ID del colegio.'
            ], 400);
        }

        // 2. Buscamos las credenciales en nuestro config/institutions.php
        $credentials = config("institutions.{$institutionId}");

        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => "Institución con ID {$institutionId} no encontrada o inactiva."
            ], 404);
        }

      // 3. Inyectamos los datos en la conexión 'tenant' al vuelo
        Config::set('database.connections.tenant.driver', 'mysql'); // <--- ¡ESTA ES LA MAGIA QUE FALTABA!
        Config::set('database.connections.tenant.host', $credentials['host']);
        Config::set('database.connections.tenant.port', $credentials['port']);
        Config::set('database.connections.tenant.database', $credentials['database']);
        Config::set('database.connections.tenant.username', $credentials['username']);
        Config::set('database.connections.tenant.password', $credentials['password']);
        
        // Configuraciones estándar de MySQL en Laravel para evitar dolores de cabeza
        Config::set('database.connections.tenant.charset', 'utf8mb4');
        Config::set('database.connections.tenant.collation', 'utf8mb4_unicode_ci');
        Config::set('database.connections.tenant.prefix', '');
        Config::set('database.connections.tenant.strict', false); // O true, según tu MySQL 5.7
        
        // Configuraciones estándar de MySQL en Laravel para evitar dolores de cabeza
        Config::set('database.connections.tenant.charset', 'utf8mb4');
        Config::set('database.connections.tenant.collation', 'utf8mb4_unicode_ci');
        Config::set('database.connections.tenant.prefix', '');
        Config::set('database.connections.tenant.strict', false); // O true, según tu MySQL 5.7
        // 4. Purgamos la conexión previa y forzamos a Laravel a conectarse a la nueva DB
        DB::purge('tenant');
        DB::reconnect('tenant');

        // 5. Establecemos 'tenant' como la conexión por defecto para todo el ciclo de vida de esta request
        Config::set('database.default', 'tenant');

        return $next($request);
    }

}