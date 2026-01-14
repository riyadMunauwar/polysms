<?php 

require_once 'vendor/autoload.php';

use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetGatewayConfig;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionSmsDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckDeliveryDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckAnsBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCpBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCliDTO;
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

// Initialize components
$hook = SmsHook::instance();
$registry = GatewayRegistry::init();
$manager = SmsManager::init($registry);

// Register gateways
$manager->register('gennet', function(){
    return new Gennet();
});

$manager->register('infozillion', function(){
    return new Infozillion();
});

// Configuration for Infozillion
$config = [
    'username' => '',
    'password' => '',
    'billMsisdn' => '',
    'apiKey' => '',
    'cli' => '',
];

echo "=== INFOZILLION SMS GATEWAY TESTS ===\n\n";

// ============================================
// TEST 1: Send SMS (IPTSP Type)
// ============================================
echo "TEST 1: Send SMS (IPTSP Type)\n";
echo str_repeat("-", 50) . "\n";

$smsIPTSP = new InfozillionSmsDTO([
    'type' => 'iptsp',
    'username' => $config['username'],
    'password' => $config['password'],
    'billMsisdn' => $config['billMsisdn'],
    // 'usernameSecondary' => null,
    // 'passwordSecondary' => null,
    // 'billMsisdnSecondary' => null,
    'apiKey' => $config['apiKey'],
    'cli' => $config['cli'],
    'msisdnList' => ['8801794263387'],
    'transactionType' => 'T',
    'messageType' => '1',
    'isLongSMS' => null,
    // 'campaignId' => null,
    'message' => 'Hello from GioSMS - IPTSP Test',
]);

$result = $manager->gateway('infozillion')->send($smsIPTSP);

print_r($result);

// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 2: Send SMS (MNO Type)
// // ============================================
// echo "TEST 2: Send SMS (MNO Type)\n";
// echo str_repeat("-", 50) . "\n";

// $smsMNO = new InfozillionSmsDTO([
//     'type' => 'mno',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => null,
//     'campaignId' => null,
//     'message' => 'Hello from GioSMS - MNO Test',
// ]);

// $result = $manager->gateway('infozillion')->send($smsMNO);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 3: Send Unicode Bangla SMS
// // ============================================
// echo "TEST 3: Send Unicode Bangla SMS\n";
// echo str_repeat("-", 50) . "\n";

// $smsBangla = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'isLongSMS' => false,
//     'campaignId' => null,
//     'messageType' => '3', // Unicode Bangla
//     'message' => 'হ্যালো, আপনি কেমন আছেন?',
// ]);

// $result = $manager->gateway('infozillion')->send($smsBangla);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 4: Send Long SMS
// // ============================================
// echo "TEST 4: Send Long SMS\n";
// echo str_repeat("-", 50) . "\n";

// $longMessage = str_repeat("This is a long message test. ", 10); // Create a long message

// $smsLong = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => $longMessage,
// ]);

// $result = $manager->gateway('infozillion')->send($smsLong);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Message Length: " . strlen($longMessage) . " characters\n";
// echo "\n";

// // ============================================
// // TEST 5: Check Delivery Report
// // ============================================
// echo "TEST 5: Check Delivery Report\n";
// echo str_repeat("-", 50) . "\n";

// $checkDelivery = new InfozillionCheckDeliveryDTO([
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'msisdnList' => '01794263387',
//     'serverReference' => '9c1981f4-a31b-4937-a800-7212075f7ad3', // Replace with actual server reference from previous send
// ]);

// $infozillion = $manager->gateway('infozillion');
// $result = $infozillion->checkDelivery($checkDelivery);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 6: Check ANS Balance (MNO)
// // ============================================
// echo "TEST 6: Check ANS Balance (Grameenphone)\n";
// echo str_repeat("-", 50) . "\n";

// $checkAnsBalance = new InfozillionCheckAnsBalanceDTO([
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'mno' => 'gp', // Options: gp, robi, banglalink, teletalk
// ]);

// $result = $infozillion->checkAnsBalance($checkAnsBalance);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 7: Check CP Balance
// // ============================================
// echo "TEST 7: Check CP Balance\n";
// echo str_repeat("-", 50) . "\n";

// $checkCpBalance = new InfozillionCheckCpBalanceDTO([
//     'apiKey' => $config['apiKey'],
// ]);

// $result = $infozillion->checkCpBalance($checkCpBalance);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 8: Check CLI Availability
// // ============================================
// echo "TEST 8: Check CLI Availability\n";
// echo str_repeat("-", 50) . "\n";

// $checkCli = new InfozillionCheckCliDTO([
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'apiKey' => $config['apiKey'],
//     'mno' => 'gp',
//     'cli' => $config['cli'],
// ]);

// $result = $infozillion->checkCli($checkCli);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Gateway Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n";
// echo "\n";

// // ============================================
// // TEST 9: Test Helper Methods
// // ============================================
// echo "TEST 9: Test Helper Methods\n";
// echo str_repeat("-", 50) . "\n";

// // Send an SMS first to get a result
// $testSms = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => 'Helper method test',
// ]);

// $result = $infozillion->send($testSms);

// echo "Is Successful: " . ($infozillion->isSuccessful($result) ? 'Yes' : 'No') . "\n";
// echo "Error Message: " . $infozillion->getErrorMessage($result) . "\n";
// echo "Response Code: " . ($infozillion->getResponseCode($result) ?? 'N/A') . "\n";
// echo "Is Insufficient Balance: " . ($infozillion->isInsufficientBalance($result) ? 'Yes' : 'No') . "\n";
// echo "Is Invalid Credentials: " . ($infozillion->isInvalidCredentials($result) ? 'Yes' : 'No') . "\n";
// echo "Is Invalid CLI: " . ($infozillion->isInvalidCli($result) ? 'Yes' : 'No') . "\n";
// echo "Is Account Barred: " . ($infozillion->isAccountBarred($result) ? 'Yes' : 'No') . "\n";
// echo "Is IP Blacklisted: " . ($infozillion->isIpBlacklisted($result) ? 'Yes' : 'No') . "\n";
// echo "Is Invalid Content: " . ($infozillion->isInvalidContent($result) ? 'Yes' : 'No') . "\n";
// echo "Is Limit Exceeded: " . ($infozillion->isLimitExceeded($result) ? 'Yes' : 'No') . "\n";
// echo "\n";

// // ============================================
// // TEST 10: Test Hooks
// // ============================================
// echo "TEST 10: Test Hooks (Before/After SMS Sent)\n";
// echo str_repeat("-", 50) . "\n";

// // Add hooks
// $hook->addFilter(Hook::BEFORE_SMS_SENT, function($dto){
//     echo "HOOK: Before SMS sent - Modifying message\n";
//     $dto->message = $dto->message . " [Hook Modified]";
//     return $dto;
// });

// $hook->addAction(Hook::AFTER_SMS_SENT, function() {
//     echo "HOOK: After SMS sent - Logging completed\n";
// });

// $hookTestSms = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => 'Hook test message',
// ]);

// $result = $infozillion->send($hookTestSms);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message sent: " . $result->message . "\n";
// echo "\n";

// // ============================================
// // TEST 11: Test Multiple Recipients
// // ============================================
// echo "TEST 11: Test Multiple Recipients (IPTSP - Max 1)\n";
// echo str_repeat("-", 50) . "\n";

// $multiRecipient = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387', // IPTSP allows only 1
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => 'Multi recipient test',
// ]);

// $result = $infozillion->send($multiRecipient);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "\n";

// // ============================================
// // TEST 12: Test Error Scenarios
// // ============================================
// echo "TEST 12: Test Error Scenarios (Invalid Credentials)\n";
// echo str_repeat("-", 50) . "\n";

// $invalidSms = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => 'invalid_username',
//     'password' => 'invalid_password',
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => 'Error test',
// ]);

// $result = $infozillion->send($invalidSms);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "Is Invalid Credentials: " . ($infozillion->isInvalidCredentials($result) ? 'Yes' : 'No') . "\n";
// echo "\n";

// // ============================================
// // TEST 13: Test Flash SMS
// // ============================================
// echo "TEST 13: Test Flash SMS (English)\n";
// echo str_repeat("-", 50) . "\n";

// $flashSms = new InfozillionSmsDTO([
//     'type' => 'iptsp',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'T',
//     'messageType' => '2', // English Flash
//     'isLongSMS' => true,
//     'campaignId' => null,
//     'message' => 'This is a flash message!',
// ]);

// $result = $infozillion->send($flashSms);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "\n";

// // ============================================
// // TEST 14: Test with Campaign ID (Promotional)
// // ============================================
// echo "TEST 14: Test with Campaign ID (Promotional)\n";
// echo str_repeat("-", 50) . "\n";

// $campaignSms = new InfozillionSmsDTO([
//     'type' => 'mno',
//     'username' => $config['username'],
//     'password' => $config['password'],
//     'billMsisdn' => $config['billMsisdn'],
//     'usernameSecondary' => null,
//     'passwordSecondary' => null,
//     'billMsisdnSecondary' => null,
//     'apiKey' => $config['apiKey'],
//     'cli' => $config['cli'],
//     'msisdnList' => '01794263387',
//     'transactionType' => 'P', // Promotional
//     'messageType' => '1',
//     'isLongSMS' => true,
//     'campaignId' => 'YOUR_CAMPAIGN_ID_HERE', // Replace with actual campaign ID
//     'message' => 'Promotional message with campaign',
// ]);

// $result = $infozillion->send($campaignSms);
// echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
// echo "Message: " . $result->message . "\n";
// echo "\n";

// // ============================================
// // Summary
// // ============================================
// echo "\n" . str_repeat("=", 50) . "\n";
// echo "ALL TESTS COMPLETED\n";
// echo str_repeat("=", 50) . "\n";