<?php

namespace App\Auth;

/**
 * Permission - Define granular permissions for RBAC
 */
class Permission
{
    protected $id;
    protected $name;
    protected $resource;
    protected $action;
    protected $description;

    public function __construct(
        int $id = 0,
        string $name,
        string $resource,
        string $action,
        string $description = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->resource = $resource;
        $this->action = $action;
        $this->description = $description;
    }

    /**
     * Get permission ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get permission name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get resource
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * Get action
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Check if permission matches pattern
     */
    public function matches(string $pattern): bool
    {
        // Pattern like "company:view" or "company:*" or "*:*"
        $parts = explode(':', $pattern);

        if (count($parts) !== 2) {
            return false;
        }

        list($resourcePattern, $actionPattern) = $parts;

        $resourceMatch = $resourcePattern === '*' || $resourcePattern === $this->resource;
        $actionMatch = $actionPattern === '*' || $actionPattern === $this->action;

        return $resourceMatch && $actionMatch;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resource' => $this->resource,
            'action' => $this->action,
            'description' => $this->description,
        ];
    }
}
