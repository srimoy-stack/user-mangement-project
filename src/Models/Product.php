<?php
declare(strict_types=1);

namespace App\Models;

class Product
{
    public int $id;
    public string $title;
    public ?string $description;
    public float $price;
    public ?string $category;
    public string $created_at;
}
