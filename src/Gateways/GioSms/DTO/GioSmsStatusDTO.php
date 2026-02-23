<?php

namespace Riyad\PolySms\Gateways\GioSms\DTO;

use Riyad\PolySms\DTO\BaseDTO;

/**
 * DTO for checking delivery status of a single SMS via GioSMS.
 */
class GioSmsStatusDTO extends BaseDTO
{
    /** Message ID returned from the send endpoint */
    public string $messageId;
}