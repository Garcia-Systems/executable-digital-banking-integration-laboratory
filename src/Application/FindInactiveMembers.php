<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
final readonly class FindInactiveMembers
{
    public function __construct(private MemberActivityRepository $repository) {}
    /** @return list<InactiveMemberSummary> */
    public function execute(\DateTimeImmutable $cutoff): array { return $this->repository->inactiveMembers($cutoff); }
}
