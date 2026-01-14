<?php

namespace Riyad\PolySms\Gateways\Infozillion\DTO;

use Riyad\PolySms\DTO\BaseDTO;

class InfozillionSmsDTO extends BaseDTO
{
    public string $type;
    public string $username;
    public string $password;
    public string $billMsisdn;
    public ?string $usernameSecondary = null;
    public ?string $passwordSecondary = null;
    public ?string $billMsisdnSecondary = null;
    public string $apiKey;
    public string $cli;
    public array $msisdnList;
    public string $transactionType;
    public string $messageType;
    public ?bool $isLongSMS = false;
    public ?string $campaignId = null;
    public string $message;
}