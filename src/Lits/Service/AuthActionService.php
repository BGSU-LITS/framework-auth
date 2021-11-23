<?php

declare(strict_types=1);

namespace Lits\Service;

use Jasny\Auth\Auth;
use Lits\Database;

final class AuthActionService
{
    public ActionService $service;
    public Auth $auth;
    public Database $database;

    public function __construct(
        ActionService $service,
        Auth $auth,
        Database $database
    ) {
        $this->service = $service;
        $this->auth = $auth;
        $this->database = $database;
    }
}
