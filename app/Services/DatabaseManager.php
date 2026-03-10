<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use PDO;

class DatabaseManager
{
    /**
     * Cache for institution data
     */
    protected static $institutionCache = [];

    /**
     * Get institution data from database
     */
    public static function getInstitutionData($institutionId)
    {
        if (!isset(self::$institutionCache[$institutionId])) {
            // Configure connection to dmendoza_pr_el_gral_familias database
            Config::set('database.connections.familias', [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'dmendoza_pr_el_gral_familias',
                'username' => 'root',
                'password' => '8695',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null,
            ]);
            
            // Use familias connection to get institution data
            $institution = DB::connection('familias')
                ->table('instituciones')
                ->select([
                    'ID',
                    'Institucion',
                    'Carpeta',
                    'ID_Ciudad',
                    'ID_Provincia',
                    'Direccion',
                    'Telefono',
                    'CP',
                    'Mail',
                    'Seguridad',
                    'Server',
                    'User',
                    'Pass',
                    'DB_Name',
                    'URL',
                    'Logo',
                    'Ver_App'
                ])
                ->where('ID', $institutionId)
                ->first();
            
            if (!$institution) {
                throw new \Exception("Institution with ID {$institutionId} not found");
            }
            
            self::$institutionCache[$institutionId] = $institution;
        }
        
        return self::$institutionCache[$institutionId];
    }

    /**
     * Configure database connection for institution
     */
    protected static function configureInstitutionConnection($institutionId)
    {
        $institution = self::getInstitutionData($institutionId);
        $connectionName = "institution_{$institutionId}";
        
        // Create new connection using exact same config as default mysql but different database
        Config::set("database.connections.{$connectionName}", [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $institution->DB_Name,
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => [], // Remove PDO options that cause authentication issues
        ]);
        
        return $connectionName;
    }

    /**
     * Get database connection name for institution
     */
    public static function getConnectionForInstitution($institutionId)
    {
        return self::configureInstitutionConnection($institutionId);
    }

    /**
     * Get database connection for institution
     */
    public static function connection($institutionId)
    {
        $institution = self::getInstitutionData($institutionId);
        
        // Create direct PDO connection
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3306;dbname={$institution->DB_Name};charset=utf8mb4",
            'root',
            '8695',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]
        );
        
        // Create a database connection wrapper
        return new class($pdo) {
            private $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function table($tableName) {
                return new class($this->pdo, $tableName) {
                    private $pdo;
                    private $tableName;
                    
                    public function __construct($pdo, $tableName) {
                        $this->pdo = $pdo;
                        $this->tableName = $tableName;
                    }
                    
                    public function where($column, $operator, $value = null) {
                        if ($value === null) {
                            $value = $operator;
                            $operator = '=';
                        }
                        $query = "SELECT * FROM {$this->tableName} WHERE $column $operator ?";
                        $stmt = $this->pdo->prepare($query);
                        $stmt->execute([$value]);
                        return $stmt->fetch();
                    }
                    
                    public function select($columns = ['*']) {
                        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
                        $query = "SELECT $columnList FROM {$this->tableName}";
                        $stmt = $this->pdo->prepare($query);
                        $stmt->execute();
                        return $stmt->fetchAll();
                    }
                    
                    public function first() {
                        $query = "SELECT * FROM {$this->tableName} LIMIT 1";
                        $stmt = $this->pdo->prepare($query);
                        $stmt->execute();
                        return $stmt->fetch();
                    }
                };
            }
        };
    }

    /**
     * Get table query for specific institution
     */
    public static function table($tableName, $institutionId)
    {
        return self::connection($institutionId)->table($tableName);
    }

    /**
     * Execute a query on specific institution database
     */
    public static function query($institutionId, $query, $bindings = [])
    {
        return self::connection($institutionId)->select($query, $bindings);
    }

    /**
     * Get all available institutions from database
     */
    public static function getAvailableInstitutions()
    {
        return DB::connection('familias')
            ->table('instituciones')
            ->pluck('ID')
            ->toArray();
    }

    /**
     * Get institution info
     */
    public static function getInstitutionInfo($institutionId)
    {
        return self::getInstitutionData($institutionId);
    }

    /**
     * Clear institution cache
     */
    public static function clearCache($institutionId = null)
    {
        if ($institutionId) {
            unset(self::$institutionCache[$institutionId]);
        } else {
            self::$institutionCache = [];
        }
    }
}
