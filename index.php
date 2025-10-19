<?php 

require_once 'vendor/autoload.php';

use Riyad\Polysms\DTO\Config;
use Riyad\Polysms\DTO\GioSmsGatewayConfig;
use Riyad\Polysms\DTO\SmsDTO;
use Riyad\Polysms\SmsManager;
use Riyad\Polysms\GatewayRegistry;
use Riyad\Polysms\Gateways\GioSms;
use Riyad\Polysms\Contracts\BeforeSmsSentContract;
use Riyad\Polysms\Contracts\HookContract;
use Riyad\Polysms\HookRegistry;


$registry = GatewayRegistry::init();
$hookRegistry = HookRegistry::init();


$manager = SmsManager::init($registry, $hookRegistry);

$manager->register('giosms', function(){
    return new GioSms();
}, ['config' => new GioSmsGatewayConfig(['apiKey' => '1746978236', 'apiSecret' => '8236'])]);


class CreateTransction implements BeforeSmsSentContract
{
    public function handle(SmsDTO $dto, string $gatewayName) : SmsDTO
    {
        var_dump('Continue...');
        $dto->message = '01794263387';
        return $dto;
    }
}

$manager->onBeforeSmsSent(CreateTransction::class);



$sms = new SmsDTO([
    'to' => '01794263387',
    'message' => 'Hello World',
]);

$res = $manager->gateway('giosms')->send($sms);


var_dump($res);





