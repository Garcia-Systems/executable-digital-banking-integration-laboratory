<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Debug;

final class DebugScenarioCatalog
{
    /** @return list<DebugScenario> */
    public function all(): array
    {
        $s = static fn(string $id,string $title,string $symptom,string $journey,string $fault,string $diagnosis,string $request,string $response,array $trace,string $boundary,array $detail,string $test) =>
            new DebugScenario($id,$title,$symptom,$journey,$fault,$diagnosis,'Select the scenario in DebugApplicationFactory; reset the fixture before every run.',$request,$response,$trace,$boundary,$detail,$test);
        return [
            $s('frontend-stale-member','Frontend stale response','selected member-0002 is replaced by member-0001','Member Web','BrokenRequestCoordinator','A stale completion is accepted','GET member-0001; GET member-0002','UI shows Avery Morgan (member-0001)',[
                '[PASS] selectedMember changed to member-0002','[PASS] member-0002 response committed','[FAIL] older member-0001 response committed'], 'Request-state coordination', ['Active request'=>'request-2','Late completion'=>'request-1','Invariant'=>'Only the active request may update state','Diagnostic'=>'STALE_COMPLETION_ACCEPTED'], 'state request-coordinator stale-response test'),
            $s('api-contract-drift','API field contract drift','HTTP 200 but Member Web shows load failure','Member summary','BrokenMemberSummaryFixture','Frontend runtime contract rejects the response','GET /api/members/member-0001','HTTP 200; runtime parser: invalid member summary',[
                '[PASS] route and HTTP transport','[PASS] response status 200','[FAIL] frontend/API contract validation'], 'Frontend/API contract', ['Expected field'=>'displayName','Observed field'=>'nameText','Parser result'=>'required field missing','Diagnostic'=>'MEMBER_SUMMARY_CONTRACT_MISMATCH'], 'shared member-summary contract fixture test'),
            $s('northstar-new-product-class','Northstar new product class','member summary unavailable','Member summary','FaultInjectingNorthstarTransport','Unsupported vendor semantic value','GET /api/members/member-0001','HTTP 502; upstream_invalid_response',[
                '[PASS] route, Harbor ID, and application service','[PASS] Northstar HTTP status 200','[PASS] JSON decoded','[FAIL] Northstar product translation'], 'Vendor semantic translation', ['Vendor field'=>'productClass','Observed value'=>'MMA','Supported values'=>'DDA, SAV','Diagnostic'=>'NORTHSTAR_UNSUPPORTED_PRODUCT_CLASS'], 'Northstar enum translation allow-list test'),
            $s('northstar-malformed-json','Northstar malformed JSON','member summary unavailable','Member summary','MalformedNorthstarTransport','JSON cannot be decoded','GET /api/members/member-0001','HTTP 502; upstream_invalid_response',[
                '[PASS] Northstar request and status 200','[FAIL] Northstar JSON decoding'], 'Northstar response decoding', ['Classification'=>'invalid JSON syntax','Operation'=>'customer lookup','Diagnostic'=>'NORTHSTAR_RESPONSE_DECODING_FAILURE'], 'Northstar malformed-response client test'),
            $s('heritage-soap-fault','Heritage SOAP Fault','available balances cannot load','Financial overview','SoapFaultHeritageTransport','SOAP Body contains a Fault','GET /api/members/member-0001/financial-overview','HTTP 502; upstream_service_error',[
                '[PASS] Harbor service and Heritage HTTP transport','[PASS] XML and SOAP Envelope parsed','[FAIL] SOAP operation result'], 'Heritage SOAP operation', ['Transport status'=>'200','Body classification'=>'SOAP Fault','Fault code'=>'Server','Diagnostic'=>'HERITAGE_SOAP_FAULT'], 'Heritage SOAP Fault-before-success test'),
            $s('heritage-malformed-xml','Heritage malformed XML','available balances cannot load','Financial overview','MalformedHeritageTransport','SOAP XML cannot be decoded','GET /api/members/member-0001/financial-overview','HTTP 502; upstream_invalid_response',[
                '[PASS] Heritage HTTP transport','[FAIL] XML decoding and structure'], 'Heritage XML decoding', ['Transport status'=>'200','Classification'=>'malformed XML','Diagnostic'=>'HERITAGE_RESPONSE_DECODING_FAILURE'], 'Heritage malformed-XML client test'),
            $s('sql-inactivity-predicate','SQL inactivity predicate','recently active member appears inactive','Activity review','IncorrectInactivitySqlRepository','Row predicate answers the wrong business question','activity-review --days=180','member-0001 appears in inactive result',[
                '[PASS] fixtures and cutoff parameter','[PASS] SQL executed','[FAIL] inactivity query semantics'], 'SQL business semantics', ['Fixture fact'=>'member has old and recent activity','Predicate'=>'activity.occurred_at < :cutoff','Required rule'=>'NOT EXISTS recent activity','Diagnostic'=>'SQL_INACTIVITY_ROW_PREDICATE'], 'old-plus-recent member NOT EXISTS regression'),
            $s('sql-missing-never-active-member','LEFT JOIN predicate pitfall','never-active member is absent from activity report','Activity review','WhereFilteredLeftJoinRepository','WHERE rejects the nullable joined row','activity-review --days=180','member-0003 missing from report',[
                '[PASS] member fixture exists','[PASS] LEFT JOIN executes','[FAIL] join/predicate semantics'], 'SQL JOIN/predicate semantics', ['Expected row'=>'member-0003 (no activity)','Observed'=>'row removed by nullable WHERE predicate','Diagnostic'=>'LEFT_JOIN_NULL_REJECTED'], 'never-active member activity report test'),
            $s('transfer-verification-review','Verification review policy','valid-looking transfer preview is rejected','Transfer preview','ManualReviewClearVerifyFixture','Verification policy blocks the workflow','POST /api/members/member-0001/transfer-preview','HTTP 422; review_required',[
                '[PASS] frontend and HTTP input validation','[PASS] ClearVerify response decoded and translated','[FAIL] transfer eligibility policy'], 'Application workflow policy', ['Harbor verification'=>'REVIEW_REQUIRED','Input validation'=>'passed','Workflow outcome'=>'blocked','Diagnostic'=>'TRANSFER_REVIEW_REQUIRED'], 'manual-review transfer policy test'),
            $s('money-decimal-conversion','Decimal conversion','12.34 is sent as 1233 minor units','Transfer preview','BrokenDecimalParserFixture','Frontend conversion loses one cent','POST transfer preview amount 1233','Request captured before Harbor processing',[
                '[PASS] text input accepted','[FAIL] frontend decimal-to-minor-unit transformation','[PASS] captured request reflects transformed value'], 'Frontend input transformation', ['Input'=>'12.34','Expected minor units'=>'1234','Observed minor units'=>'1233','Diagnostic'=>'DECIMAL_CONVERSION_MISMATCH'], 'USD parser exact-minor-units unit test'),
            $s('public-vendor-id-leak','Public vendor identifier leak','public API contains an external customer identifier','Member summary','BrokenVendorAwarePresenter','Presenter exposes integration-only data','GET /api/members/member-0001','HTTP 200; response includes externalCustomerId',[
                '[PASS] domain and application result','[FAIL] public presentation/data exposure'], 'Presenter data-minimization boundary', ['Unexpected field'=>'externalCustomerId','Observed value'=>'NS-CUST-4417','Required action'=>'omit integration identifier','Diagnostic'=>'PUBLIC_VENDOR_ID_EXPOSURE'], 'public API vendor-ID absence check'),
            $s('orchestration-account-mismatch','Account balance mismatch','checking shows savings balance details','Financial overview','PositionBasedBalanceAssociator','Balance result is attached by position, not identity','GET /api/members/member-0001/financial-overview','HTTP 200; checking available balance belongs to savings',[
                '[PASS] API and account gateway calls','[PASS] balance responses decoded','[FAIL] application account identity invariant'], 'Application orchestration identity', ['Expected association'=>'AccountId equality','Observed association'=>'list position','Diagnostic'=>'ACCOUNT_BALANCE_IDENTITY_MISMATCH'], 'financial-overview account-ID association test'),
        ];
    }

    public function find(string $id): DebugScenario
    {
        foreach ($this->all() as $scenario) if ($scenario->id === $id) return $scenario;
        throw new \InvalidArgumentException("Unknown debug scenario: {$id}");
    }
}
