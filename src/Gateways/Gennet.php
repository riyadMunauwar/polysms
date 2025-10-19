<?php 

namespace Riyad\Polysms\Gateways;

use Riyad\Polysms\AbstractGateway;
use Riyad\Polysms\DTO\Config;
use Riyad\Polysms\DTO\BaseDTO;
use Riyad\Polysms\DTO\SmsResult;
use Riyad\Polysms\GatewayRegistry;

class Gennet extends AbstractGateway
{
    private string $baseUrl;
    private string $apiKey;


    public function __construct()
    {
        $paystationGateway = GatewayRegistry::instance();
        $metaData = $paystationGateway->getMeta($this->name())['config'];

        $this->baseUrl = 'https://gbarta.gennet.com.bd/api/v1';
        $this->apiKey = $metaData->apiKey;
    }


    public function name(): string 
    {
        return 'giosms';
    }


    public function config(): Config 
    {
        return new Config([
            'displayName' => 'GioSMS',
            'description' => 'Description',
            'logoUrl' => 'logoUrl',
        ]);
    }


    public function send(BaseDTO $dto): SmsResult 
    {
        $data = [
            'api_key'     => $this->apiKey,
            'type'        => $dto->type ?? 'text',
            'senderid'    => $dto->senderId,
            'msg'         => $dto->message,
            'numbers'     => $dto->to,
        ];


        try {
                         
            $response = $this->apiCall($data);

            if($response['error'] ?? false && $response['error'] === true){
                return new SmsResult([
                    'success' => false,
                    'message' => 'Error',
                    'gatewayResponse' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            return new SmsResult([
                'success' => true,
                'message' => 'Sms successfully submited to gennet server.',
                'gatewayResponse' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(\RuntimeException $ex) {
            return new SmsResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        } catch (\Exception $ex) {
            return new SmsResult([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

        }
        
    }


    private function apiCall(array $data, array $headers = []): array
    {
        $url = $this->baseUrl . '/smsapi';

        $curl = curl_init();

        if (!$curl) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        // Convert POST fields to URL-encoded format if needed
        $postFields = http_build_query($data);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Set a reasonable timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$curlError}");
        }

        // Decode JSON response safely
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("API request failed with status {$httpCode}: " . ($decodedResponse['message'] ?? $response));
        }

        return $decodedResponse;
    }

}