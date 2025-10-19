<?php

namespace Riyad\Polysms\Contracts;

use Riyad\Polysms\DTO\BaseDTO;
use Riyad\Polysms\DTO\SmsResult;
use Riyad\Polysms\DTO\Config;

interface GatewayContract
{
    public function name(): string;

    public function config(): Config;

    public function send(BaseDTO $dto): SmsResult;
}