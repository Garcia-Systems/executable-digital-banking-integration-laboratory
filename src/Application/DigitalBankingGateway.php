<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Member, MemberId};

/** A Harbor-owned capability contract; vendor and transport types stop below it. */
interface DigitalBankingGateway
{
    public function findMember(MemberId $memberId): Member;
}
