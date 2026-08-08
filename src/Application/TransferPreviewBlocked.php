<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
final class TransferPreviewBlocked extends \RuntimeException { public function __construct(public readonly TransferPreviewBlockReason $reason){parent::__construct($reason->value);} }
