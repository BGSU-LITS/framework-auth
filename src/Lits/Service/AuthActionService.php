<?php

declare(strict_types=1);

namespace Lits\Service;

use Jasny\Auth\Auth;

final class AuthActionService
{
    public function __construct(
        public ActionService $service,
        public Auth $auth,
    ) {
    }
}
