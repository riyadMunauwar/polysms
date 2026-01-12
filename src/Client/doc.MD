# HTTP Client Usage Documentation

A flexible, production-ready HTTP client for PHP with support for various content types, authentication, and full URL override capabilities.

## Table of Contents

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Configuration](#configuration)
- [Making Requests](#making-requests)
- [Authentication](#authentication)
- [Content Types](#content-types)
- [Advanced Features](#advanced-features)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

## Installation

```php
use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;
```

## Basic Usage

### Simple GET Request

```php
// Initialize client with base URL
$client = new Http('https://api.example.com');

// Make a GET request and get JSON response
$response = $client->request('/users')->json();

// Make a GET request and get raw response
$rawResponse = $client->request('/users')->body();
```

### POST Request

```php
$client = new Http('https://api.example.com');

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

$response = $client->request('/users', 'POST', $data)->json();
```

## Configuration

### Constructor Options

```php
$client = new Http(
    baseUrl: 'https://api.example.com',
    defaultHeaders: [
        'Accept' => 'application/json',
        'User-Agent' => 'MyApp/1.0'
    ],
    verifySsl: true,      // Enable SSL verification (default: true)
    timeout: 30           // Request timeout in seconds (default: 30)
);
```

### Runtime Configuration

```php
$client = new Http('https://api.example.com');

// Set SSL verification
$client->setSslVerification(false); // Not recommended for production

// Set timeout
$client->setTimeout(60);

// Set default headers
$client->setDefaultHeaders([
    'Accept' => 'application/json',
    'X-Custom-Header' => 'value'
]);
```

## Making Requests

### Request Method Signature

```php
$client->request(
    endpoint: '/path/to/resource',     // Required: URL path or full URL
    method: 'GET',                      // Optional: HTTP method (default: GET)
    data: [],                           // Optional: Request body data
    headers: [],                        // Optional: Request-specific headers
    queryParams: [],                    // Optional: Query string parameters
    contentType: 'application/json'     // Optional: Content-Type header
);
```

### GET Request with Query Parameters

```php
$response = $client->request(
    endpoint: '/users',
    method: 'GET',
    queryParams: [
        'page' => 1,
        'limit' => 20,
        'sort' => 'name'
    ]
)->json();

// Results in: https://api.example.com/users?page=1&limit=20&sort=name
```

### POST Request

```php
$data = [
    'title' => 'New Post',
    'content' => 'Post content here',
    'author_id' => 123
];

$response = $client->request('/posts', 'POST', $data)->json();
```

### PUT Request

```php
$data = [
    'title' => 'Updated Post',
    'content' => 'Updated content'
];

$response = $client->request('/posts/123', 'PUT', $data)->json();
```

### PATCH Request

```php
$data = ['status' => 'published'];

$response = $client->request('/posts/123', 'PATCH', $data)->json();
```

### DELETE Request

```php
$response = $client->request('/posts/123', 'DELETE')->json();
```

### Full URL Override

You can override the base URL by providing a complete URL:

```php
$client = new Http('https://api.example.com');

// Use base URL
$response1 = $client->request('/users')->json();
// Requests: https://api.example.com/users

// Override with full URL
$response2 = $client->request('https://different-api.com/data')->json();
// Requests: https://different-api.com/data

// Back to using base URL
$response3 = $client->request('/posts')->json();
// Requests: https://api.example.com/posts
```

### Without Base URL

```php
// Initialize without base URL
$client = new Http();

// Must use full URLs
$response = $client->request('https://api.example.com/users')->json();
```

## Authentication

### Bearer Token Authentication

```php
$client = new Http('https://api.example.com');
$client->setBearerToken('your-api-token-here');

// Token is automatically included in Authorization header
$response = $client->request('/protected/resource')->json();
```

### Custom Authentication Headers

```php
$response = $client->request(
    endpoint: '/users',
    headers: [
        'X-API-Key' => 'your-api-key',
        'X-API-Secret' => 'your-api-secret'
    ]
)->json();
```

### Basic Authentication

```php
$token = base64_encode('username:password');

$response = $client->request(
    endpoint: '/users',
    headers: [
        'Authorization' => 'Basic ' . $token
    ]
)->json();
```

## Content Types

### JSON (Default)

```php
$data = ['name' => 'John', 'age' => 30];

$response = $client->request(
    endpoint: '/users',
    method: 'POST',
    data: $data,
    contentType: 'application/json'
)->json();
```

### Form URL Encoded

```php
$data = ['username' => 'john', 'password' => 'secret'];

$response = $client->request(
    endpoint: '/login',
    method: 'POST',
    data: $data,
    contentType: 'application/x-www-form-urlencoded'
)->json();
```

### Multipart Form Data (File Upload)

```php
$data = [
    'title' => 'My Document',
    'file' => '/path/to/file.pdf',  // File path for upload
    'description' => 'Document description'
];

$response = $client->request(
    endpoint: '/documents',
    method: 'POST',
    data: $data,
    contentType: 'multipart/form-data'
)->json();
```

### Plain Text

```php
$response = $client->request(
    endpoint: '/webhook',
    method: 'POST',
    data: 'Plain text content',
    contentType: 'text/plain'
)->body();
```

## Advanced Features

### Custom Headers Per Request

```php
$response = $client->request(
    endpoint: '/users',
    headers: [
        'X-Custom-Header' => 'custom-value',
        'X-Request-ID' => uniqid()
    ]
)->json();
```

### Combining Query Parameters with Full URL

```php
$response = $client->request(
    endpoint: 'https://api.example.com/search',
    queryParams: [
        'q' => 'search term',
        'page' => 1
    ]
)->json();

// Results in: https://api.example.com/search?q=search+term&page=1
```

### Reset Client State

```php
$client = new Http('https://api.example.com');

// Make first request
$response1 = $client->request('/users', 'POST', $data)->json();

// Reset client state for clean next request
$client->reset();

// Make second request
$response2 = $client->request('/posts')->json();
```

### Response Handling

```php
// Get JSON response as associative array
$jsonData = $client->request('/users')->json();
echo $jsonData['name'];

// Get raw response as string
$rawData = $client->request('/users')->body();
echo $rawData;

// Get response and handle specific fields
$response = $client->request('/users/123')->json();
$userName = $response['data']['name'] ?? 'Unknown';
```

## Error Handling

### Basic Error Handling

```php
use Riyad\PolySms\Client\HttpException;

try {
    $response = $client->request('/users')->json();
    // Process response
} catch (HttpException $e) {
    echo "Error: " . $e->getMessage();
    echo "Code: " . $e->getCode();
}
```

### Comprehensive Error Handling

```php
try {
    $response = $client->request(
        endpoint: '/users',
        method: 'POST',
        data: $userData
    )->json();
    
    // Success handling
    return $response;
    
} catch (HttpException $e) {
    $code = $e->getCode();
    $message = $e->getMessage();
    
    // Handle specific HTTP status codes
    switch ($code) {
        case 400:
            // Bad request
            log_error("Bad request: $message");
            break;
        case 401:
            // Unauthorized
            log_error("Authentication failed: $message");
            break;
        case 404:
            // Not found
            log_error("Resource not found: $message");
            break;
        case 500:
            // Server error
            log_error("Server error: $message");
            break;
        default:
            // Other errors
            log_error("Request failed: $message");
    }
    
    throw $e; // Re-throw if needed
}
```

### Validation Before Request

```php
try {
    // Attempting to use relative path without base URL
    $client = new Http(); // No base URL
    $response = $client->request('/users')->json();
} catch (HttpException $e) {
    // Will throw: "Base URL is not set..."
    echo $e->getMessage();
}
```

## Best Practices

### 1. Reuse Client Instances

```php
// Good: Reuse client for multiple requests
$client = new Http('https://api.example.com');
$client->setBearerToken($token);

$users = $client->request('/users')->json();
$posts = $client->request('/posts')->json();
$comments = $client->request('/comments')->json();
```

### 2. Use Environment Variables for Configuration

```php
$client = new Http(
    baseUrl: getenv('API_BASE_URL'),
    defaultHeaders: [
        'Accept' => 'application/json'
    ],
    verifySsl: getenv('APP_ENV') === 'production',
    timeout: (int) getenv('API_TIMEOUT') ?: 30
);
```

### 3. Create API-Specific Wrapper Classes

```php
class UserApiClient
{
    private Http $httpClient;
    
    public function __construct(string $apiToken)
    {
        $this->httpClient = new Http('https://api.example.com');
        $this->httpClient->setBearerToken($apiToken);
    }
    
    public function getUsers(int $page = 1): array
    {
        return $this->httpClient->request(
            endpoint: '/users',
            queryParams: ['page' => $page]
        )->json();
    }
    
    public function createUser(array $userData): array
    {
        return $this->httpClient->request(
            endpoint: '/users',
            method: 'POST',
            data: $userData
        )->json();
    }
}

// Usage
$userApi = new UserApiClient('your-api-token');
$users = $userApi->getUsers(page: 1);
$newUser = $userApi->createUser(['name' => 'John Doe']);
```

### 4. Implement Retry Logic for Transient Failures

```php
function makeRequestWithRetry(Http $client, string $endpoint, int $maxRetries = 3): array
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $client->request($endpoint)->json();
        } catch (HttpException $e) {
            $attempt++;
            
            // Only retry on server errors or timeouts
            if ($e->getCode() >= 500 || $e->getCode() === 0) {
                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt)); // Exponential backoff
                    continue;
                }
            }
            
            throw $e;
        }
    }
}
```

### 5. Log Requests for Debugging

```php
$client = new Http('https://api.example.com');

try {
    $endpoint = '/users';
    error_log("Making request to: $endpoint");
    
    $response = $client->request($endpoint)->json();
    
    error_log("Request successful: " . json_encode($response));
    
    return $response;
} catch (HttpException $e) {
    error_log("Request failed: " . $e->getMessage());
    throw $e;
}
```

### 6. Use Type Hinting and Validation

```php
class ApiClient
{
    private Http $client;
    
    public function __construct(Http $client)
    {
        $this->client = $client;
    }
    
    public function createResource(array $data): array
    {
        // Validate data before sending
        if (empty($data['required_field'])) {
            throw new \InvalidArgumentException('required_field is missing');
        }
        
        return $this->client->request(
            endpoint: '/resources',
            method: 'POST',
            data: $data
        )->json();
    }
}
```

## Complete Example

```php
<?php

use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;

// Initialize client
$client = new Http(
    baseUrl: 'https://api.example.com',
    defaultHeaders: ['Accept' => 'application/json'],
    verifySsl: true,
    timeout: 30
);

// Set authentication
$client->setBearerToken('your-api-token');

try {
    // GET request with query parameters
    $users = $client->request(
        endpoint: '/users',
        queryParams: ['page' => 1, 'limit' => 10]
    )->json();
    
    // POST request with JSON data
    $newUser = $client->request(
        endpoint: '/users',
        method: 'POST',
        data: [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]
    )->json();
    
    // PUT request to update
    $updatedUser = $client->request(
        endpoint: '/users/' . $newUser['id'],
        method: 'PUT',
        data: ['name' => 'Jane Doe']
    )->json();
    
    // Request to different API (full URL override)
    $externalData = $client->request(
        endpoint: 'https://another-api.com/data'
    )->json();
    
    // File upload
    $upload = $client->request(
        endpoint: '/upload',
        method: 'POST',
        data: [
            'file' => '/path/to/file.pdf',
            'title' => 'My Document'
        ],
        contentType: 'multipart/form-data'
    )->json();
    
    echo "All requests successful!";
    
} catch (HttpException $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Status Code: " . $e->getCode());
    
    // Handle error appropriately
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
```

## Troubleshooting

### SSL Certificate Verification Issues

If you encounter SSL certificate errors in development:

```php
// Only for development/testing - NOT for production!
$client = new Http(
    baseUrl: 'https://api.example.com',
    verifySsl: false
);
```

### Timeout Issues

For long-running requests:

```php
$client = new Http('https://api.example.com');
$client->setTimeout(120); // 2 minutes

$response = $client->request('/long-running-task')->json();
```

### Invalid JSON Response

```php
try {
    $response = $client->request('/endpoint')->json();
} catch (HttpException $e) {
    if (strpos($e->getMessage(), 'Invalid JSON') !== false) {
        // Handle invalid JSON
        $rawResponse = $client->request('/endpoint')->body();
        // Process raw response differently
    }
}
```