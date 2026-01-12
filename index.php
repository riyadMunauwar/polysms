<?php 

require_once 'vendor/autoload.php';

use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetGatewayConfig;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionSmsDTO;
use Riyad\PolySms\Gateways\Infozillion\Infozillion;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetSmsDTO;
use Riyad\PolySms\SmsManager;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\Gateways\Gennet\Gennet;
use Riyad\PolySms\HookRegistry;
use Riyad\PolySms\SmsHook;
use Riyad\PolySms\Constants\Hook;
use Riyad\PolySms\Client\Http;

$hook = SmsHook::instance();

$registry = GatewayRegistry::init();

$manager = SmsManager::init($registry);

$manager->register('gennet', function(){
    return new Gennet();
});

$manager->register('infozillion', function(){
    return new Infozillion();
});






// $hook->addFilter(Hook::BEFORE_SMS_SENT, function($dto){
//     var_dump('Before sent');

//     return $dto;
// });
// $hook->addAction(Hook::AFTER_SMS_SENT, fn() => var_dump('Hello World'));

// $infozillionSms = [
//     'username' => '',
//     'password' => '',
//     'billMsisdn' => '',
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => '',
//     'cli' => '',
//     'transactionType' => '',
//     'messageType' => '',
//     'isLongSMS' => null,
//     'campaignId' => null,
//     'message' => null,
// ];

$sms = new InfozillionSmsDTO([
    'type' => 'mno',
    'username' => '',
    'password' => '',
    'billMsisdn' => '',
    'usernameSecondary' => null,
    'passwordSecondary' => null,
    'billMsisdnSecondary' => null,
    'apiKey' => '',
    'cli' => '',
    'msisdnList' => '',
    'transactionType' => 'T',
    'messageType' => '1',
    'isLongSMS' => null,
    'campaignId' => null,
    'message' => 'Hello fro giosms',
]);

// var_dump($sms->toArray());

$res = $manager->gateway('infozillion')->send($sms);

var_dump($res);





