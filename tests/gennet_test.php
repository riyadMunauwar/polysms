<?php

require_once '../vendor/autoload.php';

use Riyad\PolySms\Gateways\Gennet\Gennet;
use Riyad\PolySms\Gateways\Gennet\DTO\GennetSmsDTO;
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

$manager->register('gennet', function () {
    return new Gennet();
});

$gennet = $manager->gateway('gennet');

// -------------------------------------------------------------------------
// Config — fill before running
// -------------------------------------------------------------------------

$config = [
    'sender_id' => 'MyBrand',
    'phone'     => '8801794263387',
];

echo "=== GENNET SMS GATEWAY TESTS ===\n\n";

// ============================================
// TEST 1: Send Single SMS (Text)
// ============================================
echo "TEST 1: Send Single SMS (Text)\n";
echo str_repeat('-', 50) . "\n";

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => $config['sender_id'],
    'to'       => $config['phone'],
    'message'  => 'Hello from PolySMS - Gennet Text Test',
    'type'     => 'text',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 2: Send Unicode Bangla SMS
// ============================================
echo "TEST 2: Send Unicode Bangla SMS\n";
echo str_repeat('-', 50) . "\n";

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => $config['sender_id'],
    'to'       => $config['phone'],
    'message'  => 'হ্যালো, আপনি কেমন আছেন? এটি একটি পরীক্ষামূলক বার্তা।',
    'type'     => 'unicode',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 3: Send SMS (Default type)
// ============================================
echo "TEST 3: Send SMS (Default type — omit type field)\n";
echo str_repeat('-', 50) . "\n";

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => $config['sender_id'],
    'to'       => $config['phone'],
    'message'  => 'Hello from PolySMS - Default type test',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 4: Test Hooks (Before / After SMS Sent)
// ============================================
echo "TEST 4: Test Hooks (Before / After SMS Sent)\n";
echo str_repeat('-', 50) . "\n";

$hook->addFilter(Hook::BEFORE_SMS_SENT, function ($dto) {
    echo "HOOK: Before SMS sent — appending hook tag to message\n";
    $dto->message = $dto->message . ' [Hook]';
    return $dto;
});

$hook->addAction(Hook::AFTER_SMS_SENT, function () {
    echo "HOOK: After SMS sent — logging completed\n";
});

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => $config['sender_id'],
    'to'       => $config['phone'],
    'message'  => 'Hook test message',
    'type'     => 'text',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n\n";

// ============================================
// TEST 5: verifySsl disabled (dev/local only)
// ============================================
echo "TEST 5: Send SMS with SSL Verification Disabled\n";
echo str_repeat('-', 50) . "\n";

$result = (new Gennet())
    ->verifySsl(false)
    ->send(new GennetSmsDTO([
        'senderId' => $config['sender_id'],
        'to'       => $config['phone'],
        'message'  => 'Hello from PolySMS - SSL disabled test',
        'type'     => 'text',
    ]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 6: Error Scenario — Invalid Sender ID
// ============================================
echo "TEST 6: Error Scenario — Invalid Sender ID\n";
echo str_repeat('-', 50) . "\n";

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => 'INVALID_SENDER_THAT_DOES_NOT_EXIST',
    'to'       => $config['phone'],
    'message'  => 'This should fail with invalid sender',
    'type'     => 'text',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 7: Error Scenario — Invalid Phone Number
// ============================================
echo "TEST 7: Error Scenario — Invalid Phone Number\n";
echo str_repeat('-', 50) . "\n";

$result = $gennet->send(new GennetSmsDTO([
    'senderId' => $config['sender_id'],
    'to'       => '000000000000',
    'message'  => 'This should fail with invalid number',
    'type'     => 'text',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// Summary
// ============================================
echo "\n" . str_repeat('=', 50) . "\n";
echo "ALL GENNET TESTS COMPLETED\n";
echo str_repeat('=', 50) . "\n";