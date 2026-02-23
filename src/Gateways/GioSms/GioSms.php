<?php

declare(strict_types=1);

namespace Riyad\PolySms\Gateways\GioSms;

use Riyad\PolySms\AbstractGateway;
use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;
use Riyad\PolySms\Constants\Hook;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsBatchHistoryDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsBatchReportDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsCheckBalanceDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsSendDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsStatusDTO;
use Riyad\PolySms\SmsHook;

class GioSms extends AbstractGateway
{
    private const BASE_URL = 'https://api.giosms.com';

    private const ENDPOINT_SEND    = '/api/v1/send';
    private const ENDPOINT_STATUS  = '/api/v1/status';
    private const ENDPOINT_BALANCE = '/api/v1/balance';
    private const ENDPOINT_BATCH   = '/api/v1/batch';

    private Http $client;
    private SmsHook $hook;
    private bool $tokenSet = false;

    public function __construct()
    {
        $this->client = (new Http(self::BASE_URL))->throwOnError(false);
        $this->hook   = SmsHook::instance();
    }

    /**
     * Set the Bearer token for authentication.
     *
     * Must be called before any API method.
     * Returns $this for fluent chaining:
     *
     *   $gateway->withToken($token)->send($dto);
     */
    public function withToken(string $apiToken): static
    {
        $this->client->setBearerToken($apiToken);
        $this->tokenSet = true;
        return $this;
    }

    /**
     * Enable or disable SSL certificate verification.
     *
     * Disable only in local/dev environments against self-signed certs.
     * Always keep enabled (default) in production.
     *
     *   $gateway->withToken($token)->verifySsl(false)->send($dto);
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
        return 'giosms';
    }

    public function config(): Config
    {
        return new Config([
            'displayName' => 'GioSMS',
            'description' => 'GioSMS Bangladesh SMS Gateway',
            'logoUrl'     => '#',
        ]);
    }

    // -------------------------------------------------------------------------
    // Public API methods
    // -------------------------------------------------------------------------

    /**
     * Send a single or bulk SMS.
     *
     * Mode is determined automatically from the DTO:
     *   - Single   : `to` with one phone number
     *   - Bulk     : `to` with comma-separated numbers
     *   - Contacts : `contactIds` array
     *   - Groups   : `groupIds` array
     *
     * Single sends return `message_id`; bulk/contact/group return `batch_id`.
     *
     * @param GioSmsSendDTO $dto
     * @return SmsResult
     */
    public function send(BaseDTO $dto): SmsResult
    {
        $this->ensureToken();

        $dto = $this->hook->applyFilters(Hook::BEFORE_SMS_SENT, $dto);

        $result = $this->post(self::ENDPOINT_SEND, $this->buildSendPayload($dto));

        if ($result->success) {
            $this->hook->doAction(Hook::AFTER_SMS_SENT);
        }

        return $result;
    }

    /**
     * Get the delivery status of a single SMS by its message ID.
     *
     * @param GioSmsStatusDTO $dto
     * @return SmsResult
     */
    public function checkStatus(GioSmsStatusDTO $dto): SmsResult
    {
        $this->ensureToken();

        return $this->get(self::ENDPOINT_STATUS . '/' . rawurlencode($dto->messageId));
    }

    /**
     * Check account balance, optionally with a cost estimate for a given message.
     *
     * @param GioSmsCheckBalanceDTO $dto
     * @return SmsResult
     */
    public function checkBalance(GioSmsCheckBalanceDTO $dto): SmsResult
    {
        $this->ensureToken();

        $queryParams = $dto->message !== null && $dto->message !== ''
            ? ['message' => $dto->message]
            : [];

        return $this->get(self::ENDPOINT_BALANCE, $queryParams);
    }

    /**
     * Get the processing report for a bulk send batch.
     *
     * Returns live stats while processing; final counters once completed.
     *
     * @param GioSmsBatchReportDTO $dto
     * @return SmsResult
     */
    public function checkBatchReport(GioSmsBatchReportDTO $dto): SmsResult
    {
        $this->ensureToken();

        return $this->get(self::ENDPOINT_BATCH . '/' . rawurlencode($dto->batchId));
    }

    /**
     * List all currently in-progress batches for the authenticated user.
     *
     * @return SmsResult
     */
    public function getActiveBatches(): SmsResult
    {
        $this->ensureToken();

        return $this->get(self::ENDPOINT_BATCH . '/active');
    }

    /**
     * Get historical batch list, most recent first.
     *
     * @param GioSmsBatchHistoryDTO $dto
     * @return SmsResult
     */
    public function getBatchHistory(GioSmsBatchHistoryDTO $dto): SmsResult
    {
        $this->ensureToken();

        return $this->get(self::ENDPOINT_BATCH . '/history', ['limit' => $dto->limit]);
    }

    // -------------------------------------------------------------------------
    // Convenience helpers on SmsResult
    // -------------------------------------------------------------------------

    public function isSuccessful(SmsResult $result): bool
    {
        return $result->success === true;
    }

    /** Whether the send response contains a batch_id (bulk/contact/group send). */
    public function isBulkSend(SmsResult $result): bool
    {
        return isset($result->response['batch_id']);
    }

    /** Whether the send response contains a message_id (single send). */
    public function isSingleSend(SmsResult $result): bool
    {
        return isset($result->response['message_id']);
    }

    /** Extract message_id from a single-send result. Returns null if absent. */
    public function getMessageId(SmsResult $result): ?string
    {
        return $result->response['message_id'] ?? null;
    }

    /** Extract batch_id from a bulk-send result. Returns null if absent. */
    public function getBatchId(SmsResult $result): ?string
    {
        return $result->response['batch_id'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Private HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a GET request and return a normalised SmsResult.
     *
     * @param array<string, mixed> $queryParams
     */
    private function get(string $endpoint, array $queryParams = []): SmsResult
    {
        return $this->execute('GET', $endpoint, queryParams: $queryParams);
    }

    /**
     * Execute a POST request and return a normalised SmsResult.
     *
     * @param array<string, mixed> $data
     */
    private function post(string $endpoint, array $data = []): SmsResult
    {
        return $this->execute('POST', $endpoint, data: $data);
    }

    /**
     * Core request executor — all public methods funnel through here.
     *
     * With throwOnError(false), the Http client never throws on non-2xx HTTP
     * responses — it always returns the decoded JSON body so API error payloads
     * (e.g. 401, 402, 429) flow through resultFromResponse() like any other
     * response. HttpException is only raised for cURL transport failures or
     * when the response body cannot be decoded as valid JSON.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $queryParams
     */
    private function execute(
        string $method,
        string $endpoint,
        array $data = [],
        array $queryParams = []
    ): SmsResult {
        try {
            $response = $this->client
                ->request(
                    endpoint: $endpoint,
                    method: $method,
                    data: $data,
                    queryParams: $queryParams,
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
     * Normalise a decoded API response body into a SmsResult.
     *
     * GioSMS always returns `{ success: bool, message: string, data?: {...} }`.
     *
     * On success  → `response` is populated with `data` (the useful payload).
     * On failure  → `response` is populated with the full body so callers can
     *               inspect raw error fields (e.g. validation details).
     */
    private function resultFromResponse(array $response): SmsResult
    {
        $success = (bool) ($response['success'] ?? false);

        return new SmsResult([
            'success'  => $success,
            'message'  => $response['message'] ?? ($success ? 'OK' : 'Unknown error'),
            'response' => $success ? ($response['data'] ?? $response) : $response,
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

    /**
     * Guard — throws a LogicException if withToken() was never called.
     *
     * Called at the top of every public API method so developers get a clear,
     * actionable error immediately rather than a cryptic 401 from the gateway.
     *
     * @throws \LogicException
     */
    private function ensureToken(): void
    {
        if (!$this->tokenSet) {
            throw new \LogicException(
                'GioSMS: Bearer token is not set. Call withToken($apiToken) before making any API request.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Private payload builders
    // -------------------------------------------------------------------------

    /**
     * Build the POST payload for the send endpoint.
     *
     * Exactly one recipient key (`to` / `contact_ids` / `group_ids`) and
     * one message key (`message` / `template_id`) are included.
     *
     * @return array<string, mixed>
     */
    private function buildSendPayload(GioSmsSendDTO $dto): array
    {
        $payload = [
            'sender_id' => $dto->senderId,
            'type'      => $dto->type,
        ];

        // Recipient — exactly one mode
        $payload = match (true) {
            $dto->to         !== null => $payload + ['to'          => $dto->to],
            $dto->contactIds !== null => $payload + ['contact_ids' => $dto->contactIds],
            $dto->groupIds   !== null => $payload + ['group_ids'   => $dto->groupIds],
            default                   => $payload,
        };

        // Message — text takes precedence over template
        $payload = match (true) {
            $dto->message    !== null => $payload + ['message'     => $dto->message],
            $dto->templateId !== null => $payload + ['template_id' => $dto->templateId],
            default                   => $payload,
        };

        return $payload;
    }
}