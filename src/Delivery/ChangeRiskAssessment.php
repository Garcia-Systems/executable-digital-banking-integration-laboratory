<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Delivery;

final readonly class ChangeRiskAssessment
{
    /** @param list<string> $reasons @param list<string> $requiredEvidence */
    private function __construct(public string $classification, public array $reasons, public array $requiredEvidence) {}
    public static function assess(ChangeSet $change): self
    {
        $files=$change->files; $categories=array_keys($change->classified()); $reasons=[]; $evidence=[]; $risk='LOW';
        $contains=static function(string $term)use($files):bool {foreach($files as $file)if(str_contains(strtolower($file),strtolower($term)))return true;return false;};
        if ($categories === ['DOCUMENTATION']) {$reasons[]='documentation-only change';$evidence[]='documentation check';}
        elseif ($categories === ['FRONTEND'] && ($contains('.css') || $contains('style'))) {$reasons[]='frontend-only cosmetic change';$evidence=['frontend tests','manual viewport check'];}
        if (in_array('API_CONTRACT',$categories,true) || in_array('HARBOR_HTTP',$categories,true)) {$risk='MODERATE';$reasons[]='public Harbor API surface changed';$evidence[]='shared contract and HTTP integration tests';}
        if (in_array('DATABASE',$categories,true)) {$risk='MODERATE';$reasons[]='database schema or query semantics changed';$evidence[]='repository and integration tests';}
        if (in_array('INTEGRATION',$categories,true)) {$risk='HIGH';$reasons[]='external integration boundary changed';$evidence[]='adapter and deterministic integration tests';}
        if ($contains('money') || $contains('transfer') || $contains('previewtransfer')) {$risk='HIGH';$reasons[]='money movement or transfer validation may change';$evidence[]='exact-boundary unit and HTTP integration tests';}
        if ($contains('security') || $contains('sensitive') || $contains('dataexposure')) {$risk='HIGH';$reasons[]='sensitive-data handling changed';$evidence[]='security and API data-exposure checks';}
        if ($reasons===[]) {$reasons[]='no high-impact path heuristic matched';$evidence[]='targeted regression tests';}
        return new self($risk,array_values(array_unique($reasons)),array_values(array_unique($evidence)));
    }
}
