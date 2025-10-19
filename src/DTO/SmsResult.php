<?php

namespace Riyad\Polysms\DTO;

class SmsResult extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?array $errors;
    public ?string $gateway;
}