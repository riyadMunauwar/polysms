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

    public function __construct(string $baseUrl = '', array $defaultHeaders = [], bool $verifySsl = true, int $timeout = 30)
    {
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
     * Prepare the request
     */
    public function request(
        string $endpoint,
        string $method = 'GET',
        $data = [],
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

        return $this;
    }

    /**
     * Get raw response
     */
    public function body(): string
    {
        return $this->_executeRequest(false);
    }

    /**
     * Get JSON-decoded response
     */
    public function json(): array
    {
        return $this->_executeRequest(true);
    }

    /**
     * Build full URL with query parameters
     */
    private function _buildUrl(): string
    {
        $url = $this->baseUrl . '/' . ltrim($this->endpoint, '/');
        if (!empty($this->queryParams)) {
            $url .= '?' . http_build_query($this->queryParams);
        }
        return $url;
    }

    /**
     * Prepare headers
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
     * Set request body
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
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($this->data) ? $this->data : json_encode($this->data));
                break;

            default:
                throw new HttpException("Unsupported Content-Type: {$this->contentType}");
        }
    }

    /**
     * Execute cURL request and return raw or JSON response
     */
    private function _executeRequest(bool $asJson)
    {
        $ch = curl_init();
        if (!$ch) {
            throw new HttpException('Failed to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->_buildUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->_prepareHeaders(),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_CUSTOMREQUEST => $this->method,
        ]);

        $this->_setRequestBody($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new HttpException("cURL error: {$curlError}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new HttpException("HTTP error: {$httpCode} - {$response}", $httpCode);
        }

        if ($asJson) {
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpException("Invalid JSON response: " . json_last_error_msg());
            }
            return $decoded;
        }

        return $response;
    }
}