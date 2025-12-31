<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Feature Test Case Class
 * Base class for API and feature tests
 */
abstract class FeatureTestCase extends TestCase
{
    protected $request;
    protected $response;
    protected $authToken = null;
    protected $authUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize HTTP objects
        $this->response = new Response();
    }

    /**
     * Create HTTP request
     */
    protected function request(string $method, string $path, array $data = [], array $headers = []): Response
    {
        // Create request object
        $this->request = new Request(
            $_SERVER,
            $method === 'GET' ? $data : [],
            $method !== 'GET' ? $data : [],
            []
        );

        // Set authentication header if token exists
        if ($this->authToken) {
            $headers['Authorization'] = "Bearer {$this->authToken}";
        }

        // TODO: Implement HTTP request execution
        // For now, return mock response
        return $this->response;
    }

    /**
     * Authenticate as user
     */
    protected function actingAs($user, string $token): self
    {
        $this->authUser = $user;
        $this->authToken = $token;

        return $this;
    }

    /**
     * Make GET request
     */
    protected function get(string $path, array $headers = []): Response
    {
        return $this->request('GET', $path, [], $headers);
    }

    /**
     * Make POST request
     */
    protected function post(string $path, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $path, $data, $headers);
    }

    /**
     * Make PUT request
     */
    protected function put(string $path, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $path, $data, $headers);
    }

    /**
     * Make DELETE request
     */
    protected function delete(string $path, array $headers = []): Response
    {
        return $this->request('DELETE', $path, [], $headers);
    }

    /**
     * Assert response status
     */
    protected function assertResponseStatus(int $status): self
    {
        $this->assertEquals($status, $this->response->getStatusCode());

        return $this;
    }

    /**
     * Assert response is 200 OK
     */
    protected function assertOk(): self
    {
        return $this->assertResponseStatus(200);
    }

    /**
     * Assert response is 201 Created
     */
    protected function assertCreated(): self
    {
        return $this->assertResponseStatus(201);
    }

    /**
     * Assert response is 401 Unauthorized
     */
    protected function assertUnauthorized(): self
    {
        return $this->assertResponseStatus(401);
    }

    /**
     * Assert response is 403 Forbidden
     */
    protected function assertForbidden(): self
    {
        return $this->assertResponseStatus(403);
    }

    /**
     * Assert response is 404 Not Found
     */
    protected function assertNotFound(): self
    {
        return $this->assertResponseStatus(404);
    }

    /**
     * Assert response is 422 Unprocessable Entity
     */
    protected function assertUnprocessable(): self
    {
        return $this->assertResponseStatus(422);
    }

    /**
     * Assert JSON response
     */
    protected function assertJsonResponse(array $expected): self
    {
        $actual = json_decode($this->response->getBody(), true);
        $this->assertEquals($expected, $actual);

        return $this;
    }

    /**
     * Assert JSON has key
     */
    protected function assertJsonHas(string $key): self
    {
        $data = json_decode($this->response->getBody(), true);
        $this->assertArrayHasKey($key, $data);

        return $this;
    }
}
