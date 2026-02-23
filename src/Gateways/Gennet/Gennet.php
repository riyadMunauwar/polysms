<?php

declare(strict_types=1);

namespace Riyad\PolySms\Gateways\Gennet;

use Riyad\PolySms\AbstractGateway;
use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;
use Riyad\PolySms\Constants\Hook;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\SmsHook;

class Gennet extends AbstractGateway
{
    private const BASE_URL = 'https://gbarta.gennet.com.bd/api/v1';

    private const ENDPOINT_SEND = '/smsapi';

    private string $apiKey;
    private Http $client;
    private SmsHook $hook;

    public function __construct()
    {
        $metaData = GatewayRegistry::instance()->getMeta($this->name())['config'];

        $this->apiKey = $metaData->apiKey;
        $this->client = (new Http(self::BASE_URL))->throwOnError(false);
        $this->hook   = SmsHook::instance();
    }

    // -------------------------------------------------------------------------
    // Fluent configuration helpers
    // -------------------------------------------------------------------------

    /**
     * Enable or disable SSL certificate verification.
     *
     * Disable only in local/dev environments against self-signed certs.
     * Always keep enabled (default) in production.
     *
     *   (new Gennet())->verifySsl(false)->send($dto);
     */
    public function verifySsl(bool $verify): static
    {
        $this->client->setSslVerification($verify);
        return $this;
    }

    // -------------------------------------------------------------------------
    // AbstractGateway contract
    // -------------------------------------------------------------------------

    public function name(): string
    {
        return 'gennet';
    }

    public function config(): Config
    {
        return new Config([
            'displayName' => 'Gennet',
            'description' => 'Gennet Bangladesh SMS Gateway',
            'logoUrl'     => '#',
        ]);
    }

    // -------------------------------------------------------------------------
    // Public API methods
    // -------------------------------------------------------------------------

    /**
     * Send an SMS via Gennet gateway.
     *
     * @param BaseDTO $dto Expected fields: senderId, message, to, type (optional)
     * @return SmsResult
     */
    public function send(BaseDTO $dto): SmsResult
    {
        $dto = $this->hook->applyFilters(Hook::BEFORE_SMS_SENT, $dto);

        $result = $this->post(self::ENDPOINT_SEND, [
            'api_key'  => $this->apiKey,
            'type'     => $dto->type ?? 'text',
            'senderid' => $dto->senderId,
            'msg'      => $dto->message,
            'numbers'  => $dto->to,
        ]);

        if ($result->success) {
            $this->hook->doAction(Hook::AFTER_SMS_SENT);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Private HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a POST request and return a normalised SmsResult.
     *
     * @param array<string, mixed> $data
     */
    private function post(string $endpoint, array $data = []): SmsResult
    {
        try {
            $response = $this->client
                ->request(
                    endpoint: $endpoint,
                    method: 'POST',
                    data: $data,
                    contentType: 'application/json'
                )
                ->json();

            return $this->resultFromResponse($response);

        } catch (HttpException $ex) {
            return $this->fail(
                message: 'HTTP Error: ' . $ex->getMessage(),
                response: ['error' => $ex->getMessage(), 'code' => $ex->getCode()]
            );
        } catch (\Exception $ex) {
            return $this->fail(
                message: 'Exception: ' . $ex->getMessage(),
                response: ['error' => $ex->getMessage()]
            );
        }
    }

    /**
     * Normalise a decoded Gennet API response into a SmsResult.
     *
     * Gennet signals failure with `{ "error": true, ... }`.
     * Any response without `error: true` is treated as success.
     */
    private function resultFromResponse(array $response): SmsResult
    {
        if (($response['error'] ?? false) === true) {
            return $this->fail(
                message: $response['message'] ?? 'Unknown error',
                response: $response
            );
        }

        return new SmsResult([
            'success'  => true,
            'message'  => $response['message'] ?? 'SMS successfully submitted to Gennet server',
            'response' => $response,
            'gateway'  => $this->name(),
        ]);
    }

    /**
     * Build a failed SmsResult.
     *
     * The `response` field is always populated so callers never receive null
     * and can inspect error context regardless of where the failure originated.
     *
     * @param array<string, mixed> $response
     */
    private function fail(string $message, array $response = []): SmsResult
    {
        return new SmsResult([
            'success'  => false,
            'message'  => $message,
            'response' => $response,
            'gateway'  => $this->name(),
        ]);
    }
}