<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Database;
use Lits\Service\AuthDatabaseActionService;

abstract class AuthDatabaseAction extends AuthAction
{
    protected Database $database;

    public function __construct(AuthDatabaseActionService $service)
    {
        parent::__construct($service->service);

        $this->database = $service->database;
    }
}
