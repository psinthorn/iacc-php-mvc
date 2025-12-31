<?php

namespace Tests\Feature\Product;

use Tests\Feature\FeatureTestCase;

/**
 * Product API Tests
 */
class ProductApiTest extends FeatureTestCase
{
    /**
     * Test list products returns 200
     */
    public function testListProductsReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/products');

        $this->assertOk();
    }

    /**
     * Test list products returns paginated results
     */
    public function testListProductsReturnsPaginatedResults()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/products?page=1&per_page=10');

        $this->assertJsonHas('data');
    }

    /**
     * Test show product returns 200
     */
    public function testShowProductReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/products/1');

        $this->assertOk();
    }

    /**
     * Test show nonexistent product returns 404
     */
    public function testShowNonexistentProductReturns404()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/products/99999');

        $this->assertNotFound();
    }

    /**
     * Test create product with valid data returns 201
     */
    public function testCreateProductWithValidDataReturns201()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:create']]);

        $data = [
            'name' => 'New Product',
            'sku' => 'NEW-001',
            'price' => 99.99,
            'stock_quantity' => 100,
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/products', $data);

        $this->assertCreated();
    }

    /**
     * Test create product without permission returns 403
     */
    public function testCreateProductWithoutPermissionReturns403()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $data = [
            'name' => 'New Product',
            'sku' => 'NEW-001',
            'price' => 99.99,
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/products', $data);

        $this->assertForbidden();
    }

    /**
     * Test create product with invalid data returns 422
     */
    public function testCreateProductWithInvalidDataReturns422()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:create']]);

        $data = [
            'price' => 'invalid-price',
            // missing required fields
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/products', $data);

        $this->assertUnprocessable();
    }

    /**
     * Test update product returns 200
     */
    public function testUpdateProductReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:edit']]);

        $data = [
            'name' => 'Updated Product',
            'price' => 149.99,
        ];

        $response = $this->actingAs(['id' => 1], $token)->put('/api/v1/products/1', $data);

        $this->assertOk();
    }

    /**
     * Test delete product returns 200
     */
    public function testDeleteProductReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:delete']]);

        $response = $this->actingAs(['id' => 1], $token)->delete('/api/v1/products/1');

        $this->assertOk();
    }

    /**
     * Test filter products by category
     */
    public function testFilterProductsByCategory()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/products?category_id=1');

        $this->assertOk();
    }

    /**
     * Test filter products by price range
     */
    public function testFilterProductsByPriceRange()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/products?min_price=50&max_price=200');

        $this->assertOk();
    }

    /**
     * Test search products
     */
    public function testSearchProducts()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['product:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/products?search=laptop');

        $this->assertOk();
    }
}
