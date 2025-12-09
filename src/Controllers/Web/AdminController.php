<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Database;
use App\Repositories\UserRepository;
use PDO;

class AdminController
{
    private PDO $db;
    private UserRepository $users;

   public function __construct(Database $db)
{
    $this->db = $db->pdo();   // Correct
    $this->users = new UserRepository($db);
}


    // ---------------------------------------------------------
    // LOGIN (SESSION BASED)
    // ---------------------------------------------------------
    public function showLogin(): void
    {
        header("Content-Type: application/json");
        echo json_encode([
            "message" => "Send POST /admin/login with email & password"
        ]);
    }

    public function login(): void
    {
        header("Content-Type: application/json");

        // Accept both JSON and form-data
        $input = json_decode(file_get_contents("php://input"), true);
        $email = $input['email'] ?? $_POST['email'] ?? null;
        $password = $input['password'] ?? $_POST['password'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        // Fetch admin
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($password, $admin['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        $_SESSION['admin_id'] = $admin['id'];

        echo json_encode([
            'message' => 'Login successful',
            'admin' => [
                'id' => $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email']
            ]
        ]);
    }

    // ---------------------------------------------------------
    // VERIFY SESSION (UTILITY)
    // ---------------------------------------------------------
    private function requireAuth(): bool
    {
        if (!isset($_SESSION['admin_id'])) {
            header("Content-Type: application/json");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return false;
        }
        return true;
    }

    // ---------------------------------------------------------
    // LIST USERS (Search + Sort + Pagination)
    // ---------------------------------------------------------
    public function listUsers(): void
    {
        if (!$this->requireAuth()) return;

        header("Content-Type: application/json");

        $search = $_GET['q'] ?? null;
        $sort   = $_GET['sort'] ?? 'created_at';
        $dir    = $_GET['dir'] ?? 'desc';

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $users = $this->users->listUsers($search, $limit, $offset, $sort, $dir);
        $total = $this->users->countUsers($search);

        echo json_encode([
            'data' => $users,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ]);
    }

    // ---------------------------------------------------------
    // CREATE USER
    // ---------------------------------------------------------
    public function createUser(): void
    {
        if (!$this->requireAuth()) return;

        header("Content-Type: application/json");

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['name'], $input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and email are required']);
            return;
        }

        try {
            $id = $this->users->createUser($input);

            echo json_encode([
                'message' => 'User created successfully',
                'id' => $id
            ]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
            throw $e;
        }
    }

    // ---------------------------------------------------------
    // SHOW USER
    // ---------------------------------------------------------
    public function showUser(array $vars): void
    {
        if (!$this->requireAuth()) return;

        header("Content-Type: application/json");

        $id = (int)$vars['id'];
        $user = $this->users->findUser($id);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        echo json_encode($user);
    }

    // ---------------------------------------------------------
    // UPDATE USER
    // ---------------------------------------------------------
    public function updateUser(array $vars): void
    {
        if (!$this->requireAuth()) return;

        header("Content-Type: application/json");

        $id = (int)$vars['id'];
        $user = $this->users->findUser($id);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        $updated = $this->users->updateUser($id, $input);

        echo json_encode([
            'message' => $updated ? 'User updated' : 'No changes applied'
        ]);
    }

    // ---------------------------------------------------------
    // DELETE USER
    // ---------------------------------------------------------
    public function deleteUser(array $vars): void
    {
        if (!$this->requireAuth()) return;

        header("Content-Type: application/json");

        $id = (int)$vars['id'];

        $deleted = $this->users->deleteUser($id);

        echo json_encode([
            'message' => $deleted ? 'User deleted' : 'User not found'
        ]);
    }
}
