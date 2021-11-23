<?php

declare(strict_types=1);

namespace Lits;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface AuthRequired
{
    /** @return string|bool|null */
    public function __invoke(ServerRequest $request);
}
