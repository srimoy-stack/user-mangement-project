<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Repositories\ProductRepository;
use Firebase\JWT\JWT;

class ProductController
{
    private ProductRepository $repo;
    private Database $db;
    private ?int $uid = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->repo = new ProductRepository($db);
    }

    /**
     * Called by index.php JWT middleware to set authenticated user id.
     */
    public function setUserId(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * POST /api/auth/login
     * Body: { "email": "...", "password": "..." }
     * Returns: { "token": "..." }
     *
     * Uses admins table (per your choice A).
     */
    public function login(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['email'], $input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'email and password required']);
            return;
        }

        $email = trim($input['email']);
        $password = $input['password'];

        $admin = $this->db->fetch("SELECT id, email, password FROM admins WHERE email = :email LIMIT 1", [
            ':email' => $email
        ]);

        if (!$admin || !password_verify($password, $admin['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        $now = time();
        $ttl = (int)($_ENV['JWT_TTL'] ?? 3600);
        $payload = [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            // payload key 'uid' used by JwtMiddleware
            'uid' => (int)$admin['id'],
            'email' => $admin['email']
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        echo json_encode(['token' => $token, 'expires_in' => $ttl]);
    }

    /**
     * GET /api/products
     * Supports: ?page=1&limit=10&q=...&category=...&sort=price&dir=asc
     */
    public function index(): void
    {
        header('Content-Type: application/json');

        $q = $_GET['q'] ?? null;
        $category = $_GET['category'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'created_at';
        $dir = $_GET['dir'] ?? 'desc';

        $rows = $this->repo->paginate($q, $category, $limit, $offset, $sort, $dir);
        $total = $this->repo->count($q, $category);

        echo json_encode([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $limit,
                'current_page' => $page,
                'last_page' => (int)ceil($total / max(1, $limit))
            ]
        ]);
    }

    /**
     * POST /api/products
     * Protected by JWT middleware (index.php ensures auth)
     */
    public function store(): void
    {
        header('Content-Type: application/json');

        // protected: ensure uid exists
        if ($this->uid === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // Validate required fields
        $title = trim($input['title'] ?? '');
        if ($title === '') {
            http_response_code(422);
            echo json_encode(['error' => 'title is required']);
            return;
        }

        $price = isset($input['price']) ? (float)$input['price'] : 0.0;
        $productData = [
            'title' => $title,
            'description' => $input['description'] ?? null,
            'price' => $price,
            'category' => $input['category'] ?? null
        ];

        $id = $this->repo->create($productData);

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Product created']);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(array $vars): void
    {
        header('Content-Type: application/json');

        $id = (int)$vars['id'];
        $product = $this->repo->find($id);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        echo json_encode($product);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(array $vars): void
    {
        header('Content-Type: application/json');

        if ($this->uid === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $id = (int)$vars['id'];
        $product = $this->repo->find($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // basic validation
        $title = trim($input['title'] ?? $product['title']);
        if ($title === '') {
            http_response_code(422);
            echo json_encode(['error' => 'title is required']);
            return;
        }

        $data = [
            'title' => $title,
            'description' => $input['description'] ?? $product['description'],
            'price' => isset($input['price']) ? (float)$input['price'] : (float)$product['price'],
            'category' => $input['category'] ?? $product['category']
        ];

        $updated = $this->repo->update($id, $data);

        echo json_encode(['message' => $updated ? 'Product updated' : 'No changes applied']);
    }

    /**
     * DELETE /api/products/{id}
     */
    public function delete(array $vars): void
    {
        header('Content-Type: application/json');

        if ($this->uid === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $id = (int)$vars['id'];
        $exists = $this->repo->find($id);
        if (!$exists) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        $deleted = $this->repo->delete($id);
        echo json_encode(['message' => $deleted ? 'Product deleted' : 'Could not delete product']);
    }
}
