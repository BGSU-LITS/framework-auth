<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class AuthConfig extends Config
{
    public ?string $context = null;
    public ?DatabaseConfig $database = null;
    public int $expires = 10 * 60 * 60;
    public ?string $required = null;
    public ?string $url = null;
}
