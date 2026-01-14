<?php

namespace Riyad\PolySms\Gateways\Infozillion\DTO;

use Riyad\PolySms\DTO\BaseDTO;

class InfozillionCheckDeliveryDTO extends BaseDTO
{
    public string $username;
    public string $password;
    public string $billMsisdn;
    public ?string $usernameSecondary;
    public ?string $passwordSecondary;
    public ?string $billMsisdnSecondary;
    public string $apiKey;
    public array $msisdnList;
    public string $serverReference;
}