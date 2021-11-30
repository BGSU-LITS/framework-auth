<?php

declare(strict_types=1);

use Jasny\Auth\Session\Jwt\CookieMiddleware;
use Lits\AuthMiddleware;
use Lits\Framework;
use Middlewares\Https as HttpsMiddleware;

return function (Framework $framework): void {
    if ($framework->isCli()) {
        return;
    }

    $framework->app()->add(AuthMiddleware::class);
    $framework->app()->add(CookieMiddleware::class);
    $framework->app()->add(HttpsMiddleware::class);
};
