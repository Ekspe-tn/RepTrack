<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UsersSeed extends AbstractSeed
{
    public function run(): void
    {
        $email = 'admin@reptrack.tn';
        $hash = password_hash('ChangeMe123!', PASSWORD_BCRYPT);

        $this->execute(
            "INSERT INTO users (name, email, password, role, active, created_at)\n" .
            "VALUES ('Admin', '{$email}', '{$hash}', 'admin', 1, NOW())\n" .
            "ON DUPLICATE KEY UPDATE\n" .
            "name = VALUES(name),\n" .
            "password = VALUES(password),\n" .
            "role = VALUES(role),\n" .
            "active = VALUES(active)"
        );
    }
}
