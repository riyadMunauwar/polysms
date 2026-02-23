<?php 

namespace Riyad\PolySms\Gateways\Infozillion;

use Riyad\PolySms\AbstractGateway;
use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;
use Riyad\PolySms\SmsHook;
use Riyad\PolySms\Constants\Hook;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckDeliveryDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckAnsBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCpBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCliDTO;


class Infozillion extends AbstractGateway
{
    private string $sendSmsIPTSPUrl;
    private string $sendSmsMNOUrl;
    private string $checkDeliveryMNOUrl;
    private string $checkDeliveryIPTSPUrl;
    private string $checkAnsBalanceMNOUrl;
    private string $checkAnsBalanceIPTSPUrl;
    private string $checkCliMNOUrl;
    private string $checkCpBalanceUrl;

    private ?Http $client;
    private SmsHook $hook;

    /**
     * A2P API Response code to message mapping
     */
    private const A2P_ERROR_MESSAGES = [
        '9000' => 'Request successful',
        '9001' => 'Required field missing',
        '9002' => 'Client credentials mismatch',
        '9003' => 'Out of balance',
        '9004' => 'Insufficient balance',
        '9005' => 'Client not found',
        '9006' => 'Invalid source IP',
        '9007' => 'Invalid bill MSISDN',
        '9008' => 'Invalid CLI',
        '9009' => 'Missing destination MSISDN',
        '9010' => 'Max limit for destination MSISDN exceeds',
        '9011' => 'MNO server failure',
        '9012' => 'Dipping failure',
        '9013' => 'Invalid Content',
        '9014' => 'Invalid keyword',
        '9015' => 'DND server failure',
        '9016' => 'Invalid check delivery request',
        '9017' => 'Invalid check balance request',
        '9018' => 'Invalid check CLI request',
        '9019' => 'Invalid transaction type',
        '9020' => 'Invalid message type',
        '9099' => 'Server failure',
    ];

    /**
     * ANS API Response code to message mapping
     */
    private const ANS_ERROR_MESSAGES = [
        '1000' => 'Success',
        '1001' => 'IP Blacklist',
        '1002' => 'Invalid Username',
        '1003' => 'Invalid Password',
        '1004' => 'Parameter missing',
        '1005' => 'Invalid Parameter',
        '1006' => 'CLI/Masking Invalid',
        '1007' => 'Account Barred',
        '1008' => 'Insufficient Balance',
        '1009' => 'DND User',
        '1010' => 'Invalid MSISDN',
        '1011' => 'Duplicate Transaction ID',
        '1012' => 'Message lengths exceed',
        '1013' => 'No Request found',
        '1014' => 'Delivery pending',
        '1015' => 'TPS Limit Exceeded',
        '1016' => 'Number Barred',
        '1017' => 'API is not allowed for user',
        '1018' => 'No Live Campaign',
        '1019' => 'Messagebody Invalid',
        '1020' => 'Internal Server Error',
        '1021' => 'CLI/Masking Already Exists',
        '1050' => 'Allowed campaigns limit exceeded',
        '1051' => 'Allowed SMS quota is completed',
        '1052' => 'Submission record not found',
        '1053' => 'Invalid Transaction Id',
        '1054' => 'MSISDN Limit Exceeded',
    ];

    public function __construct()
    {
        $this->sendSmsIPTSPUrl = 'https://api.mnpspbd.com/a2p-sms-iptsp/api/v1/send-sms';
        $this->sendSmsMNOUrl = 'https://api.mnpspbd.com/a2p-sms/api/v1/send-sms';
        $this->checkDeliveryMNOUrl = 'https://api.mnpspbd.com/a2p-proxy-api/api/v1/check-delivery-report';
        $this->checkDeliveryIPTSPUrl = 'https://api.mnpspbd.com/a2p-proxy-api-iptsp/api/v1/check-delivery-report';
        $this->checkAnsBalanceMNOUrl = 'https://api.mnpspbd.com/a2p-proxy-api/api/v1/check-credit-balance';
        $this->checkAnsBalanceIPTSPUrl = 'https://api.mnpspbd.com/a2p-proxy-api-iptsp/api/v1/check-credit-balance';
        $this->checkCliMNOUrl = 'https://api.mnpspbd.com/a2p-proxy-api/api/v1/check-cli';
        $this->checkCpBalanceUrl = 'https://api.mnpspbd.com/a2p-wallet/api/v1/check-current-balance';
        
        $this->client = new Http('https://api.mnpspbd.com')->throwOnError(false);
        $this->hook = SmsHook::instance();
    }

    public function name(): string 
    {
        return 'infozillion';
    }

    public function config(): Config 
    {
        return new Config([
            'displayName' => 'Infozillion',
            'description' => 'Infozillion Bangladesh',
            'logoUrl' => '#',
        ]); 
    }

    /**
     * Determine whether a PROXY API response is successful.
     * (checkDelivery, checkAnsBalance, checkCpBalance, checkCli)
     *
     * These endpoints only have a single A2P layer — MNO codes are not
     * returned at send-time, so we only validate serverResponseCode === 9000.
     *
     * @param array $response Decoded JSON response
     * @return bool
     */
    private function isProxySuccessResponse(array $response): bool
    {
        if (!array_key_exists('serverResponseCode', $response)) {
            return false;
        }

        return (int) $response['serverResponseCode'] === 9000;
    }

    /**
     * Determine whether a SEND SMS response is a true end-to-end success.
     *
     * Two independent layers must both confirm success:
     *
     *   1. A2P / MNPSP layer  → serverResponseCode must be integer 9000
     *   2. MNO / ANS layer    → mnoResponseCode must be integer/string 1000
     *
     * If the MNO layer is absent (null) — which is expected for Promotional
     * SMS per the API spec — we do NOT fail the request; promotional sends
     * are queued asynchronously and mnoTxnId/mnoResponseCode are always null.
     * The caller must rely on checkDelivery() for final status in that case.
     *
     * For Transactional SMS the MNO layer is always populated synchronously,
     * so a missing/non-1000 mnoResponseCode is a genuine failure.
     *
     * @param array  $response         Decoded JSON from send-sms endpoint
     * @param string $transactionType  'T' for Transactional, 'P' for Promotional
     * @return bool
     */
    private function isSendSuccessResponse(array $response, string $transactionType): bool
    {
        // Gate 1: A2P layer must be 9000
        if (!array_key_exists('serverResponseCode', $response)) {
            return false;
        }

        if ((int) $response['serverResponseCode'] !== 9000) {
            return false;
        }

        // Gate 2: MNO layer — only validated for Transactional SMS.
        // Promotional responses always return null MNO fields by design.
        if (strtoupper($transactionType) === 'T') {
            $mnoCode = $response['mnoResponseCode'] ?? null;

            // Null MNO code on a transactional send means MNO never responded
            if ($mnoCode === null) {
                return false;
            }

            if ((int) $mnoCode !== 1000) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build an error message that surfaces both A2P and MNO failure reasons.
     *
     * Priority:
     *   - If A2P layer failed → report A2P error (MNO was never reached)
     *   - If A2P passed but MNO failed → report MNO error with A2P context
     *   - If both passed → return success message
     *
     * @param array $response Decoded JSON from send-sms endpoint
     * @return string
     */
    private function parseSendResponseMessage(array $response): string
    {
        $serverCode = array_key_exists('serverResponseCode', $response)
            ? (string) $response['serverResponseCode']
            : null;

        $mnoCode = array_key_exists('mnoResponseCode', $response) && $response['mnoResponseCode'] !== null
            ? (string) $response['mnoResponseCode']
            : null;

        // A2P layer failed — MNO was never reached
        if ($serverCode !== null && (int) $serverCode !== 9000) {
            $a2pMsg  = $this->getErrorMessageByCode($serverCode);
            $rawMsg  = $response['serverResponseMessage'] ?? null;

            return $rawMsg && $rawMsg !== $a2pMsg
                ? $a2pMsg . ': ' . $rawMsg
                : $a2pMsg;
        }

        // A2P passed but MNO layer returned an error
        if ($mnoCode !== null && (int) $mnoCode !== 1000) {
            $mnoMsg = $this->getErrorMessageByCode($mnoCode);
            $rawMnoMsg = $response['mnoResponseMessage'] ?? null;

            $detail = $rawMnoMsg && $rawMnoMsg !== $mnoMsg
                ? $mnoMsg . ': ' . $rawMnoMsg
                : $mnoMsg;

            return 'MNO Error — ' . $detail;
        }

        // MNO code absent on a transactional send (gateway never responded)
        if ($serverCode !== null && (int) $serverCode === 9000 && $mnoCode === null
            && array_key_exists('mnoResponseCode', $response)
        ) {
            return 'MNO Error — No response received from MNO';
        }

        // Both layers OK (or promotional with null MNO fields)
        return $this->getErrorMessageByCode($serverCode ?? '9000');
    }

    /**
     * Get error message based on response code.
     *
     * Normalises the code to string before lookup so that integer codes
     * (as returned by the real API) correctly resolve against the string-
     * keyed constants arrays.
     *
     * Checks A2P (9xxx) codes first, then ANS (1xxx) codes.
     *
     * @param string|int $code    Response code (string or int)
     * @param string|null $fallbackMessage Fallback if code is unrecognised
     * @return string
     */
    private function getErrorMessageByCode(string|int $code, ?string $fallbackMessage = null): string
    {
        $code = (string) $code;

        if (isset(self::A2P_ERROR_MESSAGES[$code])) {
            return self::A2P_ERROR_MESSAGES[$code];
        }

        if (isset(self::ANS_ERROR_MESSAGES[$code])) {
            return self::ANS_ERROR_MESSAGES[$code];
        }

        return $fallbackMessage ?? 'Unknown error';
    }

    /**
     * Parse response and return a human-readable status message.
     *
     * When the server provides its own message AND it differs from our
     * canonical mapping, both are joined so callers have full context.
     *
     * @param array $response API response
     * @return string
     */
    private function parseResponseMessage(array $response): string
    {
        // Prefer serverResponseCode; fall back to a generic 'code' key if present
        $code = array_key_exists('serverResponseCode', $response)
            ? (string) $response['serverResponseCode']
            : (array_key_exists('code', $response) ? (string) $response['code'] : null);

        $serverMessage = $response['serverResponseMessage'] ?? $response['message'] ?? null;

        if ($code !== null) {
            $knownMessage = $this->getErrorMessageByCode($code);

            if ($serverMessage && $serverMessage !== $knownMessage) {
                return $knownMessage . ': ' . $serverMessage;
            }

            return $knownMessage;
        }

        return $serverMessage ?? 'Unknown error';
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

    public function send(BaseDTO $dto): SmsResult 
    {
        $dto = $this->hook->applyFilters(Hook::BEFORE_SMS_SENT, $dto);

        $data = [
            'username'        => $dto->username,
            'password'        => $dto->password,
            'billMsisdn'      => $dto->billMsisdn,
            'apiKey'          => $dto->apiKey,
            'cli'             => $dto->cli,
            'msisdnList'      => $dto->msisdnList,
            'transactionType' => $dto->transactionType,
            'messageType'     => $dto->messageType,
            'isLongSMS'       => $dto?->isLongSMS,
            'message'         => $dto->message,
        ];

        if ($dto?->usernameSecondary && $dto?->passwordSecondary && $dto?->billMsisdnSecondary) {
            $data['usernameSecondary']    = $dto->usernameSecondary;
            $data['passwordSecondary']    = $dto->passwordSecondary;
            $data['billMsisdnSecondary']  = $dto->billMsisdnSecondary;
        }

        if ($dto?->campaignId) {
            $data['campaignId'] = $dto->campaignId;
        }

        $smsSendApiUrl = $dto->type === 'iptsp' ? $this->sendSmsIPTSPUrl : $this->sendSmsMNOUrl;

        try {
            $response = $this->client
                ->request(endpoint: $smsSendApiUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if (!$this->isSendSuccessResponse($response, $dto->transactionType)) {
                return new SmsResult([
                    'success'  => false,
                    'message'  => $this->parseSendResponseMessage($response),
                    'response' => $response,
                    'gateway'  => $this->name(),
                ]);
            }

            $this->hook->doAction(Hook::AFTER_SMS_SENT);

            return new SmsResult([
                'success'  => true,
                'message'  => $this->parseSendResponseMessage($response),
                'response' => $response,
                'gateway'  => $this->name(),
            ]);

        } catch (HttpException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'HTTP Error: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'Exception: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        }
    }

    /**
     * Check delivery report for sent SMS.
     *
     * @param InfozillionCheckDeliveryDTO $dto
     * @return SmsResult
     */
    public function checkDelivery(InfozillionCheckDeliveryDTO $dto): SmsResult
    {
        $data = [
            'username'        => $dto->username,
            'password'        => $dto->password,
            'billMsisdn'      => $dto->billMsisdn,
            'apiKey'          => $dto->apiKey,
            'msisdnList'      => $dto->msisdnList,
            'serverReference' => $dto->serverReference,
        ];

        if ($dto?->usernameSecondary && $dto?->passwordSecondary && $dto?->billMsisdnSecondary) {
            $data['usernameSecondary']   = $dto->usernameSecondary;
            $data['passwordSecondary']   = $dto->passwordSecondary;
            $data['billMsisdnSecondary'] = $dto->billMsisdnSecondary;
        }

        try {
            $response = $this->client
                ->request(endpoint: $this->checkDeliveryMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if (!$this->isProxySuccessResponse($response)) {
                return new SmsResult([
                    'success'  => false,
                    'message'  => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway'  => $this->name(),
                ]);
            }

            return new SmsResult([
                'success'  => true,
                'message'  => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway'  => $this->name(),
            ]);

        } catch (HttpException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'HTTP Error: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'Exception: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        }
    }

    /**
     * Check ANS (MNO/IPTSP) balance.
     *
     * @param InfozillionCheckAnsBalanceDTO $dto
     * @return SmsResult
     */
    public function checkAnsBalance(InfozillionCheckAnsBalanceDTO $dto): SmsResult
    {
        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'mno'      => $dto->mno,
            'apiKey'   => $dto->apiKey,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkAnsBalanceMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if (!$this->isProxySuccessResponse($response)) {
                return new SmsResult([
                    'success'  => false,
                    'message'  => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway'  => $this->name(),
                ]);
            }

            return new SmsResult([
                'success'  => true,
                'message'  => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway'  => $this->name(),
            ]);

        } catch (HttpException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'HTTP Error: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'Exception: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        }
    }

    /**
     * Check CP (Content Provider/Aggregator) balance.
     *
     * @param InfozillionCheckCpBalanceDTO $dto
     * @return SmsResult
     */
    public function checkCpBalance(InfozillionCheckCpBalanceDTO $dto): SmsResult
    {
        $data = [
            'apiKey' => $dto->apiKey,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkCpBalanceUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if (!$this->isProxySuccessResponse($response)) {
                return new SmsResult([
                    'success'  => false,
                    'message'  => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway'  => $this->name(),
                ]);
            }

            return new SmsResult([
                'success'  => true,
                'message'  => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway'  => $this->name(),
            ]);

        } catch (HttpException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'HTTP Error: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'Exception: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        }
    }

    /**
     * Check CLI availability status.
     *
     * @param InfozillionCheckCliDTO $dto
     * @return SmsResult
     */
    public function checkCli(InfozillionCheckCliDTO $dto): SmsResult
    {
        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'mno'      => $dto->mno,
            'apiKey'   => $dto->apiKey,
            'cli'      => $dto->cli,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkCliMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if (!$this->isProxySuccessResponse($response)) {
                return new SmsResult([
                    'success'  => false,
                    'message'  => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway'  => $this->name(),
                ]);
            }

            return new SmsResult([
                'success'  => true,
                'message'  => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway'  => $this->name(),
            ]);

        } catch (HttpException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'HTTP Error: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => 'Exception: ' . $ex->getMessage(),
                'gateway' => $this->name(),
            ]);
        }
    }

    /**
     * Helper method to check if response was successful.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isSuccessful(SmsResult $result): bool
    {
        return $result->success === true;
    }

    /**
     * Get error message from result.
     *
     * @param SmsResult $result
     * @return string
     */
    public function getErrorMessage(SmsResult $result): string
    {
        return $result->message ?? 'Unknown error';
    }

    /**
     * Get gateway response data.
     *
     * @param SmsResult $result
     * @return array|null
     */
    public function getGatewayResponse(SmsResult $result): ?array
    {
        return $result->gatewayResponse ?? null;
    }

    /**
     * Get response code from result.
     *
     * @param SmsResult $result
     * @return string|null
     */
    public function getResponseCode(SmsResult $result): ?string
    {
        $response = $this->getGatewayResponse($result);
        if ($response === null) {
            return null;
        }

        $code = $response['serverResponseCode'] ?? $response['code'] ?? null;
        return $code !== null ? (string) $code : null;
    }

    /**
     * Check if error is due to insufficient balance.
     * Covers A2P (9003, 9004) and ANS (1008) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInsufficientBalance(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9003', '9004', '1008'], true);
    }

    /**
     * Check if error is due to invalid credentials.
     * Covers A2P (9002, 9005) and ANS (1002, 1003) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidCredentials(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9002', '9005', '1002', '1003'], true);
    }

    /**
     * Check if error is due to invalid CLI.
     * Covers A2P (9008) and ANS (1006) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidCli(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9008', '1006'], true);
    }

    /**
     * Check if account is barred.
     * ANS error code 1007.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isAccountBarred(SmsResult $result): bool
    {
        return $this->getResponseCode($result) === '1007';
    }

    /**
     * Check if IP is blacklisted.
     * Covers A2P (9006) and ANS (1001) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isIpBlacklisted(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9006', '1001'], true);
    }

    /**
     * Check if message content is invalid.
     * Covers A2P (9013, 9014) and ANS (1019) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidContent(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9013', '9014', '1019'], true);
    }

    /**
     * Check if a limit has been exceeded.
     * Covers A2P (9010) and ANS (1015, 1050, 1051, 1054) codes.
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isLimitExceeded(SmsResult $result): bool
    {
        return in_array($this->getResponseCode($result), ['9010', '1015', '1050', '1051', '1054'], true);
    }
}