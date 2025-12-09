<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['admin_id'] ?? null;
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }
}
