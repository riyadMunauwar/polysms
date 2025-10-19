<?php

namespace Riyad\Polysms\Contracts;

use Riyad\Polysms\Contracts\GatewayContract;
use Riyad\Polysms\Contracts\BeforeSmsSentContract;
use Riyad\Polysms\DTO\BaseDTO;
use Riyad\Polysms\DTO\SmsResult;


interface SmsManagerContract
{
    public function register(string $name, callable $factory, array $meta = []): void;


    public function unregister(string $name): void;


    public function gateway(string $gateway): static;


    public function getGateway(string $gateway) : GatewayContract;


    public function send(BaseDTO $dto): SmsResult;


    public function map(callable $callback): array;


    public function filter(callable $callback): array;


    public function onBeforeSmsSent(string|callable|BeforeSmsSentContract $hook): void;
}