<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Infrastructure\Http\{HttpClient, HttpTimeoutException, HttpTransportException};
use Harbor\DigitalBankingLab\Integration\Northstar\Exception\{NorthstarCustomerNotFound, NorthstarHttpFailure, NorthstarResponseDecodingFailure, NorthstarTimeoutFailure, NorthstarUnavailableFailure};
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomer, NorthstarCustomerKey, NorthstarCustomerStatus, NorthstarProduct, NorthstarProductClass, NorthstarProductKey, NorthstarProductState};

final readonly class NorthstarRestClient implements NorthstarClient
{
    public function __construct(private HttpClient $http, private string $baseUrl)
    {
        if ($baseUrl === '') throw new \InvalidArgumentException('Northstar base URL must not be empty.');
    }

    public function findCustomer(NorthstarCustomerKey $key): NorthstarCustomer
    {
        $url = rtrim($this->baseUrl, '/') . '/v1/customers/' . rawurlencode($key->value);
        try {
            $response = $this->http->request('GET', $url, ['Accept' => 'application/json']);
        } catch (HttpTimeoutException $error) {
            throw new NorthstarTimeoutFailure('Northstar request timed out.', previous: $error);
        } catch (HttpTransportException $error) {
            throw new NorthstarUnavailableFailure('Northstar is unavailable.', previous: $error);
        }
        if ($response->statusCode === 404) throw new NorthstarCustomerNotFound(404);
        if ($response->statusCode !== 200) throw new NorthstarHttpFailure($response->statusCode);

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new NorthstarResponseDecodingFailure('Northstar response contains malformed JSON.', previous: $error);
        }
        if (!is_array($data)) throw new NorthstarResponseDecodingFailure('Northstar response must be a JSON object.');
        return $this->decodeCustomer($data);
    }

    /** @param array<mixed> $data */
    private function decodeCustomer(array $data): NorthstarCustomer
    {
        $this->requireShape($data, ['customerKey' => 'string', 'customerStatus' => 'string', 'fullName' => 'string', 'products' => 'array'], 'customer');
        if (count($data['products']) > 100) throw new NorthstarResponseDecodingFailure('Northstar customer contains too many products.');
        $products = [];
        foreach ($data['products'] as $index => $product) {
            if (!is_array($product)) throw new NorthstarResponseDecodingFailure("Northstar product {$index} must be an object.");
            $this->requireShape($product, ['productKey' => 'string', 'productClass' => 'string', 'nickname' => 'string', 'currentBalanceCents' => 'integer', 'state' => 'string'], "product {$index}");
            try {
                $products[] = new NorthstarProduct(new NorthstarProductKey($product['productKey']), new NorthstarProductClass($product['productClass']), $product['nickname'], $product['currentBalanceCents'], NorthstarProductState::from($product['state']));
            } catch (\ValueError|\InvalidArgumentException $error) {
                throw new NorthstarResponseDecodingFailure("Northstar product {$index} contains an invalid value.", previous: $error);
            }
        }
        try {
            return new NorthstarCustomer(new NorthstarCustomerKey($data['customerKey']), NorthstarCustomerStatus::from($data['customerStatus']), $data['fullName'], $products);
        } catch (\ValueError|\InvalidArgumentException $error) {
            throw new NorthstarResponseDecodingFailure('Northstar customer contains an invalid value.', previous: $error);
        }
    }

    /** @param array<mixed> $data @param array<string, string> $fields */
    private function requireShape(array $data, array $fields, string $context): void
    {
        foreach ($fields as $field => $type) {
            if (!array_key_exists($field, $data) || gettype($data[$field]) !== $type) {
                throw new NorthstarResponseDecodingFailure("Northstar {$context} field '{$field}' is required and must be {$type}.");
            }
        }
    }
}
