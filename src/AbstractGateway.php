<?php

namespace Riyad\PolySms;

use Riyad\PolySms\Contracts\GatewayContract;
use Riyad\PolySms\DTO\BaseDTO;
use Riyad\PolySms\DTO\Config;
use Riyad\PolySms\DTO\SmsResult;

abstract class AbstractGateway implements GatewayContract
{
    abstract public function name(): string;

    abstract public function config(): Config;


    public function send(BaseDTO $dto): SmsResult
    {
        $className = get_class($this);

        throw new UnsupportedFeatureException("'{$className}' does not support this send() method.");
    }
}