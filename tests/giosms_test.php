<?php

require_once '../vendor/autoload.php';

use Riyad\PolySms\Gateways\GioSms\GioSms;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsSendDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsStatusDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsCheckBalanceDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsBatchReportDTO;
use Riyad\PolySms\Gateways\GioSms\DTO\GioSmsBatchHistoryDTO;
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

// Register GioSMS gateway
$manager->register('giosms', function () {
    return (new GioSms())->verifySsl(false)->withToken('1|oLfWXcTzQooHm6igvgxHb26O0ZdVhz7g3yDu68LG41503051');
});

// Shorthand — reused across tests
$giosms = $manager->gateway('giosms');

// -------------------------------------------------------------------------
// Config — fill before running
// -------------------------------------------------------------------------

$config = [
    'api_token' => '',
    'sender_id' => '',
    'phone'     => '',   // single recipient
    'phones'    => '', // bulk
];

echo "=== GIOSMS GATEWAY TESTS ===\n\n";

// ============================================
// TEST 1: Send Single SMS (Transactional)
// ============================================
echo "TEST 1: Send Single SMS (Transactional)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Hello from GioSMS - Single Transactional Test',
    'senderId' => $config['sender_id'],
    'type'     => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// Capture message_id for TEST 4
$singleMessageId = $giosms->getMessageId($result);

// ============================================
// TEST 2: Send Single SMS (OTP)
// ============================================
echo "TEST 2: Send Single SMS (OTP)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Your OTP is 4521. Valid for 5 minutes.',
    'senderId' => $config['sender_id'],
    'type'     => 'otp',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Is Single Send: " . ($giosms->isSingleSend($result) ? 'Yes' : 'No') . "\n";
echo "Message ID: " . ($giosms->getMessageId($result) ?? 'N/A') . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 3: Send Single SMS (Promotional)
// ============================================
echo "TEST 3: Send Single SMS (Promotional)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Flash sale! 50% off today only. Visit giosoft.com',
    'senderId' => $config['sender_id'],
    'type'     => 'promotional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 4: Check Single SMS Delivery Status
// ============================================
echo "TEST 4: Check Single SMS Delivery Status\n";
echo str_repeat('-', 50) . "\n";

$messageId = $singleMessageId ?? 'msg_abc123def456'; // fallback to a sample ID

$result = $giosms->checkStatus(new GioSmsStatusDTO([
    'messageId' => $messageId,
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 5: Send Bulk SMS
// ============================================
echo "TEST 5: Send Bulk SMS\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phones'],
    'message'  => 'Bulk test from GioSMS. Flash sale! 50% off today.',
    'senderId' => $config['sender_id'],
    'type'     => 'promotional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Is Bulk Send: " . ($giosms->isBulkSend($result) ? 'Yes' : 'No') . "\n";
echo "Batch ID: " . ($giosms->getBatchId($result) ?? 'N/A') . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// Capture batch_id for TEST 7 & 8
$bulkBatchId = $giosms->getBatchId($result);

// ============================================
// TEST 6: Send SMS to Contact IDs
// ============================================
echo "TEST 6: Send SMS to Contact IDs\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'contactIds' => [1, 2, 3],
    'message'    => 'Hello from GioSMS - Contact based send test',
    'senderId'   => $config['sender_id'],
    'type'       => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Is Bulk Send: " . ($giosms->isBulkSend($result) ? 'Yes' : 'No') . "\n";
echo "Batch ID: " . ($giosms->getBatchId($result) ?? 'N/A') . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 7: Send SMS to Group IDs
// ============================================
echo "TEST 7: Send SMS to Group IDs\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'groupIds' => [1, 2],
    'message'  => 'Hello from GioSMS - Group based send test',
    'senderId' => $config['sender_id'],
    'type'     => 'promotional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Is Bulk Send: " . ($giosms->isBulkSend($result) ? 'Yes' : 'No') . "\n";
echo "Batch ID: " . ($giosms->getBatchId($result) ?? 'N/A') . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 8: Send SMS with Template ID
// ============================================
echo "TEST 8: Send SMS with Template ID\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'         => $config['phone'],
    'templateId' => 5,
    'senderId'   => $config['sender_id'],
    'type'       => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 9: Check Balance (Basic)
// ============================================
echo "TEST 9: Check Balance (Basic)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->checkBalance(new GioSmsCheckBalanceDTO());

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 10: Check Balance with Cost Estimate
// ============================================
echo "TEST 10: Check Balance with Cost Estimate\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->checkBalance(new GioSmsCheckBalanceDTO([
    'message' => 'Hello from GioSMS! This is a test message for cost estimation.',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 11: Check Balance with Unicode Message Estimate
// ============================================
echo "TEST 11: Check Balance with Unicode (Bangla) Cost Estimate\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->checkBalance(new GioSmsCheckBalanceDTO([
    'message' => 'হ্যালো, আপনি কেমন আছেন? এটি একটি পরীক্ষামূলক বার্তা।',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 12: Get Active Batches
// ============================================
echo "TEST 12: Get Active Batches\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->getActiveBatches();

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 13: Check Batch Report
// ============================================
echo "TEST 13: Check Batch Report\n";
echo str_repeat('-', 50) . "\n";

$batchId = $bulkBatchId ?? 'batch_m1abc_7kR4xWz9pQ2n'; // fallback to sample ID

$result = $giosms->checkBatchReport(new GioSmsBatchReportDTO([
    'batchId' => $batchId,
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 14: Get Batch History (Default limit)
// ============================================
echo "TEST 14: Get Batch History (Default limit: 20)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->getBatchHistory(new GioSmsBatchHistoryDTO());

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 15: Get Batch History (Custom limit)
// ============================================
echo "TEST 15: Get Batch History (Custom limit: 5)\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->getBatchHistory(new GioSmsBatchHistoryDTO([
    'limit' => 5,
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 16: Test Helper Methods
// ============================================
echo "TEST 16: Test Helper Methods\n";
echo str_repeat('-', 50) . "\n";

$singleResult = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Helper method test message',
    'senderId' => $config['sender_id'],
    'type'     => 'transactional',
]));

$bulkResult = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phones'],
    'message'  => 'Helper method bulk test message',
    'senderId' => $config['sender_id'],
    'type'     => 'promotional',
]));

echo "--- Single Send Result ---\n";
echo "Is Successful:  " . ($giosms->isSuccessful($singleResult) ? 'Yes' : 'No') . "\n";
echo "Is Single Send: " . ($giosms->isSingleSend($singleResult) ? 'Yes' : 'No') . "\n";
echo "Is Bulk Send:   " . ($giosms->isBulkSend($singleResult) ? 'Yes' : 'No') . "\n";
echo "Message ID:     " . ($giosms->getMessageId($singleResult) ?? 'N/A') . "\n";
echo "Batch ID:       " . ($giosms->getBatchId($singleResult) ?? 'N/A') . "\n";

echo "\n--- Bulk Send Result ---\n";
echo "Is Successful:  " . ($giosms->isSuccessful($bulkResult) ? 'Yes' : 'No') . "\n";
echo "Is Single Send: " . ($giosms->isSingleSend($bulkResult) ? 'Yes' : 'No') . "\n";
echo "Is Bulk Send:   " . ($giosms->isBulkSend($bulkResult) ? 'Yes' : 'No') . "\n";
echo "Message ID:     " . ($giosms->getMessageId($bulkResult) ?? 'N/A') . "\n";
echo "Batch ID:       " . ($giosms->getBatchId($bulkResult) ?? 'N/A') . "\n\n";

// ============================================
// TEST 17: Test Hooks (Before / After SMS Sent)
// ============================================
echo "TEST 17: Test Hooks (Before / After SMS Sent)\n";
echo str_repeat('-', 50) . "\n";

$hook->addFilter(Hook::BEFORE_SMS_SENT, function ($dto) {
    echo "HOOK: Before SMS sent — appending hook tag to message\n";
    $dto->message = $dto->message . ' [Hook]';
    return $dto;
});

$hook->addAction(Hook::AFTER_SMS_SENT, function () {
    echo "HOOK: After SMS sent — logging completed\n";
});

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Hook test message',
    'senderId' => $config['sender_id'],
    'type'     => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n\n";

// ============================================
// TEST 18: Error Scenario — Invalid Token
// ============================================
echo "TEST 18: Error Scenario — Invalid Bearer Token\n";
echo str_repeat('-', 50) . "\n";

$invalidGateway = (new GioSms())->withToken('invalid_token_here');

$result = $invalidGateway->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'This should fail with 401',
    'senderId' => $config['sender_id'],
    'type'     => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 19: Error Scenario — Missing Token (LogicException)
// ============================================
echo "TEST 19: Error Scenario — Missing Bearer Token (LogicException)\n";
echo str_repeat('-', 50) . "\n";

try {
    $noTokenGateway = new GioSms();
    $noTokenGateway->send(new GioSmsSendDTO([
        'to'       => $config['phone'],
        'message'  => 'This should throw LogicException',
        'senderId' => $config['sender_id'],
    ]));
    echo "ERROR: Expected LogicException was not thrown!\n\n";
} catch (\LogicException $e) {
    echo "Correctly caught LogicException:\n";
    echo "  " . $e->getMessage() . "\n\n";
}

// ============================================
// TEST 20: Error Scenario — Invalid Sender ID
// ============================================
echo "TEST 20: Error Scenario — Invalid Sender ID\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->send(new GioSmsSendDTO([
    'to'       => $config['phone'],
    'message'  => 'Test with invalid sender ID',
    'senderId' => 'INVALID_SENDER_THAT_DOES_NOT_EXIST',
    'type'     => 'transactional',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 21: Error Scenario — Invalid Batch ID
// ============================================
echo "TEST 21: Error Scenario — Invalid Batch ID\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->checkBatchReport(new GioSmsBatchReportDTO([
    'batchId' => 'batch_invalid_does_not_exist',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// TEST 22: Error Scenario — Invalid Message ID
// ============================================
echo "TEST 22: Error Scenario — Invalid Message ID\n";
echo str_repeat('-', 50) . "\n";

$result = $giosms->checkStatus(new GioSmsStatusDTO([
    'messageId' => 'msg_invalid_does_not_exist',
]));

echo "Success: " . ($result->success ? 'Yes' : 'No') . "\n";
echo "Message: " . $result->message . "\n";
echo "Response: " . json_encode($result->response, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// Summary
// ============================================
echo "\n" . str_repeat('=', 50) . "\n";
echo "ALL TESTS COMPLETED\n";
echo str_repeat('=', 50) . "\n";