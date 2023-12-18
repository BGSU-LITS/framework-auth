<?php

declare(strict_types=1);

namespace Lits;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface AuthRequired
{
    public function __invoke(ServerRequest $request): string|bool|null;
}
