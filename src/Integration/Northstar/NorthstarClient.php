<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomer, NorthstarCustomerKey};

interface NorthstarClient
{
    public function findCustomer(NorthstarCustomerKey $key): NorthstarCustomer;
}
