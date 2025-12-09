<?php
declare(strict_types=1);

namespace App\Models;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $phone;
    public ?string $city;
    public string $created_at;
}
