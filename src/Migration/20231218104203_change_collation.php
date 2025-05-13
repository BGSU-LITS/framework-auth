<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class ChangeCollation extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->changeCollation('utf8mb4_general_ci');
    }

    #[\Override]
    protected function down(): void
    {
    }
}
