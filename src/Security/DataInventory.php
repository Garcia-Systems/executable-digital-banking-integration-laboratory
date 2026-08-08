<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

final class DataInventory
{
    /** @return list<DataElementDescriptor> */
    public function all(): array
    {
        return [
            new DataElementDescriptor('harbor.institution_name', DataSensitivity::PUBLIC, 'Public identity', 'yes', 'not needed', 'yes'),
            new DataElementDescriptor('help.static_text', DataSensitivity::PUBLIC, 'Member help', 'yes', 'not needed', 'yes'),
            new DataElementDescriptor('api.documentation', DataSensitivity::PUBLIC, 'Developer education', 'yes', 'not needed', 'no'),
            new DataElementDescriptor('integration.diagnostic_code', DataSensitivity::INTERNAL, 'Operational diagnosis', 'no', 'yes', 'no'),
            new DataElementDescriptor('integration.vendor_name', DataSensitivity::INTERNAL, 'Integration operation', 'no', 'yes', 'no'),
            new DataElementDescriptor('application.operation', DataSensitivity::INTERNAL, 'Operational diagnosis', 'no', 'yes', 'no'),
            new DataElementDescriptor('architecture.metadata', DataSensitivity::INTERNAL, 'Laboratory education', 'no', 'yes', 'no'),
            new DataElementDescriptor('member.display_name', DataSensitivity::MEMBER_SENSITIVE, 'Member experience', 'yes, scoped member response', 'no by default', 'yes'),
            new DataElementDescriptor('member.harbor_id', DataSensitivity::MEMBER_SENSITIVE, 'Harbor resource identity', 'yes, intentionally', 'no by default', 'yes'),
            new DataElementDescriptor('account.harbor_id', DataSensitivity::MEMBER_SENSITIVE, 'Harbor account selection', 'yes, intentionally', 'no by default', 'yes'),
            new DataElementDescriptor('account.balance', DataSensitivity::MEMBER_SENSITIVE, 'Member experience / application decisions', 'yes, only where required', 'no by default', 'yes, only where required'),
            new DataElementDescriptor('transfer.amount', DataSensitivity::MEMBER_SENSITIVE, 'Transfer preview', 'yes, preview only', 'no', 'runtime only'),
            new DataElementDescriptor('transfer.memo', DataSensitivity::MEMBER_SENSITIVE, 'Member confirmation', 'yes, preview only', 'no', 'runtime only'),
            new DataElementDescriptor('verification.status', DataSensitivity::MEMBER_SENSITIVE, 'Harbor workflow', 'yes, scoped response', 'no', 'runtime only'),
            new DataElementDescriptor('activity.timestamp', DataSensitivity::MEMBER_SENSITIVE, 'Activity experience / decisions', 'yes, activity response', 'no', 'runtime only'),
            new DataElementDescriptor('northstar.customer_key', DataSensitivity::INTERNAL, 'Integration mapping', 'no', 'no by default', 'no'),
            new DataElementDescriptor('vendor.api_token', DataSensitivity::SECRET, 'Client transport authentication', 'no', 'no', 'no'),
            new DataElementDescriptor('vendor.api_credential', DataSensitivity::SECRET, 'Client transport authentication', 'no', 'no', 'no'),
            new DataElementDescriptor('vendor.signing_key_placeholder', DataSensitivity::SECRET, 'Future transport configuration only', 'no', 'no', 'no'),
        ];
    }

    public function render(): string
    {
        $text = "Harbor Data Inventory\n\n";
        foreach ($this->all() as $item) {
            $text .= "{$item->name}\nClassification: {$item->classification->value}\nPrimary use: {$item->primaryUse}\nPublic API: {$item->publicApi}\nDiagnostics: {$item->diagnostics}\nMember Web: {$item->memberWeb}\n\n";
        }
        return $text;
    }
}
