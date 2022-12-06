<?php

declare(strict_types=1);

namespace Lits\Service;

use Lits\Database;

final class AuthDatabaseActionService
{
    public AuthActionService $service;
    public Database $database;

    public function __construct(AuthActionService $service, Database $database)
    {
        $this->service = $service;
        $this->database = $database;
    }
}
