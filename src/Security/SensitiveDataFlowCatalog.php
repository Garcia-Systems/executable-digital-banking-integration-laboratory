<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

final class SensitiveDataFlowCatalog
{
    /** @return list<SensitiveDataFlow> */
    public function all(): array { return [
        new SensitiveDataFlow('member-summary','Member Summary',[['component'=>'Northstar','representation'=>'vendor customer JSON'],['component'=>'adapter','representation'=>'Harbor Member'],['component'=>'API presenter','representation'=>'scoped member DTO'],['component'=>'Member Web','representation'=>'runtime view state']]),
        new SensitiveDataFlow('account-balance-details','Account Balance Details',[['component'=>'Heritage','representation'=>'SOAP account details'],['component'=>'adapter','representation'=>'Harbor AccountBalanceDetails'],['component'=>'financial overview','representation'=>'Harbor application result'],['component'=>'API presenter','representation'=>'scoped overview DTO'],['component'=>'Member Web','representation'=>'runtime view state']]),
        new SensitiveDataFlow('verification','Verification',[['component'=>'ClearVerify','representation'=>'provider status JSON'],['component'=>'adapter','representation'=>'Harbor VerificationStatus'],['component'=>'API presenter','representation'=>'Harbor verification DTO'],['component'=>'Member Web','representation'=>'runtime workflow state']]),
        new SensitiveDataFlow('transfer-preview','Transfer Preview',[['component'=>'Member Web','representation'=>'sourceAccountId, destinationAccountId, amount, memo'],['component'=>'Harbor API','representation'=>'validated request values'],['component'=>'Application','representation'=>'MemberId, AccountIds, Money, memo'],['component'=>'gateways','representation'=>'member lookup; source balance lookup; verification lookup'],['component'=>'TransferPreview','representation'=>'Harbor-owned preview'],['component'=>'Member Web','representation'=>'validated preview fields only']]),
    ]; }

    public function find(string $id): ?SensitiveDataFlow { foreach($this->all() as $flow) if($flow->id===$id)return $flow; return null; }

    public function renderTransferPreview(): string
    {
        return "Transfer Preview Data Flow\n\nMember Web:\nsourceAccountId\ndestinationAccountId\namount\nmemo\n\nHarbor API:\nvalidated request values\n\nApplication:\nMemberId\nAccountIds\nMoney\nmemo\n\nNorthstar:\nmember lookup only\nmemo sent: NO\ntransfer amount sent: NO\n\nHeritage:\nsource balance lookup only\nmemo sent: NO\ndestination account sent: NO\n\nClearVerify:\nverification lookup by MemberId only\ntransfer amount sent: NO\nmemo sent: NO\naccount balances sent: NO\n\nHarbor Response:\nvalidated preview fields only\n";
    }
}
