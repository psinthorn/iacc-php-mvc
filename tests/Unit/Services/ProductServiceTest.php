<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * Product Service Tests
 */
class ProductServiceTest extends TestCase
{
    protected $productService;
    protected $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->productService = new ProductService(
            $this->productRepository,
            $this->db,
            new \App\Foundation\Logger()
        );
    }

    /**
     * Test list products returns paginated results
     */
    public function testListProductsReturnsPaginatedResults()
    {
        $products = [
            (object)['id' => 1, 'name' => 'Product 1', 'price' => 100.00],
            (object)['id' => 2, 'name' => 'Product 2', 'price' => 200.00],
        ];

        $this->productRepository->method('paginate')->willReturn([
            'data' => $products,
            'total' => 2,
        ]);

        $result = $this->productService->listProducts(1, 10);

        $this->assertCount(2, $result['data']);
    }

    /**
     * Test get product returns product data
     */
    public function testGetProductReturnsProductData()
    {
        $product = (object)[
            'id' => 1,
            'name' => 'Test Product',
            'price' => 150.00,
            'sku' => 'TEST-001',
        ];

        $this->productRepository->method('find')->willReturn($product);

        $result = $this->productService->getProduct(1);

        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals(150.00, $result->price);
    }

    /**
     * Test get nonexistent product throws exception
     */
    public function testGetNonexistentProductThrowsException()
    {
        $this->productRepository->method('find')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->productService->getProduct(999);
    }

    /**
     * Test create product with valid data
     */
    public function testCreateProductWithValidData()
    {
        $data = [
            'name' => 'New Product',
            'price' => 99.99,
            'sku' => 'NEW-001',
            'category_id' => 1,
        ];

        $product = (object)array_merge($data, ['id' => 1]);
        $this->productRepository->method('create')->willReturn($product);

        $result = $this->productService->createProduct($data);

        $this->assertEquals('New Product', $result->name);
    }

    /**
     * Test create product with duplicate SKU throws exception
     */
    public function testCreateProductWithDuplicateSkuThrowsException()
    {
        $data = [
            'name' => 'New Product',
            'sku' => 'EXISTING-SKU',
            'price' => 99.99,
        ];

        $this->productRepository->method('findBySku')->willReturn(
            (object)['id' => 1, 'sku' => 'EXISTING-SKU']
        );

        $this->expectException(ValidationException::class);
        $this->productService->createProduct($data);
    }

    /**
     * Test update product
     */
    public function testUpdateProductWithValidData()
    {
        $data = [
            'name' => 'Updated Product',
            'price' => 199.99,
        ];

        $product = (object)array_merge($data, ['id' => 1, 'sku' => 'TEST-001']);
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->method('update')->willReturn($product);

        $result = $this->productService->updateProduct(1, $data);

        $this->assertEquals('Updated Product', $result->name);
    }

    /**
     * Test delete product
     */
    public function testDeleteProductRemovesRecord()
    {
        $product = (object)['id' => 1, 'name' => 'Test Product'];
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->method('delete')->willReturn(true);

        $result = $this->productService->deleteProduct(1);

        $this->assertTrue($result);
    }

    /**
     * Test get product by SKU
     */
    public function testGetProductBySkuReturnsProduct()
    {
        $product = (object)['id' => 1, 'name' => 'Test', 'sku' => 'TEST-001'];
        $this->productRepository->method('findBySku')->willReturn($product);

        $result = $this->productService->getProductBySku('TEST-001');

        $this->assertEquals('TEST-001', $result->sku);
    }

    /**
     * Test adjust product stock
     */
    public function testAdjustProductStockUpdatesQuantity()
    {
        $product = (object)['id' => 1, 'stock_quantity' => 100];
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->method('updateStock')->willReturn(true);

        $result = $this->productService->adjustStock(1, -50);

        $this->assertTrue($result);
    }
}
