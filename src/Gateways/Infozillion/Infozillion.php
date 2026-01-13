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
        $this->sendSmsIPTSPUrl = 'https://a2papiintl.mnpspbd.com/a2p-sms-iptsp/api/v1/send-sms';
        $this->sendSmsMNOUrl = 'https://a2papiintl.mnpspbd.com/a2p-sms/api/v1/send-sms';
        $this->checkDeliveryMNOUrl = 'https://a2papiintl.mnpspbd.com/a2p-proxy-api/api/v1/check-delivery-report';
        $this->checkDeliveryIPTSPUrl = 'https://a2papiintl.mnpspbd.com/a2p-proxy-api-iptsp/api/v1/check-delivery-report';
        $this->checkAnsBalanceMNOUrl = 'https://a2papiintl.mnpspbd.com/a2p-proxy-api/api/v1/check-credit-balance';
        $this->checkAnsBalanceIPTSPUrl = 'https://a2papiintl.mnpspbd.com/a2p-proxy-api-iptsp/api/v1/check-credit-balance';
        $this->checkCliMNOUrl = 'https://a2papiintl.mnpspbd.com/a2p-proxy-api/api/v1/check-cli';
        $this->checkCpBalanceUrl = 'https://a2papiintl.mnpspbd.com/a2p-wallet/api/v1/check-current-balance';
        
        $this->client = new Http('https://a2papiintl.mnpspbd.com', verifySsl: false)->throwOnError(false);
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
     * Get error message based on response code
     * Checks both A2P and ANS error code mappings
     *
     * @param string $code Response code
     * @param string|null $fallbackMessage Fallback message if code not found
     * @return string
     */
    private function getErrorMessageByCode(string $code, ?string $fallbackMessage = null): string
    {
        // Check A2P codes first (9xxx series)
        if (isset(self::A2P_ERROR_MESSAGES[$code])) {
            return self::A2P_ERROR_MESSAGES[$code];
        }
        
        // Check ANS codes (1xxx series)
        if (isset(self::ANS_ERROR_MESSAGES[$code])) {
            return self::ANS_ERROR_MESSAGES[$code];
        }
        
        return $fallbackMessage ?? 'Unknown error';
    }

    /**
     * Parse response and return appropriate message
     *
     * @param array $response API response
     * @return string
     */
    private function parseResponseMessage(array $response): string
    {
        $code = $response['serverResponseCode'] ?? $response['code'] ?? null;
        $serverMessage = $response['serverResponseMessage'] ?? $response['message'] ?? null;
        
        if ($code) {
            $knownMessage = $this->getErrorMessageByCode($code);
            
            // If we have additional details from server, append them
            if ($serverMessage && $serverMessage !== $knownMessage) {
                return $knownMessage . ': ' . $serverMessage;
            }
            
            return $knownMessage;
        }
        
        return $serverMessage ?? 'Unknown error';
    }

    public function send(BaseDTO $dto): SmsResult 
    {
        $dto = $this->hook->applyFilters(Hook::BEFORE_SMS_SENT, $dto);

        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'billMsisdn' => $dto->billMsisdn,
            'usernameSecondary' => $dto?->usernameSecondary,
            'passwordSecondary' => $dto?->passwordSecondary,
            'billMsisdnSecondary' => $dto?->billMsisdnSecondary,
            'apiKey' => $dto->apiKey,
            'cli' => $dto->cli,
            'msisdnList' => $dto->msisdnList,
            'transactionType' => $dto->transactionType,
            'messageType' => $dto->messageType,
            'isLongSMS' => $dto?->isLongSMS,
            'campaignId' => $dto?->campaignId,
            'message' => $dto->message,
        ];

        $smsSendApiUrl = $dto->type === 'iptsp' ? $this->sendSmsIPTSPUrl : $this->sendSmsMNOUrl;

        try {
            $response = $this->client->request(endpoint: $smsSendApiUrl, method: 'POST', data: $data, contentType: 'application/json')->json();

            if(($response['serverResponseCode'] ?? false) && $response['serverResponseCode'] != '9000'){
                return new SmsResult([
                    'success' => false,
                    'message' => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            $this->hook->doAction(Hook::AFTER_SMS_SENT);

            return new SmsResult([
                'success' => true,
                'message' => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway' => $this->name(),
            ]);
 
        } catch(HttpException $ex) {
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
     * Check delivery report for sent SMS
     *
     * @param InfozillionCheckDeliveryDTO $dto
     * @return SmsResult
     */
    public function checkDelivery(InfozillionCheckDeliveryDTO $dto): SmsResult
    {
        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'billMsisdn' => $dto->billMsisdn,
            'usernameSecondary' => $dto?->usernameSecondary,
            'passwordSecondary' => $dto?->passwordSecondary,
            'billMsisdnSecondary' => $dto?->billMsisdnSecondary,
            'apiKey' => $dto->apiKey,
            'msisdnList' => $dto->msisdnList,
            'serverReference' => $dto->serverReference,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkDeliveryMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if(($response['serverResponseCode'] ?? false) && $response['serverResponseCode'] != '9000'){
                return new SmsResult([
                    'success' => false,
                    'message' => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new SmsResult([
                'success' => true,
                'message' => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(HttpException $ex) {
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
     * Check ANS (MNO/IPTSP) balance
     *
     * @param InfozillionCheckAnsBalanceDTO $dto
     * @return SmsResult
     */
    public function checkAnsBalance(InfozillionCheckAnsBalanceDTO $dto): SmsResult
    {
        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'mno' => $dto->mno,
            'apiKey' => $dto->apiKey,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkAnsBalanceMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if(($response['serverResponseCode'] ?? false) && $response['serverResponseCode'] != '9000'){
                return new SmsResult([
                    'success' => false,
                    'message' => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new SmsResult([
                'success' => true,
                'message' => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(HttpException $ex) {
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
     * Check CP (Content Provider/Aggregator) balance
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

            if(($response['serverResponseCode'] ?? false) && $response['serverResponseCode'] != '9000'){
                return new SmsResult([
                    'success' => false,
                    'message' => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new SmsResult([
                'success' => true,
                'message' => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(HttpException $ex) {
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
     * Check CLI availability status
     *
     * @param InfozillionCheckCliDTO $dto
     * @return SmsResult
     */
    public function checkCli(InfozillionCheckCliDTO $dto): SmsResult
    {
        $data = [
            'username' => $dto->username,
            'password' => $dto->password,
            'mno' => $dto->mno,
            'apiKey' => $dto->apiKey,
            'cli' => $dto->cli,
        ];

        try {
            $response = $this->client
                ->request(endpoint: $this->checkCliMNOUrl, method: 'POST', data: $data, contentType: 'application/json')
                ->json();

            if(($response['serverResponseCode'] ?? false) && $response['serverResponseCode'] != '9000'){
                return new SmsResult([
                    'success' => false,
                    'message' => $this->parseResponseMessage($response),
                    'response' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new SmsResult([
                'success' => true,
                'message' => $this->parseResponseMessage($response),
                'response' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(HttpException $ex) {
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
     * Helper method to check if response was successful
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isSuccessful(SmsResult $result): bool
    {
        return $result->success === true;
    }

    /**
     * Get error message from result
     *
     * @param SmsResult $result
     * @return string
     */
    public function getErrorMessage(SmsResult $result): string
    {
        return $result->message ?? 'Unknown error';
    }

    /**
     * Get gateway response data
     *
     * @param SmsResult $result
     * @return array|null
     */
    public function getGatewayResponse(SmsResult $result): ?array
    {
        return $result->gatewayResponse ?? null;
    }

    /**
     * Get response code from result
     *
     * @param SmsResult $result
     * @return string|null
     */
    public function getResponseCode(SmsResult $result): ?string
    {
        $response = $this->getGatewayResponse($result);
        return $response['serverResponseCode'] ?? $response['code'] ?? null;
    }

    /**
     * Check if error is due to insufficient balance
     * Works for both A2P (9003, 9004) and ANS (1008) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInsufficientBalance(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9003', '9004', '1008']);
    }

    /**
     * Check if error is due to invalid credentials
     * Works for both A2P (9002, 9005) and ANS (1002, 1003) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidCredentials(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9002', '9005', '1002', '1003']);
    }

    /**
     * Check if error is due to invalid CLI
     * Works for both A2P (9008) and ANS (1006) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidCli(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9008', '1006']);
    }

    /**
     * Check if account is barred
     * ANS error code 1007
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isAccountBarred(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return $code === '1007';
    }

    /**
     * Check if IP is blacklisted
     * Works for both A2P (9006) and ANS (1001) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isIpBlacklisted(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9006', '1001']);
    }

    /**
     * Check if message content is invalid
     * Works for both A2P (9013, 9014) and ANS (1019) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isInvalidContent(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9013', '9014', '1019']);
    }

    /**
     * Check if limit is exceeded
     * Works for A2P (9010) and ANS (1015, 1050, 1051, 1054) error codes
     *
     * @param SmsResult $result
     * @return bool
     */
    public function isLimitExceeded(SmsResult $result): bool
    {
        $code = $this->getResponseCode($result);
        return in_array($code, ['9010', '1015', '1050', '1051', '1054']);
    }
}