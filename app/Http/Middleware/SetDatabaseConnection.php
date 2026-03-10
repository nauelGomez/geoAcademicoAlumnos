<?php

namespace App\Http\Middleware;

use App\Services\DatabaseManager;
use Closure;
use Illuminate\Http\Request;

class SetDatabaseConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get institution ID from request (header, query parameter, or route parameter)
        $institutionId = $this->getInstitutionId($request);
        
        if ($institutionId) {
            // You can store this in request for later use
            $request->attributes->set('institution_id', $institutionId);
        }
        
        return $next($request);
    }

    /**
     * Extract institution ID from request
     */
    private function getInstitutionId(Request $request)
    {
        // Priority order: header > query parameter > route parameter
        return $request->header('X-Institution-ID') ??
               $request->query('institution_id') ??
               $request->route('institutionId');
    }
}
