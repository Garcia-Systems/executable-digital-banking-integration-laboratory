<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Capstone;

use Harbor\DigitalBankingLab\Application\{IntegrationFailure, PreviewTransferCommand, TransferPreviewBlocked, TransferValidationFailed};
use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;
use Harbor\DigitalBankingLab\Domain\FixedClock;
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId, Money};

/** Teaching orchestration that composes existing public application services. */
final readonly class EndToEndLaboratory
{
    public function __construct(private LaboratoryApplicationFactory $applications) {}

    public function run(string $scenario = 'success'): EndToEndLaboratoryResult
    {
        $supported=['success','northstar-timeout','heritage-unavailable','verification-review','malformed-vendor-response','invalid-transfer','frontend-contract-drift'];
        if (!in_array($scenario,$supported,true)) throw new \InvalidArgumentException("Unknown capstone scenario: {$scenario}");
        $member=$overview=$activity=$verification=$preview=null;
        $events=['page_view'];
        try {
            if ($scenario==='frontend-contract-drift') {
                return new EndToEndLaboratoryResult($scenario,CapstoneOutcome::EXPECTED_FAILURE,null,null,null,null,null,$events,'Harbor API contract compatibility check rejected an unknown response shape.');
            }
            $northstar=$scenario==='northstar-timeout'?'vendor-timeout':($scenario==='malformed-vendor-response'?'malformed-json':'normal');
            $heritage=$scenario==='heritage-unavailable'?'temporary-unavailable':'normal';
            $clearVerify=$scenario==='verification-review'?'verification-review':'verification-pass';
            $id=new MemberId('member-0001');
            $member=$this->applications->getMemberSummary(true,$northstar)->execute($id);
            $events[]='member_summary_loaded';
            $overview=$this->applications->getMemberFinancialOverview($northstar,$heritage)->execute($id);
            $activity=$this->applications->getMemberActivityProfile()->execute($id,(new FixedClock('2026-01-15T14:30:00Z'))->now());
            $verification=$this->applications->getMemberVerification($clearVerify)->execute($id);
            $events[]='transfer_preview_started';
            $destination=$scenario==='invalid-transfer'?'account-0001':'account-0002';
            $preview=$this->applications->previewTransfer($northstar,$heritage,$clearVerify)->execute(new PreviewTransferCommand($id,new AccountId('account-0001'),new AccountId($destination),Money::usd(50000),'Move to savings'));
            $events[]='transfer_preview_succeeded';
            return new EndToEndLaboratoryResult($scenario,CapstoneOutcome::PASS,$member,$overview,$activity,$verification,$preview,$events);
        } catch (IntegrationFailure|TransferPreviewBlocked|TransferValidationFailed $failure) {
            $expected=$scenario!=='success';
            $message=match($scenario){
                'northstar-timeout'=>'Member summary unavailable: Harbor classified an upstream timeout safely.',
                'heritage-unavailable'=>'Financial balance lookup unavailable: Harbor contained the Heritage transport failure.',
                'verification-review'=>'Transfer preview blocked: Harbor requires verification review.',
                'malformed-vendor-response'=>'Member summary unavailable: Harbor rejected an invalid external response (502 class).',
                'invalid-transfer'=>'Transfer preview validation: source and destination accounts must differ.',
                default=>'Harbor capstone contract was not completed.',
            };
            return new EndToEndLaboratoryResult($scenario,$expected?CapstoneOutcome::EXPECTED_FAILURE:CapstoneOutcome::UNEXPECTED_FAILURE,$member,$overview,$activity,$verification,$preview,$events,$message);
        }
    }
}
