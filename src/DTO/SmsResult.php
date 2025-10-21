<?php

namespace Riyad\PolySms\DTO;

class SmsResult extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?array $errors;
    public ?string $gateway;
}