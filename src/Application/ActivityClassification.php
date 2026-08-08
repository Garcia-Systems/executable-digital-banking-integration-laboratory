<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
enum ActivityClassification: string { case RECENTLY_ACTIVE='RECENTLY_ACTIVE'; case INACTIVE='INACTIVE'; case NEVER_ACTIVE='NEVER_ACTIVE'; }
