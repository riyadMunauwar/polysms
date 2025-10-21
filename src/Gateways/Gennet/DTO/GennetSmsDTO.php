<?php

namespace Riyad\PolySms\Gateways\Gennet\DTO;

use Riyad\PolySms\DTO\BaseDTO;

class GennetSmsDTO extends BaseDTO
{
    public ?string $type;
    public string $senderId;
    public string $to;
    public string $message;
}