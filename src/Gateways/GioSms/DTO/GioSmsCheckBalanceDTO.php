<?php

namespace Riyad\PolySms\Gateways\GioSms\DTO;

use Riyad\PolySms\DTO\BaseDTO;

/**
 * DTO for checking account balance via GioSMS.
 *
 * Optionally provide a message text to receive a cost estimate alongside balance.
 */
class GioSmsCheckBalanceDTO extends BaseDTO
{
    /** @var string|null Optional message text for cost estimation. Max 1000 characters. */
    public ?string $message = null;
}