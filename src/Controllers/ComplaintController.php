<?php

namespace App\Controllers;

use App\Services\ComplaintService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ComplaintController - Customer complaint management
 */
class ComplaintController extends Controller implements ControllerInterface
{
    protected $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * GET /api/complaints
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $status = $this->get('status');
            $priority = $this->get('priority');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }
            if ($priority) {
                $filters['priority'] = $priority;
            }

            $result = $this->complaintService->getAll($filters, $page, $perPage);

            return $this->jsonPaginated(
                $result['data'],
                $result['page'],
                $result['per_page'],
                $result['total']
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/complaints/:id
     */
    public function show($id)
    {
        try {
            $complaint = $this->complaintService->getById($id);
            return $this->json(['data' => $complaint]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/complaints
     */
    public function store()
    {
        try {
            $data = $this->all();

            $complaint = $this->complaintService->create($data);

            return $this->json(['data' => $complaint], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/complaints/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $complaint = $this->complaintService->update($id, $data);

            return $this->json(['data' => $complaint]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/complaints/:id
     */
    public function destroy($id)
    {
        try {
            $this->complaintService->delete($id);

            return $this->json(['message' => 'Complaint deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/complaints/:id/resolve
     */
    public function resolve($id)
    {
        try {
            $data = $this->all();
            $resolution = $data['resolution'] ?? '';

            if (!$resolution) {
                return $this->jsonError('Resolution required', 400);
            }

            $complaint = $this->complaintService->resolve($id, $resolution);

            return $this->json([
                'message' => 'Complaint resolved',
                'data' => $complaint,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/customers/:customerId/complaints
     */
    public function getByCustomer($customerId)
    {
        try {
            $complaints = $this->complaintService->getByCustomer($customerId);
            return $this->json(['data' => $complaints]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/complaints/open
     */
    public function getOpen()
    {
        try {
            $complaints = $this->complaintService->getOpenComplaints();
            return $this->json(['data' => $complaints]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
