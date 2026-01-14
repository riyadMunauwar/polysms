<?php

declare(strict_types=1);

namespace Riyad\PolySms\Client;

use Exception;
use Riyad\PolySms\Client\HttpException;

class Http
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

        // Append query parameters if any
        if (!empty($this->queryParams)) {
            $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
            $url .= $separator . http_build_query($this->queryParams);
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

        $formattedHeaders = [];
        foreach ($finalHeaders as $key => $value) {
            $formattedHeaders[] = "{$key}: {$value}";
        }

        return $formattedHeaders;
    }

    /**
     * Set request body based on content type
     * 
     * @param resource $ch cURL handle
     * @return void
     * @throws HttpException
     */
    private function _setRequestBody($ch): void
    {
        if (!in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        switch ($this->contentType) {
            case 'application/json':
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->data));
                break;

            case 'application/x-www-form-urlencoded':
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->data));
                break;

            case 'multipart/form-data':
                $postData = [];
                foreach ($this->data as $key => $value) {
                    if (is_string($value) && file_exists($value)) {
                        $postData[$key] = curl_file_create($value);
                    } else {
                        $postData[$key] = $value;
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                break;

            case 'text/plain':
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    is_string($this->data) ? $this->data : json_encode($this->data)
                );
                break;

            default:
                throw new HttpException("Unsupported Content-Type: {$this->contentType}");
        }
    }

    /**
     * Execute cURL request and return raw or JSON response
     * 
     * @param bool $asJson Whether to decode response as JSON
     * @param bool $withStatus Whether to return response with status code
     * @return string|array
     * @throws HttpException
     */
    private function _executeRequest(bool $asJson, bool $withStatus = false): string|array
    {
        $ch = curl_init();
        if (!$ch) {
            throw new HttpException('Failed to initialize cURL.');
        }

        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->_buildUrl(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->_prepareHeaders(),
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                CURLOPT_CUSTOMREQUEST => $this->method,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
            ]);

            $this->_setRequestBody($ch);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            if ($response === false) {
                throw new HttpException("cURL error: {$curlError}");
            }

            // Throw on HTTP errors if enabled (but not when using response() method)
            if (!$withStatus && $this->throwOnError && ($httpCode < 200 || $httpCode >= 300)) {
                throw new HttpException(
                    "HTTP error: {$httpCode} - {$response}",
                    $httpCode
                );
            }

            if ($asJson) {
                $decoded = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new HttpException(
                        "Invalid JSON response: " . json_last_error_msg()
                    );
                }
                return $decoded;
            }

            return $response;
        } finally {
            curl_close($ch);
        }
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