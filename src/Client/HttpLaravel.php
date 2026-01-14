<?php

declare(strict_types=1);

namespace Riyad\PolySms\Client;

use Exception;
use Illuminate\Support\Facades\Http as LaravelHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Riyad\PolySms\Client\HttpException;

class HttpLaravel
{
    private string $baseUrl;
    private array $defaultHeaders = [];
    private ?string $bearerToken = null;
    private bool $verifySsl = true;
    private int $timeout = 30;

    private string $endpoint = '';
    private string $method = 'GET';
    private array|string $data = [];
    private array $headers = [];
    private array $queryParams = [];
    private string $contentType = 'application/json';
    private bool $isFullUrl = false;
    private bool $throwOnError = true;

    public function __construct(
        string $baseUrl = '',
        array $defaultHeaders = [],
        bool $verifySsl = true,
        int $timeout = 30
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = $defaultHeaders;
        $this->verifySsl = $verifySsl;
        $this->timeout = $timeout;
    }

    public function setBearerToken(string $token): self
    {
        $this->bearerToken = $token;
        return $this;
    }

    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    public function setSslVerification(bool $verify): self
    {
        $this->verifySsl = $verify;
        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set whether to throw exceptions on HTTP errors (non-2xx status codes)
     * 
     * @param bool $throw If true, throws HttpException on non-2xx responses (default: true)
     * @return self
     */
    public function throwOnError(bool $throw = true): self
    {
        $this->throwOnError = $throw;
        return $this;
    }

    /**
     * Prepare the request
     * 
     * The endpoint can be either:
     * - A relative path (e.g., '/api/users') - will be appended to baseUrl
     * - A full URL (e.g., 'https://example.com/api/users') - will override baseUrl
     * 
     * @param string $endpoint Relative path or full URL
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE, etc.)
     * @param array|string $data Request body data
     * @param array $headers Additional headers for this request
     * @param array $queryParams Query string parameters
     * @param string $contentType Content-Type header value
     * @return self
     */
    public function request(
        string $endpoint,
        string $method = 'GET',
        array|string $data = [],
        array $headers = [],
        array $queryParams = [],
        string $contentType = 'application/json'
    ): self {
        $this->endpoint = $endpoint;
        $this->method = strtoupper($method);
        $this->data = $data;
        $this->headers = $headers;
        $this->queryParams = $queryParams;
        $this->contentType = $contentType;
        $this->isFullUrl = $this->_isFullUrl($endpoint);

        return $this;
    }

    /**
     * Get raw response
     * 
     * @return string
     * @throws HttpException
     */
    public function body(): string
    {
        return $this->_executeRequest(false);
    }

    /**
     * Get JSON-decoded response
     * 
     * @return array
     * @throws HttpException
     */
    public function json(): array
    {
        return $this->_executeRequest(true);
    }

    /**
     * Get response with status code and body
     * Useful when you want to handle HTTP errors manually
     * 
     * @return array ['status' => int, 'body' => string, 'success' => bool]
     * @throws HttpException Only for cURL errors, not HTTP errors
     */
    public function response(): array
    {
        return $this->_executeRequest(false, true);
    }

    /**
     * Check if the endpoint is a full URL
     * 
     * @param string $endpoint
     * @return bool
     */
    private function _isFullUrl(string $endpoint): bool
    {
        return (bool) filter_var($endpoint, FILTER_VALIDATE_URL) ||
               preg_match('/^https?:\/\//i', $endpoint);
    }

    /**
     * Build full URL with query parameters
     * 
     * @return string
     * @throws HttpException
     */
    private function _buildUrl(): string
    {
        // If endpoint is a full URL, use it directly
        if ($this->isFullUrl) {
            $url = $this->endpoint;
        } else {
            // Otherwise, combine baseUrl with endpoint
            if (empty($this->baseUrl)) {
                throw new HttpException(
                    'Base URL is not set. Either provide a base URL in the constructor or use a full URL in the request.'
                );
            }
            $url = $this->baseUrl . '/' . ltrim($this->endpoint, '/');
        }

        return $url;
    }

    /**
     * Prepare headers
     * 
     * @return array
     */
    private function _prepareHeaders(): array
    {
        $finalHeaders = array_merge($this->defaultHeaders, $this->headers);

        if ($this->bearerToken) {
            $finalHeaders['Authorization'] = 'Bearer ' . $this->bearerToken;
        }

        if ($this->contentType !== 'multipart/form-data') {
            $finalHeaders['Content-Type'] = $this->contentType;
        }

        return $finalHeaders;
    }

    /**
     * Build Laravel HTTP client with options
     * 
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function _buildHttpClient()
    {
        $client = LaravelHttp::timeout($this->timeout)
            ->withHeaders($this->_prepareHeaders());

        if (!$this->verifySsl) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Execute HTTP request and return raw or JSON response
     * 
     * @param bool $asJson Whether to decode response as JSON
     * @param bool $withStatus Whether to return response with status code
     * @return string|array
     * @throws HttpException
     */
    private function _executeRequest(bool $asJson, bool $withStatus = false): string|array
    {
        try {
            $client = $this->_buildHttpClient();
            $url = $this->_buildUrl();

            // Execute the request based on method and content type
            $response = $this->_sendRequest($client, $url);

            // Handle throwOnError setting
            if (!$withStatus && $this->throwOnError && !$response->successful()) {
                throw new HttpException(
                    "HTTP error: {$response->status()} - {$response->body()}",
                    $response->status()
                );
            }

            // Return response based on requested format
            if ($withStatus) {
                return [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'success' => $response->successful()
                ];
            }

            if ($asJson) {
                $decoded = $response->json();
                if ($decoded === null && $response->body() !== 'null') {
                    throw new HttpException(
                        "Invalid JSON response: " . json_last_error_msg()
                    );
                }
                return $decoded ?? [];
            }

            return $response->body();

        } catch (RequestException $e) {
            throw new HttpException(
                "Request failed: " . $e->getMessage(),
                $e->response ? $e->response->status() : 0
            );
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException("Request error: " . $e->getMessage());
        }
    }

    /**
     * Send the HTTP request based on method and content type
     * 
     * @param \Illuminate\Http\Client\PendingRequest $client
     * @param string $url
     * @return Response
     * @throws HttpException
     */
    private function _sendRequest($client, string $url): Response
    {
        // Add query parameters
        if (!empty($this->queryParams)) {
            $client = $client->withQueryParameters($this->queryParams);
        }

        $method = strtolower($this->method);

        // Handle different HTTP methods
        switch ($method) {
            case 'get':
                return $client->get($url);

            case 'post':
                return $this->_sendPostRequest($client, $url);

            case 'put':
                return $this->_sendPutRequest($client, $url);

            case 'patch':
                return $this->_sendPatchRequest($client, $url);

            case 'delete':
                return $this->_sendDeleteRequest($client, $url);

            case 'head':
                return $client->head($url);

            case 'options':
                return $client->send('OPTIONS', $url);

            default:
                return $client->send($this->method, $url, $this->_prepareRequestData());
        }
    }

    /**
     * Send POST request based on content type
     * 
     * @param \Illuminate\Http\Client\PendingRequest $client
     * @param string $url
     * @return Response
     */
    private function _sendPostRequest($client, string $url): Response
    {
        switch ($this->contentType) {
            case 'multipart/form-data':
                return $client->asMultipart()->post($url, $this->_prepareMultipartData());

            case 'application/x-www-form-urlencoded':
                return $client->asForm()->post($url, is_array($this->data) ? $this->data : []);

            case 'application/json':
                return $client->post($url, is_array($this->data) ? $this->data : []);

            case 'text/plain':
                return $client->withBody(
                    is_string($this->data) ? $this->data : json_encode($this->data),
                    'text/plain'
                )->post($url);

            default:
                return $client->post($url, $this->_prepareRequestData());
        }
    }

    /**
     * Send PUT request based on content type
     * 
     * @param \Illuminate\Http\Client\PendingRequest $client
     * @param string $url
     * @return Response
     */
    private function _sendPutRequest($client, string $url): Response
    {
        switch ($this->contentType) {
            case 'application/x-www-form-urlencoded':
                return $client->asForm()->put($url, is_array($this->data) ? $this->data : []);

            case 'application/json':
                return $client->put($url, is_array($this->data) ? $this->data : []);

            case 'text/plain':
                return $client->withBody(
                    is_string($this->data) ? $this->data : json_encode($this->data),
                    'text/plain'
                )->put($url);

            default:
                return $client->put($url, $this->_prepareRequestData());
        }
    }

    /**
     * Send PATCH request based on content type
     * 
     * @param \Illuminate\Http\Client\PendingRequest $client
     * @param string $url
     * @return Response
     */
    private function _sendPatchRequest($client, string $url): Response
    {
        switch ($this->contentType) {
            case 'application/x-www-form-urlencoded':
                return $client->asForm()->patch($url, is_array($this->data) ? $this->data : []);

            case 'application/json':
                return $client->patch($url, is_array($this->data) ? $this->data : []);

            case 'text/plain':
                return $client->withBody(
                    is_string($this->data) ? $this->data : json_encode($this->data),
                    'text/plain'
                )->patch($url);

            default:
                return $client->patch($url, $this->_prepareRequestData());
        }
    }

    /**
     * Send DELETE request based on content type
     * 
     * @param \Illuminate\Http\Client\PendingRequest $client
     * @param string $url
     * @return Response
     */
    private function _sendDeleteRequest($client, string $url): Response
    {
        if (empty($this->data)) {
            return $client->delete($url);
        }

        return $client->delete($url, is_array($this->data) ? $this->data : []);
    }

    /**
     * Prepare request data based on content type
     * 
     * @return array
     */
    private function _prepareRequestData(): array
    {
        return is_array($this->data) ? $this->data : [];
    }

    /**
     * Prepare multipart form data
     * 
     * @return array
     */
    private function _prepareMultipartData(): array
    {
        if (!is_array($this->data)) {
            return [];
        }

        $multipartData = [];
        foreach ($this->data as $key => $value) {
            if (is_string($value) && file_exists($value)) {
                $multipartData[] = [
                    'name' => $key,
                    'contents' => fopen($value, 'r'),
                    'filename' => basename($value)
                ];
            } else {
                $multipartData[$key] = $value;
            }
        }

        return $multipartData;
    }

    /**
     * Reset request parameters to initial state
     * Useful for making multiple requests with the same client instance
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->endpoint = '';
        $this->method = 'GET';
        $this->data = [];
        $this->headers = [];
        $this->queryParams = [];
        $this->contentType = 'application/json';
        $this->isFullUrl = false;
        $this->throwOnError = true;

        return $this;
    }
}