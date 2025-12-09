<?php
declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Validate the Authorization header Bearer token.
     * Returns authenticated user_id or false.
     */
    public function handle(): int|false
    {
        header("Content-Type: application/json");

        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid Authorization header']);
            return false;
        }

        $token = trim(substr($auth, 7));

        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            // Expect token payload to contain "uid"
            if (!isset($decoded->uid)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token payload']);
                return false;
            }

            return (int)$decoded->uid;

        } catch (\Firebase\JWT\ExpiredException $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token expired']);
            return false;

        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return false;
        }
    }
}
