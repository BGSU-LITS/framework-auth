<?php

declare(strict_types=1);

namespace Lits\Service;

use Jasny\Auth\Auth;

final class AuthActionService
{
    public ActionService $service;
    public Auth $auth;

    public function __construct(ActionService $service, Auth $auth)
    {
        $this->service = $service;
        $this->auth = $auth;
    }
}
