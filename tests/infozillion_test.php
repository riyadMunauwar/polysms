<?php

require_once '../vendor/autoload.php';

use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionSmsDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckDeliveryDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckAnsBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCpBalanceDTO;
use Riyad\PolySms\Gateways\Infozillion\DTO\InfozillionCheckCliDTO;
use Riyad\PolySms\Gateways\Infozillion\Infozillion;
use Riyad\PolySms\SmsManager;
use Riyad\PolySms\GatewayRegistry;
use Riyad\PolySms\SmsHook;
use Riyad\PolySms\Constants\Hook;

// -------------------------------------------------------------------------
// Bootstrap
// -------------------------------------------------------------------------

$hook     = SmsHook::instance();
$registry = GatewayRegistry::init();
$manager  = SmsManager::init($registry);

$manager->register('infozillion', function () {
    return (new Infozillion())->verifySsl(false);
});

$infozillion = $manager->gateway('infozillion');

// -------------------------------------------------------------------------
// Config — fill before running
// -------------------------------------------------------------------------

$config = [
    'username'   => '',
    'password'   => '',
    'billMsisdn' => '',
    'apiKey'     => '',
    'cli'        => '',
    'phone'      => '',
];

echo "=== INFOZILLION SMS GATEWAY TESTS ===\n\n";

// ============================================
// TEST 1: Send SMS (IPTSP Type)
// ============================================
echo "TEST 1: Send SMS (IPTSP Type)\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'message'         => 'Hello from PolySMS - IPTSP Test',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// Capture serverReference for delivery check in TEST 5
$serverReference = $result->response['serverReference'] ?? null;

// ============================================
// TEST 2: Send SMS (MNO Type)
// ============================================
echo "TEST 2: Send SMS (MNO Type)\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'mno',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'message'         => 'Hello from PolySMS - MNO Test',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 3: Send Unicode Bangla SMS
// ============================================
echo "TEST 3: Send Unicode Bangla SMS\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '3',
    'isLongSMS'       => false,
    'message'         => 'হ্যালো, আপনি কেমন আছেন?',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 4: Send Long SMS
// ============================================
echo "TEST 4: Send Long SMS\n";
echo str_repeat('-', 50) . "\n";

$longMessage = str_repeat('This is a long message test. ', 10);

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'isLongSMS'       => true,
    'message'         => $longMessage,
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Message Length: " . strlen($longMessage) . " characters\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 5: Check Delivery Report
// ============================================
echo "TEST 5: Check Delivery Report\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->checkDelivery(new InfozillionCheckDeliveryDTO([
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'msisdnList'      => [$config['phone']],
    'serverReference' => $serverReference ?? '9c1981f4-a31b-4937-a800-7212075f7ad3',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 6: Check ANS Balance (MNO)
// ============================================
echo "TEST 6: Check ANS Balance (Grameenphone)\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->checkAnsBalance(new InfozillionCheckAnsBalanceDTO([
    'username' => $config['username'],
    'password' => $config['password'],
    'apiKey'   => $config['apiKey'],
    'mno'      => 'gp', // Options: gp, robi, banglalink, teletalk
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 7: Check CP Balance
// ============================================
echo "TEST 7: Check CP Balance\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->checkCpBalance(new InfozillionCheckCpBalanceDTO([
    'apiKey' => $config['apiKey'],
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 8: Check CLI Availability
// ============================================
echo "TEST 8: Check CLI Availability\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->checkCli(new InfozillionCheckCliDTO([
    'username' => $config['username'],
    'password' => $config['password'],
    'apiKey'   => $config['apiKey'],
    'mno'      => 'gp',
    'cli'      => $config['cli'],
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 9: Test Helper Methods
// ============================================
echo "TEST 9: Test Helper Methods\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'message'         => 'Helper method test',
]));

echo "Is Successful:         " . ($infozillion->isSuccessful($result) ? 'Yes' : 'No') . "\n";
echo "Error Message:         " . $infozillion->getErrorMessage($result) . "\n";
echo "Response Code:         " . ($infozillion->getResponseCode($result) ?? 'N/A') . "\n";
echo "Is Insufficient Bal:   " . ($infozillion->isInsufficientBalance($result) ? 'Yes' : 'No') . "\n";
echo "Is Invalid Credentials:" . ($infozillion->isInvalidCredentials($result) ? 'Yes' : 'No') . "\n";
echo "Is Invalid CLI:        " . ($infozillion->isInvalidCli($result) ? 'Yes' : 'No') . "\n";
echo "Is Account Barred:     " . ($infozillion->isAccountBarred($result) ? 'Yes' : 'No') . "\n";
echo "Is IP Blacklisted:     " . ($infozillion->isIpBlacklisted($result) ? 'Yes' : 'No') . "\n";
echo "Is Invalid Content:    " . ($infozillion->isInvalidContent($result) ? 'Yes' : 'No') . "\n";
echo "Is Limit Exceeded:     " . ($infozillion->isLimitExceeded($result) ? 'Yes' : 'No') . "\n\n";

// ============================================
// TEST 10: Test Hooks (Before / After SMS Sent)
// ============================================
echo "TEST 10: Test Hooks (Before / After SMS Sent)\n";
echo str_repeat('-', 50) . "\n";

$hook->addFilter(Hook::BEFORE_SMS_SENT, function ($dto) {
    echo "HOOK: Before SMS sent — appending hook tag to message\n";
    $dto->message = $dto->message . ' [Hook]';
    return $dto;
});

$hook->addAction(Hook::AFTER_SMS_SENT, function () {
    echo "HOOK: After SMS sent — logging completed\n";
});

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'message'         => 'Hook test message',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n\n";

// ============================================
// TEST 11: Send Flash SMS (English)
// ============================================
echo "TEST 11: Send Flash SMS (English)\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '2',
    'isLongSMS'       => false,
    'message'         => 'This is a flash message!',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 12: Send Promotional SMS with Campaign ID
// ============================================
echo "TEST 12: Send Promotional SMS with Campaign ID\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'mno',
    'username'        => $config['username'],
    'password'        => $config['password'],
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'P',
    'messageType'     => '1',
    'campaignId'      => 'YOUR_CAMPAIGN_ID_HERE',
    'message'         => 'Promotional message with campaign',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 13: Error Scenario — Invalid Credentials
// ============================================
echo "TEST 13: Error Scenario — Invalid Credentials\n";
echo str_repeat('-', 50) . "\n";

$result = $infozillion->send(new InfozillionSmsDTO([
    'type'            => 'iptsp',
    'username'        => 'invalid_username',
    'password'        => 'invalid_password',
    'billMsisdn'      => $config['billMsisdn'],
    'apiKey'          => $config['apiKey'],
    'cli'             => $config['cli'],
    'msisdnList'      => [$config['phone']],
    'transactionType' => 'T',
    'messageType'     => '1',
    'message'         => 'Error test',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Is Invalid Credentials: " . ($infozillion->isInvalidCredentials($result) ? 'Yes' : 'No') . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// Summary
// ============================================
echo "\n" . str_repeat('=', 50) . "\n";
echo "ALL INFOZILLION TESTS COMPLETED\n";
echo str_repeat('=', 50) . "\n";