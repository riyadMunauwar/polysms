<?php

namespace Riyad\Polysms;

use Riyad\Polysms\Contracts\GatewayContract;
use Riyad\Polysms\DTO\SmsDTO;
use Riyad\Polysms\DTO\Config;
use Riyad\Polysms\DTO\SmsResult;

abstract class AbstractGateway implements GatewayContract
{
    abstract public function name(): string;


    abstract public function config(): Config;


    abstract public function send(SmsDTO $dto): SmsResult;
}