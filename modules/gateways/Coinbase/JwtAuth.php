<?php
/**
 * JWT Authentication for Coinbase CDP API
 *
 * Generates ES256-signed JWTs for authenticating with the Payment Link API.
 * Based on official Coinbase PHP example.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/const.php';

use Firebase\JWT\JWT;

class CoinbaseJwtAuth
{
    /**
     * @var string CDP API key name/identifier
     */
    private $keyName;

    /**
     * @var string EC private key in PEM format
     */
    private $privateKey;

    /**
     * @param string $keyName CDP API key name
     * @param string $privateKeyPem EC private key in PEM format
     */
    public function __construct(string $keyName, string $privateKeyPem)
    {
        $this->keyName = $keyName;
        // Handle escaped newlines from textarea input in WHMCS
        $this->privateKey = str_replace('\\n', "\n", $privateKeyPem);
    }

    /**
     * Generate a JWT token for API authentication
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path API path (e.g., /api/v1/payment-links)
     * @return string Signed JWT token
     * @throws Exception If private key is invalid
     */
    public function generateToken(string $method, string $path): string
    {
        $privateKeyResource = openssl_pkey_get_private($this->privateKey);
        if (!$privateKeyResource) {
            throw new Exception('Invalid private key: ' . openssl_error_string());
        }

        $time = time();
        $nonce = bin2hex(random_bytes(16));

        // URI format: METHOD hostname/path (no https://)
        $uri = $method . ' business.coinbase.com' . $path;

        $payload = [
            'sub' => $this->keyName,
            'iss' => JWT_ISSUER,
            'nbf' => $time,
            'exp' => $time + JWT_EXPIRY_SECONDS,
            'uri' => $uri,
        ];

        $headers = [
            'typ' => 'JWT',
            'alg' => 'ES256',
            'kid' => $this->keyName,
            'nonce' => $nonce
        ];

        return JWT::encode($payload, $privateKeyResource, 'ES256', $this->keyName, $headers);
    }
}
