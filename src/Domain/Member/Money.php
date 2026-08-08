<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

final readonly class Money
{
    private function __construct(public int $minorUnits, public string $currency)
    {
        if ($currency !== 'USD') {
            throw new \InvalidArgumentException('Harbor laboratory money supports USD only.');
        }
    }

    public static function usd(int $minorUnits): self
    {
        return new self($minorUnits, 'USD');
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Money currencies must match.');
        }

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Money currencies must match.');
        }

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function format(): string
    {
        $absolute = abs($this->minorUnits);
        $major = intdiv($absolute, 100);
        $minor = $absolute % 100;
        $sign = $this->minorUnits < 0 ? '-' : '';

        return sprintf('%s$%s.%02d', $sign, number_format($major, 0, '.', ','), $minor);
    }
}
