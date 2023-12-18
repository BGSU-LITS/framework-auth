<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateUserTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('user')
            ->addColumn('username', 'string', ['length' => 255])
            ->addColumn('password', 'string', [
                'length' => 255,
                'null' => true,
            ])
            ->addColumn('name_first', 'string', [
                'length' => 255,
                'null' => true,
            ])
            ->addColumn('name_last', 'string', [
                'length' => 255,
                'null' => true,
            ])
            ->addColumn('role_id', 'string', ['length' => 255])
            ->addColumn('disabled', 'datetime', ['null' => true])
            ->addColumn('expires', 'datetime', ['null' => true])
            ->addIndex('username', Index::TYPE_UNIQUE)
            ->addForeignKey('role_id', 'role', 'id', ForeignKey::RESTRICT)
            ->create();
    }

    protected function down(): void
    {
        $this->table('user')->drop();
    }
}
