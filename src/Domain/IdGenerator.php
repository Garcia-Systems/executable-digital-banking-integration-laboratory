<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

interface IdGenerator
{
    public function nextId(): string;

    /** Inspect the next value without advancing the generator. */
    public function peekNextId(): string;
}
