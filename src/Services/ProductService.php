<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductTypeRepository;
use App\Repositories\BrandRepository;
use App\Repositories\StockRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\ProductCreated;
use App\Events\ProductUpdated;
use App\Events\ProductDeleted;

/**
 * ProductService - Product management
 */
class ProductService extends Service implements ServiceInterface
{
    protected $repository;
    protected $categoryRepository;
    protected $typeRepository;
    protected $brandRepository;
    protected $stockRepository;

    public function __construct(
        ProductRepository $repository,
        CategoryRepository $categoryRepository,
        ProductTypeRepository $typeRepository,
        BrandRepository $brandRepository,
        StockRepository $stockRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->categoryRepository = $categoryRepository;
        $this->typeRepository = $typeRepository;
        $this->brandRepository = $brandRepository;
        $this->stockRepository = $stockRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['category_id'])) {
            $query = array_filter($query, fn($p) => $p->category_id == $filters['category_id']);
        }
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query = array_filter($query, fn($p) =>
                stripos($p->name, $search) !== false || stripos($p->code, $search) !== false
            );
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return [
            'data' => array_values($items),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public function getById($id)
    {
        $product = $this->repository->find($id);
        if (!$product) {
            throw new NotFoundException("Product not found");
        }
        return $product;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'name' => 'required|string|min:3',
            'code' => 'required|string|unique:product,code',
            'category_id' => 'required|exists:category,id',
            'type_id' => 'required|exists:product_type,id',
            'brand_id' => 'required|exists:brand,id',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'unit' => 'required|string',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Verify cost < unit price
        if ((float)$data['cost_price'] > (float)$data['unit_price']) {
            throw new BusinessException("Cost price cannot exceed unit price");
        }

        return $this->transaction(function () use ($data) {
            $product = $this->repository->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'category_id' => $data['category_id'],
                'type_id' => $data['type_id'],
                'brand_id' => $data['brand_id'],
                'unit_price' => $data['unit_price'],
                'cost_price' => $data['cost_price'],
                'unit' => $data['unit'],
                'description' => $data['description'] ?? '',
                'status' => 1,
            ]);

            $this->log('product_created', ['product_id' => $product->id, 'code' => $product->code]);
            $this->dispatch(new ProductCreated($product));

            return $product;
        });
    }

    public function update($id, array $data)
    {
        $product = $this->getById($id);

        $errors = $this->validate($data, [
            'name' => 'string|min:3',
            'code' => 'string|unique:product,code,' . $id,
            'category_id' => 'exists:category,id',
            'type_id' => 'exists:product_type,id',
            'brand_id' => 'exists:brand,id',
            'unit_price' => 'numeric|min:0',
            'cost_price' => 'numeric|min:0',
            'status' => 'in:0,1',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data) {
            $product = $this->repository->update($id, array_filter($data));

            $this->log('product_updated', ['product_id' => $id]);
            $this->dispatch(new ProductUpdated($product));

            return $product;
        });
    }

    public function delete($id)
    {
        $this->getById($id);

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('product_deleted', ['product_id' => $id]);
            $this->dispatch(new ProductDeleted($id));
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    public function getProductsByCategory($categoryId)
    {
        return $this->repository->getByCategory($categoryId);
    }

    public function getProductsByType($typeId)
    {
        return $this->repository->getByType($typeId);
    }
}
