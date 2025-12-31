<?php

namespace App\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Controller Interface
 * Defines contract for all controllers
 */
interface ControllerInterface
{
    public function __construct(Request $request, Response $response);

    /**
     * List all resources (GET /)
     */
    public function index();

    /**
     * Get single resource (GET /:id)
     */
    public function show($id);

    /**
     * Create resource (POST /)
     */
    public function store();

    /**
     * Update resource (PUT /:id)
     */
    public function update($id);

    /**
     * Delete resource (DELETE /:id)
     */
    public function destroy($id);
}
