<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class ProductRepository
{
    private Database $db;

    /**
     * Allowed sort columns for safety
     */
    private array $allowedSort = ['title', 'price', 'created_at', 'category'];

    /**
     * Explicit columns to avoid SELECT *
     */
    private array $columns = [
        'id',
        'title',
        'description',
        'price',
        'category',
        'created_at'
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Paginated listing with search and category filter.
     *
     * @param string|null $q Search term (title / description)
     * @param string|null $category Category filter
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @param string $dir 'asc'|'desc'
     * @return array<int,array<string,mixed>>
     */
    public function paginate(?string $q, ?string $category, int $limit, int $offset, string $sort, string $dir): array
    {
        if (!in_array($sort, $this->allowedSort, true)) {
            $sort = 'created_at';
        }
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT " . implode(',', $this->columns) . " FROM products WHERE 1=1";
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= " AND (title LIKE :q OR description LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        if ($category !== null && $category !== '') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY {$sort} {$dir} LIMIT :limit OFFSET :offset";

        return $this->db->fetchAll($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Count for pagination meta.
     */
    public function count(?string $q, ?string $category): int
    {
        $sql = "SELECT COUNT(*) FROM products WHERE 1=1";
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= " AND (title LIKE :q OR description LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        if ($category !== null && $category !== '') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        return (int)$this->db->fetchColumn($sql, $params);
    }

    /**
     * Create product.
     * Returns inserted id.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO products (title, description, price, category) VALUES (:title, :description, :price, :category)";
        $this->db->execute($sql, [
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':price'       => $data['price'] ?? 0.0,
            ':category'    => $data['category'] ?? null,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Find by id.
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT " . implode(',', $this->columns) . " FROM products WHERE id = :id";
        return $this->db->fetch($sql, [':id' => $id]);
    }

    /**
     * Update product.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE products SET title = :title, description = :description, price = :price, category = :category WHERE id = :id";
        $affected = $this->db->execute($sql, [
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':price'       => $data['price'] ?? 0.0,
            ':category'    => $data['category'] ?? null,
            ':id'          => $id
        ]);

        return $affected > 0;
    }

    /**
     * Delete product.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM products WHERE id = :id";
        $affected = $this->db->execute($sql, [':id' => $id]);
        return $affected > 0;
    }
}
