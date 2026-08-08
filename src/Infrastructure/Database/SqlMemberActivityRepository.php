<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Database;

use Harbor\DigitalBankingLab\Application\{AccountActivity, InactiveMemberSummary, MemberActivityFacts, MemberActivityRepository, OperationalAccount, OperationalMember};
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, AccountStatus, AccountType, MemberId, MembershipStatus, Money};

final readonly class SqlMemberActivityRepository implements MemberActivityRepository
{
    public const MEMBER_ACCOUNTS_SQL = 'SELECT account_id, member_id, display_name, account_type, account_status FROM accounts WHERE member_id = :member_id ORDER BY account_id';
    public const ACTIVITY_SINCE_SQL = 'SELECT activity_id, account_id, activity_type, occurred_at, amount_minor_units FROM account_activity WHERE account_id = :account_id AND occurred_at >= :cutoff ORDER BY occurred_at, activity_id';
    public const INACTIVE_MEMBERS_SQL = 'SELECT m.member_id, m.display_name FROM members m WHERE NOT EXISTS (SELECT 1 FROM accounts a JOIN account_activity aa ON aa.account_id = a.account_id WHERE a.member_id = m.member_id AND aa.occurred_at >= :cutoff) ORDER BY m.member_id';
    public const INCORRECT_INACTIVITY_SQL = 'SELECT DISTINCT m.member_id FROM members m JOIN accounts a ON a.member_id = m.member_id JOIN account_activity aa ON aa.account_id = a.account_id WHERE aa.occurred_at < :cutoff ORDER BY m.member_id';
    public const ACTIVITY_FACTS_SQL = 'SELECT m.member_id, m.display_name, COUNT(DISTINCT a.account_id) AS account_count, COUNT(aa.activity_id) AS activity_count, COALESCE(SUM(CASE WHEN aa.occurred_at >= :cutoff THEN 1 ELSE 0 END), 0) AS recent_activity_count, MAX(aa.occurred_at) AS most_recent_activity_at FROM members m LEFT JOIN accounts a ON a.member_id = m.member_id LEFT JOIN account_activity aa ON aa.account_id = a.account_id GROUP BY m.member_id, m.display_name ORDER BY m.member_id';
    public function __construct(private \PDO $pdo) {}
    public function members(): array
    {
        $rows = $this->pdo->query('SELECT member_id, display_name, membership_status, created_at FROM members ORDER BY member_id')->fetchAll();
        return array_map(fn(array $r) => new OperationalMember(new MemberId($r['member_id']), $r['display_name'], $this->enum(MembershipStatus::class, $r['membership_status']), new \DateTimeImmutable($r['created_at'])), $rows);
    }
    public function accountsFor(MemberId $memberId): array
    {
        $s=$this->pdo->prepare(self::MEMBER_ACCOUNTS_SQL); $s->execute(['member_id'=>$memberId->value]);
        return array_map(fn(array $r) => new OperationalAccount(new AccountId($r['account_id']), new MemberId($r['member_id']), $r['display_name'], $this->enum(AccountType::class,$r['account_type']), $this->enum(AccountStatus::class,$r['account_status'])), $s->fetchAll());
    }
    public function activitySince(AccountId $accountId, \DateTimeImmutable $cutoff): array
    {
        $s=$this->pdo->prepare(self::ACTIVITY_SINCE_SQL); $s->execute(['account_id'=>$accountId->value,'cutoff'=>$this->instant($cutoff)]);
        return array_map(fn(array $r) => new AccountActivity($r['activity_id'],new AccountId($r['account_id']),$r['activity_type'],new \DateTimeImmutable($r['occurred_at']),$r['amount_minor_units']===null?null:Money::usd((int)$r['amount_minor_units'])), $s->fetchAll());
    }
    public function mostRecentActivityByAccount(): array
    {
        $rows=$this->pdo->query('SELECT a.account_id, MAX(aa.occurred_at) AS most_recent_at FROM accounts a LEFT JOIN account_activity aa ON aa.account_id = a.account_id GROUP BY a.account_id ORDER BY a.account_id')->fetchAll();
        $out=[]; foreach($rows as $r) $out[$r['account_id']]=$r['most_recent_at']===null?null:new \DateTimeImmutable($r['most_recent_at']); return $out;
    }
    public function inactiveMembers(\DateTimeImmutable $cutoff): array
    {
        $s=$this->pdo->prepare(self::INACTIVE_MEMBERS_SQL);$s->execute(['cutoff'=>$this->instant($cutoff)]);
        return array_map(fn(array $r)=>new InactiveMemberSummary(new MemberId($r['member_id']),$r['display_name']),$s->fetchAll());
    }
    public function incorrectInactiveMemberIds(\DateTimeImmutable $cutoff): array { $s=$this->pdo->prepare(self::INCORRECT_INACTIVITY_SQL);$s->execute(['cutoff'=>$this->instant($cutoff)]);return $s->fetchAll(\PDO::FETCH_COLUMN); }
    public function totalActivityAmount(AccountId $accountId): ?Money { $s=$this->pdo->prepare('SELECT SUM(amount_minor_units) FROM account_activity WHERE account_id = :account_id AND amount_minor_units IS NOT NULL');$s->execute(['account_id'=>$accountId->value]);$v=$s->fetchColumn();return $v===null?null:Money::usd((int)$v); }
    public function activityFacts(\DateTimeImmutable $cutoff): array { $s=$this->pdo->prepare(self::ACTIVITY_FACTS_SQL);$s->execute(['cutoff'=>$this->instant($cutoff)]);return array_map($this->facts(...),$s->fetchAll()); }
    public function activityFactsFor(MemberId $memberId, \DateTimeImmutable $cutoff): ?MemberActivityFacts { foreach($this->activityFacts($cutoff) as $facts) if($facts->memberId->equals($memberId)) return $facts; return null; }
    public function explainInactiveMembers(\DateTimeImmutable $cutoff): array { $s=$this->pdo->prepare('EXPLAIN QUERY PLAN '.self::INACTIVE_MEMBERS_SQL);$s->execute(['cutoff'=>$this->instant($cutoff)]);return $s->fetchAll(); }
    private function instant(\DateTimeImmutable $v): string { return $v->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'); }
    private function facts(array $r): MemberActivityFacts { return new MemberActivityFacts(new MemberId($r['member_id']),$r['display_name'],(int)$r['account_count'],(int)$r['activity_count'],(int)$r['recent_activity_count'],$r['most_recent_activity_at']===null?null:new \DateTimeImmutable($r['most_recent_activity_at'])); }
    /** @template T of \BackedEnum @param class-string<T> $class @return T */ private function enum(string $class,string $value): \BackedEnum { try{return $class::from($value);}catch(\ValueError){throw new \UnexpectedValueException("Unsupported stored {$class} value: {$value}");} }
}
