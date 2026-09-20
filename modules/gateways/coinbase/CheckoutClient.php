<?php
/**
 * Coinbase Checkout API Client
 *
 * Handles creation and retrieval of checkouts via the CDP Business API.
 */

require_once __DIR__ . '/const.php';
require_once __DIR__ . '/JwtAuth.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CheckoutClient
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
     * @var string API path (production or sandbox)
     */
    private $apiPath;

    /**
     * @param string $keyName CDP API key name
     * @param string $privateKey EC private key in PEM format
     * @param string $apiPath API path (defaults to production)
     */
    public function __construct(string $keyName, string $privateKey, string $apiPath = CHECKOUT_API_PATH)
    {
        $this->jwtAuth = new CoinbaseJwtAuth($keyName, $privateKey);
        $this->httpClient = new Client([
            'base_uri' => CHECKOUT_API_BASE,
            // Total ceiling for a single API call; short enough to fail fast on a
            // hung connection instead of blocking a PHP-FPM worker indefinitely.
            'timeout' => 30,
            // Separate ceiling for the TCP/TLS handshake so a black-holed host is
            // detected quickly rather than consuming the full request timeout.
            'connect_timeout' => 10,
            // TLS peer verification stays enabled (Guzzle default). Never disable
            // this: the requests carry Bearer JWTs, so a MITM could otherwise
            // capture credentials usable against the Checkout API.
            'verify' => true,
            // Do not follow redirects: a redirect would re-send the Authorization
            // header to a different host and leak the Bearer token.
            'allow_redirects' => false,
        ]);
        $this->apiPath = $apiPath;
    }

    /**
     * Create a new checkout
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
    public function createCheckout(array $params): object
    {
        $token = $this->jwtAuth->generateToken('POST', $this->apiPath);

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
            $response = $this->httpClient->post($this->apiPath, [
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
            // The raw Guzzle message can embed the request line; surface only a
            // sanitized reason so an Authorization header never reaches logs.
            throw new Exception('Failed to create checkout (HTTP transport error): ' . $e->getCode());
        }
    }

    /**
     * Retrieve checkout details
     *
     * @param string $id Checkout ID
     * @return object Checkout details including status
     * @throws Exception On API error
     */
    public function getCheckout(string $id): object
    {
        $path = $this->apiPath . '/' . $id;
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
            // See createCheckout(): keep the raw transport message out of logs so
            // the Bearer token in the request headers cannot be leaked.
            throw new Exception('Failed to retrieve checkout (HTTP transport error): ' . $e->getCode());
        }
    }
}
