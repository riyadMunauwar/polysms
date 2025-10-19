<?php 

require_once 'vendor/autoload.php';

use Riyad\Polysms\DTO\Config;
use Riyad\Polysms\DTO\GennetGatewayConfig;
use Riyad\Polysms\DTO\BaseDTO;
use Riyad\Polysms\DTO\GennetSmsDTO;
use Riyad\Polysms\SmsManager;
use Riyad\Polysms\GatewayRegistry;
use Riyad\Polysms\Gateways\Gennet;
use Riyad\Polysms\Contracts\BeforeSmsSentContract;
use Riyad\Polysms\Contracts\HookContract;
use Riyad\Polysms\HookRegistry;


$registry = GatewayRegistry::init();
$hookRegistry = HookRegistry::init();


$manager = SmsManager::init($registry, $hookRegistry);

$manager->register('giosms', function(){
    return new Gennet();
}, ['config' => new GennetGatewayConfig(['apiKey' => 'XC4s0LA/gCBoPRIy'])]);


class CreateTransction implements BeforeSmsSentContract
{
    public function handle(BaseDTO $dto, string $gatewayName) : BaseDTO
    {
        var_dump('Continue...');
        return $dto;
    }
}

$manager->onBeforeSmsSent(CreateTransction::class);



$sms = new GennetSmsDTO([
    'senderId' => '8809612342019',
    'to' => '01794263387',
    'message' => 'Running test...',
]);

$res = $manager->gateway('giosms')->send($sms);


var_dump($res);





