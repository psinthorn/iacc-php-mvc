<?php

namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Permission;

/**
 * PermissionRepository - Permission data access layer
 */
class PermissionRepository extends Repository
{
    protected $modelClass = Permission::class;
    protected $table = 'permission';

    public function __construct(Database $database = null)
    {
        parent::__construct($database, new Permission());
    }

    /**
     * Find permission by name
     */
    public function findByName(string $name)
    {
        return $this->findBy('name', $name);
    }

    /**
     * Find permission by resource and action
     */
    public function findByResourceAction(string $resource, string $action)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE resource = ? AND action = ? LIMIT 1"
        );
        $stmt->execute([$resource, $action]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Get all permissions for resource
     */
    public function getByResource(string $resource)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE resource = ? ORDER BY action"
        );
        $stmt->execute([$resource]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all permissions by pattern (resource:action)
     */
    public function getByPattern(string $pattern)
    {
        [$resource, $action] = array_pad(explode(':', $pattern), 2, '');

        if ($action === '*') {
            return $this->getByResource($resource);
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE resource = ? AND action = ? ORDER BY name"
        );
        $stmt->execute([$resource, $action]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create permission for resource:action
     */
    public function createForResourceAction(string $resource, string $action, string $name = null, string $description = null)
    {
        if (!$name) {
            $name = "{$resource}:{$action}";
        }

        return $this->create([
            'name' => $name,
            'resource' => $resource,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
