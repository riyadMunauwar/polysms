<?php

namespace Riyad\PolySms\Gateways\GioSms\DTO;

use Riyad\PolySms\DTO\BaseDTO;

/**
 * DTO for fetching a batch processing report via GioSMS.
 */
class GioSmsBatchReportDTO extends BaseDTO
{
    /** Batch ID returned from a bulk send response */
    public string $batchId;
}