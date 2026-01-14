<?php
/**
 * Coinbase Payment Link API Client
 *
 * Handles creation and retrieval of payment links via the CDP Business API.
 */

require_once __DIR__ . '/const.php';
require_once __DIR__ . '/JwtAuth.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PaymentLinkClient
{
    /**
     * @var CoinbaseJwtAuth JWT authentication handler
     */
    private $jwtAuth;

    /**
     * @var Client Guzzle HTTP client
     */
    private $httpClient;

    /**
     * @param string $keyName CDP API key name
     * @param string $privateKey EC private key in PEM format
     */
    public function __construct(string $keyName, string $privateKey)
    {
        $this->jwtAuth = new CoinbaseJwtAuth($keyName, $privateKey);
        $this->httpClient = new Client([
            'base_uri' => PAYMENT_LINK_API_BASE,
            'timeout' => 30,
        ]);
    }

    /**
     * Create a new payment link
     *
     * @param array $params Payment parameters:
     *   - amount: Payment amount (string)
     *   - description: Human-readable description (optional)
     *   - metadata: Key-value pairs for tracking (array)
     *   - successUrl: Redirect URL on success
     *   - failUrl: Redirect URL on failure
     * @return object Response containing 'url' and 'id'
     * @throws Exception On API error
     */
    public function createPaymentLink(array $params): object
    {
        $token = $this->jwtAuth->generateToken('POST', PAYMENT_LINK_API_PATH);

        $body = [
            'amount' => (string) $params['amount'],
            'currency' => PAYMENT_CURRENCY,
            'network' => PAYMENT_NETWORK,
            'metadata' => $params['metadata'] ?? [],
            'successRedirectUrl' => $params['successUrl'],
            'failRedirectUrl' => $params['failUrl'],
        ];

        // Only include description if provided (max 500 chars per API)
        if (!empty($params['description'])) {
            $body['description'] = substr($params['description'], 0, 500);
        }

        try {
            $response = $this->httpClient->post(PAYMENT_LINK_API_PATH, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $result = json_decode($response->getBody()->getContents());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from API');
            }

            return $result;

        } catch (GuzzleException $e) {
            throw new Exception('Failed to create payment link: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve payment link details
     *
     * @param string $id Payment link ID
     * @return object Payment link details including status
     * @throws Exception On API error
     */
    public function getPaymentLink(string $id): object
    {
        $path = PAYMENT_LINK_API_PATH . '/' . $id;
        $token = $this->jwtAuth->generateToken('GET', $path);

        try {
            $response = $this->httpClient->get($path, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from API');
            }

            return $result;

        } catch (GuzzleException $e) {
            throw new Exception('Failed to retrieve payment link: ' . $e->getMessage());
        }
    }
}
