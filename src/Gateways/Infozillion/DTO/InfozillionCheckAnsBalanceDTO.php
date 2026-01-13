<?php

namespace Riyad\PolySms\Gateways\Infozillion\DTO;

use Riyad\PolySms\DTO\BaseDTO;

class InfozillionCheckAnsBalanceDTO extends BaseDTO
{
    public string $username;
    public string $password;
    public string $mno;
    public string $apiKey;
}