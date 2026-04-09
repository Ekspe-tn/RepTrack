<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlignIdColumnsBigint extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->execute('ALTER TABLE governorates MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE users MODIFY id INT NOT NULL AUTO_INCREMENT');
        $this->execute('ALTER TABLE governorates MODIFY id INT NOT NULL AUTO_INCREMENT');
    }
}
