<?php

declare(strict_types=1);

use Jasny\Auth\Event\Login;
use Jasny\Auth\Event\Logout;
use Lits\Data\UserData;
use Lits\Framework;
use Safe\DateTimeImmutable;

// phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive
return function (Framework $framework): void {
    $framework->dispatcher()->addListener(
        Login::class,
        function (Login $login): void {
            $user = $login->user();

            if (!($user instanceof UserData)) {
                return;
            }

            if ($user->isDisabled()) {
                $login->cancel('Your account has been disabled');
            }

            $user->expires = new DateTimeImmutable('tomorrow');
            $user->save();
        },
    );

    $framework->dispatcher()->addListener(
        Logout::class,
        function (Logout $logout): void {
            $user = $logout->user();

            if (!($user instanceof UserData)) {
                return;
            }

            $user->expires = null;
            $user->save();
        },
    );
};
