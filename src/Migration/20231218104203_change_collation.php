<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class ChangeCollation extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->changeCollation('utf8mb4_general_ci');
    }

    protected function down(): void
    {
    }
}
