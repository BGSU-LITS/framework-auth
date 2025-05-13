<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateRoleTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('role', 'id')
            ->addColumn('id', 'string', ['length' => 255])
            ->create();

        $this->insert('role', ['id' => 'user']);
        $this->insert('role', ['id' => 'admin']);
        $this->insert('role', ['id' => 'super']);
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('role')->drop();
    }
}
