<?php

namespace Riyad\PolySms\Contracts;

use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\SmsResult;
use Riyad\PolySms\DTO\Config;

interface GatewayContract
{
    public function name(): string;

    public function config(): Config;

    public function send(BaseDTO $dto): SmsResult;
}