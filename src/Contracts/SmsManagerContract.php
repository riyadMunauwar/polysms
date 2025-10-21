<?php

namespace Riyad\PolySms\Contracts;

use Riyad\PolySms\Contracts\GatewayContract;

interface SmsManagerContract
{
    public function register(string $name, callable $factory, array $meta = []): void;

    public function unregister(string $name): void;

    public function gateway(string $gateway): GatewayContract;

    public function map(callable $callback): array;

    public function filter(callable $callback): array;
}