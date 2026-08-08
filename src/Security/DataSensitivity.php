<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

enum DataSensitivity: string { case PUBLIC = 'PUBLIC'; case INTERNAL = 'INTERNAL'; case MEMBER_SENSITIVE = 'MEMBER_SENSITIVE'; case SECRET = 'SECRET'; }
