<?php 

namespace Riyad\PolySms\Gateways\Gennet;

use Riyad\PolySms\AbstractGateway;
use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\Client\Http;
use Riyad\PolySms\Client\HttpException;
use Riyad\PolySms\SmsHook;
use Riyad\PolySms\Constants\Hook;


class Gennet extends AbstractGateway
{
    private string $apiKey;

    private ?Http $client;

    private SmsHook $hook;


    public function __construct()
    {
        $registry = GatewayRegistry::instance();
        $metaData = $registry->getMeta($this->name())['config'];

        $this->apiKey = $metaData->apiKey;
        $this->client = new Http('https://gbarta.gennet.com.bd/api/v1', verifySsl: false);
        $this->hook = SmsHook::instance();
    }


    public function name(): string 
    {
        return 'gennet';
    }


    public function config(): Config 
    {
        return new Config([
            'displayName' => 'Gennet',
            'description' => 'Description',
            'logoUrl' => 'logoUrl',
        ]);
    }


    public function send(BaseDTO $dto): SmsResult 
    {
        $dto = $this->hook->applyFilters(Hook::BEFORE_SMS_SENT, $dto);

        $data = [
            'api_key'     => $this->apiKey,
            'type'        => $dto->type ?? 'text',
            'senderid'    => $dto->senderId,
            'msg'         => $dto->message,
            'numbers'     => $dto->to,
        ];

        try {
                         
            $response = $this->client->request(endpoint: '/smsapi', method: 'POST', data: $data, contentType: 'application/json')->json();

            if($response['error'] ?? false && $response['error'] === true){
                return new SmsResult([
                    'success' => false,
                    'message' => 'Error',
                    'gatewayResponse' => $response,
                    'gateway' => $this->name(),
                ]);
            }

            $this->hook->doAction(Hook::AFTER_SMS_SENT);

            return new SmsResult([
                'success' => true,
                'message' => 'Sms successfully submited to gennet server.',
                'gatewayResponse' => $response,
                'gateway' => $this->name(),
            ]);

        } catch(\HttpException $ex) {
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
}