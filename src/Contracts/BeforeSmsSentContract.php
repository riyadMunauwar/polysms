<?php

namespace Riyad\Polysms\Contracts;

use Riyad\Polysms\DTO\SmsDTO;

/**
 * Interface BeforeSmsSentContract
 *
 * Defines a contract for hooks that should execute **before** a sms is sent.
 * Implementing classes must provide a `handle` method to modify or validate the SmsDTO.
 */
interface BeforeSmsSentContract
{
    /**
     * Handle a sms before it is sent by the gateway.
     *
     * This method is called prior to invoking the `send` method on the gateway.
     * Implementations can modify, validate, or enrich the Payment DTO as needed.
     *
     * @param SmsDTO $dto The sms data transfer object to be processed
     * @param string $gatewayName The name of the currently selected gateway
     * @return SmsDTO The potentially modified Sms DTO
     *
     * @throws \RuntimeException If pre-processing fails or validation errors occur
     */
    public function handle(SmsDTO $dto, string $gatewayName): SmsDTO;
}