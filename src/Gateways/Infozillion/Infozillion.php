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


class Infozillion extends AbstractGateway
{
    private string $sendSmsIPTSPUrl;

    private string $sendSmsMNOUrl;

    private ?Http $client;

    private SmsHook $hook;

    public function __construct()
    {
        $this->sendSmsIPTSPUrl = 'https://a2papiintl.mnpspbd.com/a2p-sms-iptsp/api/v1/send-sms';
        $this->sendSmsMNOUrl = 'https://a2papiintl.mnpspbd.com/a2p-sms/api/v1/send-sms';
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
            'description' => 'MNP Bangladesh',
            'logoUrl' => '#',
        ]);
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

            if($response['serverResponseCode'] ?? false && $response['serverResponseCode'] != 9000){
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
                'message' => 'Sms successfully submited to Infozillion server.',
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