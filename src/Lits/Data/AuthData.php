<?php

declare(strict_types=1);

namespace Lits\Data;

use DateTimeInterface as DateTime;
use Jasny\Auth\ContextInterface as Context;
use Jasny\Auth\Storage\TokenStorageInterface as TokenStorage;
use Jasny\Auth\StorageInterface as Storage;
use Jasny\Auth\UserInterface as User;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;
use PDOException;

final class AuthData extends DatabaseData implements Storage, TokenStorage
{
    #[\Override]
    public function getContextForUser(User $user): ?Context
    {
        return null;
    }

    #[\Override]
    public function fetchContext(string $id): Context
    {
        return new ContextData($id, $this->settings, $this->database);
    }

    /**
     * @return array{uid: string, expire: DateTime}
     * @throws InvalidDataException
     */
    #[\Override]
    public function fetchToken(string $subject, string $token): ?array
    {
        $token = TokenData::fromSubjectToken(
            $subject,
            $token,
            $this->settings,
            $this->database,
        );

        if (\is_null($token)) {
            return null;
        }

        return [
            'uid' => (string) $token->user_id,
            'expire' => $token->expires,
        ];
    }

    /** @throws InvalidDataException */
    #[\Override]
    public function fetchUserById(string $id): ?UserData
    {
        return UserData::fromId(
            (int) $id,
            $this->settings,
            $this->database,
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    #[\Override]
    public function fetchUserByUsername(string $username): ?UserData
    {
        return UserData::fromUsername(
            $username,
            $this->settings,
            $this->database,
        );
    }

    /**
     * @throws DuplicateInsertException
     * @throws PDOException
     */
    #[\Override]
    public function saveToken(
        string $subject,
        string $token,
        User $user,
        DateTime $expire,
    ): void {
        $token = new TokenData(
            (int) $user->getAuthId(),
            $subject,
            $token,
            $expire,
            $this->settings,
            $this->database,
        );

        $token->save();
    }
}
