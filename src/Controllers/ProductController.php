<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ProductController - Product management endpoints
 */
class ProductController extends Controller implements ControllerInterface
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * GET /api/products
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $categoryId = $this->get('category_id');
            $typeId = $this->get('type_id');
            $search = $this->get('search');

            $filters = [];
            if ($categoryId) {
                $filters['category_id'] = $categoryId;
            }
            if ($typeId) {
                $filters['type_id'] = $typeId;
            }
            if ($search) {
                $filters['search'] = $search;
            }

            $result = $this->productService->getAll($filters, $page, $perPage);

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
     * GET /api/products/:id
     */
    public function show($id)
    {
        try {
            $product = $this->productService->getById($id);
            return $this->json(['data' => $product]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/products
     */
    public function store()
    {
        try {
            $data = $this->all();

            $product = $this->productService->create($data);

            return $this->json(['data' => $product], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/products/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $product = $this->productService->update($id, $data);

            return $this->json(['data' => $product]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/products/:id
     */
    public function destroy($id)
    {
        try {
            $this->productService->delete($id);

            return $this->json(['message' => 'Product deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/products/category/:categoryId
     */
    public function getByCategory($categoryId)
    {
        try {
            $products = $this->productService->getProductsByCategory($categoryId);
            return $this->json(['data' => $products]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/products/type/:typeId
     */
    public function getByType($typeId)
    {
        try {
            $products = $this->productService->getProductsByType($typeId);
            return $this->json(['data' => $products]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
