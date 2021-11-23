<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class CreateTokenTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('token', ['user_id', 'subject'])
            ->addColumn('user_id', 'integer')
            ->addColumn('subject', 'string', ['length' => 255])
            ->addColumn('token', 'string', ['length' => 255])
            ->addColumn('expires', 'datetime', ['null' => true])
            ->addForeignKey('user_id', 'user', 'id', ForeignKey::CASCADE)
            ->create();
    }

    protected function down(): void
    {
        $this->table('token')->drop();
    }
}
