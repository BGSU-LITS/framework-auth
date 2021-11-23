<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class CreateContextTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('context', ['user_id', 'context'])
            ->addColumn('user_id', 'integer')
            ->addColumn('context', 'string', ['length' => 255])
            ->addColumn('role_id', 'string', ['length' => 255])
            ->addForeignKey('user_id', 'user', 'id', ForeignKey::RESTRICT)
            ->addForeignKey('role_id', 'role', 'id', ForeignKey::RESTRICT)
            ->create();
    }

    protected function down(): void
    {
        $this->table('context')->drop();
    }
}
