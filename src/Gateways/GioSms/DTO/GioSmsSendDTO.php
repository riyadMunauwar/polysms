<?php

namespace Riyad\PolySms\Gateways\GioSms\DTO;

use Riyad\PolySms\DTO\BaseDTO;

/**
 * DTO for sending a single or bulk SMS via GioSMS.
 *
 * Exactly one of `to`, `contactIds`, or `groupIds` must be provided.
 * Exactly one of `message` or `templateId` must be provided.
 */
class GioSmsSendDTO extends BaseDTO
{
    /** @var string|null Comma-separated phone number(s) with country code */
    public ?string $to = null;

    /** @var int[]|null Contact IDs stored in GioSMS account */
    public ?array $contactIds = null;

    /** @var int[]|null Group IDs stored in GioSMS account */
    public ?array $groupIds = null;

    /** @var string|null Message body. Max 1000 characters. */
    public ?string $message = null;

    /** @var int|null Pre-approved template ID */
    public ?int $templateId = null;

    /** Registered sender ID. Max 16 characters. */
    public string $senderId;

    /** @var string SMS type: otp | transactional | promotional */
    public string $type = 'transactional';
}