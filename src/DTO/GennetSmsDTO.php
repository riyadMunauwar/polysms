<?php

namespace Riyad\Polysms\DTO;

class GennetSmsDTO extends BaseDTO
{
    public ?string $type;
    public string $senderId;
    public string $to;
    public string $message;
}