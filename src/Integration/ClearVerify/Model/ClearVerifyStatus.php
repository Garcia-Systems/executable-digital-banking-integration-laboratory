<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify\Model;
enum ClearVerifyStatus:string { case PASS='PASS'; case MANUAL_REVIEW='MANUAL_REVIEW'; case FAIL='FAIL'; }
