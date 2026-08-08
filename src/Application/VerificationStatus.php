<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
enum VerificationStatus:string { case VERIFIED='VERIFIED'; case REVIEW_REQUIRED='REVIEW_REQUIRED'; case NOT_VERIFIED='NOT_VERIFIED'; }
