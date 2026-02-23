<?php

namespace Riyad\PolySms\Gateways\GioSms\DTO;

use Riyad\PolySms\DTO\BaseDTO;

/**
 * DTO for fetching batch history via GioSMS.
 */
class GioSmsBatchHistoryDTO extends BaseDTO
{
    /** @var int Number of batches to return. Default: 20, Max: 100. */
    public int $limit = 20;
}