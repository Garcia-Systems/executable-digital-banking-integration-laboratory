<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\{ApiError, IntegrationFailureApiMapper, TransferPreviewPresenter};
use Harbor\DigitalBankingLab\Application\{IntegrationFailure, PreviewTransfer, PreviewTransferCommand,TransferPreviewBlocked,TransferPreviewBlockReason, TransferValidationFailed};
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId, Money};

final readonly class TransferPreviewController
{
    public function __construct(private PreviewTransfer $service, private TransferPreviewPresenter $presenter, private IntegrationFailureApiMapper $failures = new IntegrationFailureApiMapper()) {}

    public function create(string $memberIdentifier, string $rawBody): Response
    {
        try { $body = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return Response::json(400, (new ApiError('invalid_json', 'Request body must contain valid JSON.'))->toArray()); }
        if (!is_array($body) || array_is_list($body)) return Response::json(400, (new ApiError('invalid_request', 'Request body must be a JSON object.'))->toArray());
        $fields = $this->validate($body);
        try { $memberId = new MemberId($memberIdentifier); } catch (\InvalidArgumentException) { $fields['memberId'][] = 'Member identifier is invalid.'; }
        if ($fields !== []) return $this->validation($fields);
        try {
            $command = new PreviewTransferCommand($memberId, new AccountId($body['sourceAccountId']), new AccountId($body['destinationAccountId']), Money::usd($body['amount']['minorUnits']), array_key_exists('memo', $body) ? $body['memo'] : null);
            return Response::json(200, $this->presenter->present($this->service->execute($command)));
        } catch (TransferValidationFailed $failure) {
            return $this->validation($failure->fields);
        } catch (TransferPreviewBlocked $blocked) {
            return $blocked->reason===TransferPreviewBlockReason::VERIFICATION_REVIEW_REQUIRED
                ? Response::json(409,['error'=>['code'=>'verification_review_required','message'=>'Member verification requires review before this action can continue.']])
                : Response::json(409,['error'=>['code'=>'member_verification_required','message'=>'Member verification is required before this action can continue.']]);
        } catch (IntegrationFailure $failure) {
            $mapping = $this->failures->map($failure);
            return Response::json($mapping['status'], $mapping['error']->toArray());
        } catch (\Throwable) {
            return Response::json(500, (new ApiError('service_unavailable', 'Transfer preview is temporarily unavailable.'))->toArray());
        }
    }

    /** @param array<string,mixed> $body @return array<string,list<string>> */
    private function validate(array $body): array
    {
        $errors=[];
        foreach (['sourceAccountId','destinationAccountId'] as $field) {
            if (!isset($body[$field]) || !is_string($body[$field]) || $body[$field] === '') $errors[$field][] = $field === 'sourceAccountId' ? 'Source account is required.' : 'Destination account is required.';
            elseif (!$this->validAccountId($body[$field])) $errors[$field][] = 'Account identifier is invalid.';
        }
        if (!isset($body['amount']) || !is_array($body['amount']) || array_is_list($body['amount'])) $errors['amount'][] = 'Amount must be an object.';
        else {
            if (($body['amount']['currency'] ?? null) !== 'USD') $errors['amount.currency'][] = 'Amount currency must be USD.';
            if (!array_key_exists('minorUnits',$body['amount']) || !is_int($body['amount']['minorUnits'])) $errors['amount.minorUnits'][] = 'Amount minor units must be an integer.';
            elseif ($body['amount']['minorUnits'] <= 0) $errors['amount.minorUnits'][] = 'Amount must be greater than zero.';
        }
        if (array_key_exists('memo',$body) && $body['memo'] !== null && !is_string($body['memo'])) $errors['memo'][] = 'Memo must be a string.';
        elseif (is_string($body['memo'] ?? null) && strlen($body['memo']) > 140) $errors['memo'][] = 'Memo must be 140 characters or fewer.';
        return $errors;
    }

    private function validAccountId(string $value): bool { try { new AccountId($value); return true; } catch (\InvalidArgumentException) { return false; } }

    /** @param array<string,list<string>> $fields */
    private function validation(array $fields): Response { return Response::json(422, ['error'=>['code'=>'validation_failed','message'=>'The request contains invalid fields.','fields'=>$fields]]); }
}
