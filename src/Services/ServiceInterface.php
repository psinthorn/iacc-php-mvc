<?php

namespace App\Services;

/**
 * ServiceInterface - Contract for CRUD services
 * 
 * Defines standard operations for domain entity services.
 * Specific services may extend with additional methods.
 */
interface ServiceInterface
{
    /**
     * Get all records with optional filtering and pagination
     *
     * @param array $filters Filter criteria
     * @param int $page Page number (1-indexed)
     * @param int $perPage Records per page
     * @return array Paginated results with metadata
     */
    public function getAll($filters = [], $page = 1, $perPage = 15);

    /**
     * Get record by ID
     *
     * @param mixed $id Record ID
     * @return object Record object
     *
     * @throws NotFoundException If record not found
     */
    public function getById($id);

    /**
     * Create new record
     *
     * @param array $data Record data
     * @return object Created record object
     *
     * @throws ValidationException If data invalid
     * @throws BusinessException If business rule violated
     */
    public function create(array $data);

    /**
     * Update existing record
     *
     * @param mixed $id Record ID
     * @param array $data Updated data
     * @return object Updated record object
     *
     * @throws NotFoundException If record not found
     * @throws ValidationException If data invalid
     * @throws BusinessException If business rule violated
     */
    public function update($id, array $data);

    /**
     * Delete record
     *
     * @param mixed $id Record ID
     * @return bool True if deleted
     *
     * @throws NotFoundException If record not found
     * @throws BusinessException If cannot delete (dependencies exist)
     */
    public function delete($id);

    /**
     * Restore soft-deleted record
     *
     * @param mixed $id Record ID
     * @return object Restored record object
     *
     * @throws NotFoundException If record not found
     */
    public function restore($id);
}
