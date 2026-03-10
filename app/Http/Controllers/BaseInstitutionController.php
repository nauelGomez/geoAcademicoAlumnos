<?php

namespace App\Http\Controllers;

use App\Services\DatabaseManager;
use Illuminate\Http\Request;

abstract class BaseInstitutionController extends Controller
{
    /**
     * Get institution ID from request
     */
    protected function getInstitutionId(Request $request)
    {
        return $request->attributes->get('institution_id') ??
               $request->header('X-Institution-ID') ??
               $request->query('institution_id') ??
               $request->route('institutionId');
    }

    /**
     * Get database connection for institution
     */
    protected function getInstitutionConnection($institutionId = null)
    {
        $institutionId = $institutionId ?? request()->attributes->get('institution_id');
        return DatabaseManager::connection($institutionId);
    }

    /**
     * Get table query for institution
     */
    protected function institutionTable($tableName, $institutionId = null)
    {
        $institutionId = $institutionId ?? request()->attributes->get('institution_id');
        return DatabaseManager::table($tableName, $institutionId);
    }

    /**
     * Execute query on institution database
     */
    protected function institutionQuery($institutionId, $query, $bindings = [])
    {
        return DatabaseManager::query($institutionId, $query, $bindings);
    }

    /**
     * Validate institution exists
     */
    protected function validateInstitution($institutionId)
    {
        try {
            DatabaseManager::getInstitutionData($institutionId);
        } catch (\Exception $e) {
            abort(404, 'Institution not found');
        }
    }

    /**
     * Get model with institution connection
     */
    protected function getModelWithConnection($modelClass, $institutionId)
    {
        $model = new $modelClass;
        $model->setConnection(DatabaseManager::getConnectionForInstitution($institutionId));
        return $model;
    }
}
