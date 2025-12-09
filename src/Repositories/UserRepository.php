<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class UserRepository
{
    private Database $db;

    /**
     * Allowed columns for sorting to prevent SQL injection.
     */
    private array $allowedSort = ['name', 'email', 'created_at'];

    /**
     * Select columns (no SELECT *) — required by assignment.
     */
    private array $columns = [
        'id',
        'name',
        'email',
        'phone',
        'city',
        'created_at'
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * List users with search, pagination, sorting.
     *
     * @param string|null $search Search term (name or email)
     * @param int $limit Items per page
     * @param int $offset Offset for pagination
     * @param string $sort Sort column
     * @param string $dir Direction (asc/desc)
     *
     * @return array
     */
    public function listUsers(?string $search, int $limit, int $offset, string $sort, string $dir): array
    {
        // Validate and whitelist sort column
        if (!in_array($sort, $this->allowedSort, true)) {
            $sort = 'created_at';
        }

        // Validate direction
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT " . implode(',', $this->columns) . "
                FROM users
                WHERE 1=1";

        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= " AND (name LIKE :q OR email LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY {$sort} {$dir} 
                  LIMIT :limit OFFSET :offset";

        return $this->db->fetchAll($sql, [
            ...$params,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    /**
     * Count total users (for pagination).
     */
    public function countUsers(?string $search): int
    {
        $sql = "SELECT COUNT(*) 
                FROM users
                WHERE 1=1";

        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= " AND (name LIKE :q OR email LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }

        return (int)$this->db->fetchColumn($sql, $params);
    }

    /**
     * Create a new user — all prepared statements.
     */
    public function createUser(array $data): int
    {
        $sql = "INSERT INTO users (name, email, phone, city) 
                VALUES (:name, :email, :phone, :city)";

        $this->db->execute($sql, [
            ':name'  => $data['name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'] ?? null,
            ':city'  => $data['city'] ?? null,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Fetch a single user.
     */
    public function findUser(int $id): ?array
    {
        $sql = "SELECT " . implode(',', $this->columns) . "
                FROM users
                WHERE id = :id";

        return $this->db->fetch($sql, [':id' => $id]);
    }

    /**
     * Update user.
     */
    public function updateUser(int $id, array $data): bool
    {
        $sql = "UPDATE users
                SET name = :name,
                    email = :email,
                    phone = :phone,
                    city = :city
                WHERE id = :id";

        $affected = $this->db->execute($sql, [
            ':name'  => $data['name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'] ?? null,
            ':city'  => $data['city'] ?? null,
            ':id'    => $id
        ]);

        return $affected > 0;
    }

    /**
     * Delete user.
     */
    public function deleteUser(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $affected = $this->db->execute($sql, [':id' => $id]);
        return $affected > 0;
    }
}
