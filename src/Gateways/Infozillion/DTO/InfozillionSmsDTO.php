<?php

namespace Riyad\PolySms\Gateways\Infozillion\DTO;

use Riyad\PolySms\DTO\BaseDTO;

class InfozillionSmsDTO extends BaseDTO
{
    public string $type;
    public string $username;
    public string $password;
    public string $billMsisdn;
    public ?string $usernameSecondary;
    public ?string $passwordSecondary;
    public ?string $billMsisdnSecondary;
    public string $apiKey;
    public string $cli;
    public array $msisdnList;
    public string $transactionType;
    public string $messageType;
    public ?bool $isLongSMS;
    public ?string $campaignId;
    public string $message;
}