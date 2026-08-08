<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
enum TransferPreviewBlockReason:string { case VERIFICATION_REVIEW_REQUIRED='VERIFICATION_REVIEW_REQUIRED'; case VERIFICATION_REQUIRED='VERIFICATION_REQUIRED'; }
