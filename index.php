<?php 

require_once 'vendor/autoload.php';

use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetGatewayConfig;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetSmsDTO;
use Riyad\PolySms\SmsManager;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\Gateways\Gennet\Gennet;
use Riyad\PolySms\HookRegistry;
use Riyad\PolySms\SmsHook;
use Riyad\PolySms\Constants\Hook;

$hook = SmsHook::instance();

$registry = GatewayRegistry::init();



$manager = SmsManager::init($registry);

$manager->register('gennet', function(){
    return new Gennet();
}, ['config' => new GennetGatewayConfig(['apiKey' => '$2y$12$KdGu8CecaYTbmEmumKdPBe1v6Px7cOF3FoD.fXC4s0LA/gCBoPRIx'])]);


$hook->addFilter(Hook::BEFORE_SMS_SENT, function($dto){
    var_dump('Before sent');

    return $dto;
});
$hook->addAction(Hook::AFTER_SMS_SENT, fn() => var_dump('Hello World'));


$sms = new GennetSmsDTO([
    'senderId' => '8809612342019',
    'to' => '01794263387',
    'message' => 'Test SMS...',
]);

$res = $manager->gateway('gennet')->send($sms);


var_dump($res);





