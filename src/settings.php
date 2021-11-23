<?php

declare(strict_types=1);

use Lits\Config\AuthConfig;
use Lits\Config\LdapConfig;
use Lits\Framework;

return function (Framework $framework): void {
    $framework->addConfig('auth', new AuthConfig());
    $framework->addConfig('ldap', new LdapConfig());
};
