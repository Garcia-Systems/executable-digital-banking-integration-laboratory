<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Delivery;

final readonly class ReviewScenario { public function __construct(public string $id,public string $title,public string $change,public string $diff,public array $evidence,public string $verdict,public array $findings){} }
final class ReviewScenarioCatalog
{
    /** @return array<string,ReviewScenario> */
    public function all():array { $s=[
        new ReviewScenario('vendor-leak','Vendor identifier leak','Add northstarCustomerKey to the public member API.','+ "northstarCustomerKey": customer.key',['unit: PASS','api-data-check: FAIL'],'BLOCK',['A vendor/internal identifier is exposed without a client need.','Preserve the Harbor-owned public contract and data minimization.']),
        new ReviewScenario('sql-concatenation','SQL string concatenation','Build member SQL by concatenating MemberId.','+ WHERE member_id = \''.'" . $memberId . "',['unit: PASS','security: FAIL'],'BLOCK',['Variable SQL must remain parameterized.','This is a security regression at a database trust boundary.']),
        new ReviewScenario('transfer-threshold','Transfer threshold boundary','Change requested > available to requested >= available without policy or tests.','- requested > available\n+ requested >= available',['unit: PASS (old cases)','exact-balance case: MISSING'],'COMMENT',['Exact-balance preview semantics changed.','Clarify policy and add a boundary regression test before approval.']),
        new ReviewScenario('complete-api-field','Complete Harbor API field','Add an intentional Harbor-owned optional field with fixture, parser, tests, and documentation.','+ "preferredName": "Avery"',['unit: PASS','integration: PASS','contract: PASS','frontend: PASS','documentation: PASS'],'APPROVE',['The contract change is intentional and complete.','No blocker is identified in this teaching scenario.']),
        new ReviewScenario('mock-heavy-refactor','Mock-heavy refactor','Add interaction mocks without behavioral coverage.','+ expect(gateway).calledOnce() (many times)',['unit: PASS','new behavior covered: NO'],'COMMENT',['Mocks couple tests to implementation without proving new behavior.']),
        new ReviewScenario('raw-vendor-logging','Raw ClearVerify logging','Log the complete ClearVerify error response.','+ logger.error(response.body)',['unit: PASS','security: FAIL','data exposure: FAIL'],'BLOCK',['Raw vendor diagnostics violate data minimization.','Log only explicitly approved safe fields.']),
    ]; $out=[];foreach($s as $v)$out[$v->id]=$v;return $out; }
    public function get(string $id):ReviewScenario {return $this->all()[$id]??throw new \InvalidArgumentException("Unknown review scenario: {$id}");}
}
