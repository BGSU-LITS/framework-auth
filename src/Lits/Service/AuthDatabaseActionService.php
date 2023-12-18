<?php

declare(strict_types=1);

namespace Lits\Service;

use Lits\Database;

final class AuthDatabaseActionService
{
    public function __construct(
        public AuthActionService $service,
        public Database $database,
    ) {
    }
}
