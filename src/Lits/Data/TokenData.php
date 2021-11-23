<?php

declare(strict_types=1);

namespace Lits\Data;

use DateTimeInterface as DateTime;
use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\DateTimeImmutable;
use Throwable;

use function Latitude\QueryBuilder\field;

final class TokenData extends DatabaseData
{
    public int $user_id;
    public string $subject;
    public string $token;
    public DateTime $expires;

    public function __construct(
        int $user_id,
        string $subject,
        string $token,
        DateTime $expires,
        Settings $settings,
        Database $database
    ) {
        parent::__construct($settings, $database);

        $this->user_id = $user_id;
        $this->subject = $subject;
        $this->token = $token;
        $this->expires = $expires;
    }

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        if (!isset($row['user_id'])) {
            throw new InvalidDataException('The user_id must be specified');
        }

        if (!isset($row['subject'])) {
            throw new InvalidDataException('The subject must be specified');
        }

        if (!isset($row['token'])) {
            throw new InvalidDataException('The token must be specified');
        }

        if (!isset($row['expires'])) {
            throw new InvalidDataException(
                'The datetime expires must be specified'
            );
        }

        try {
            $expires = new DateTimeImmutable($row['expires']);
        } catch (Throwable $exception) {
            throw new InvalidDataException(
                'The datetime expires could not be parsed',
                0,
                $exception
            );
        }

        return new static(
            (int) $row['user_id'],
            \trim($row['subject']),
            \trim($row['token']),
            $expires,
            $settings,
            $database
        );
    }

    /** @throws InvalidDataException */
    public static function fromSubjectToken(
        string $subject,
        string $token,
        Settings $settings,
        Database $database
    ): ?self {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from($database->prefix . 'token')
                ->where(field('subject')->eq($subject))
                ->andWhere(field('token')->eq($token))
        );

        /** @var array<string, string|null>|null $row */
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (\is_array($row)) {
            return self::fromRow($row, $settings, $database);
        }

        return null;
    }

    /** @throws \PDOException */
    public function remove(): void
    {
        $this->database->delete('token', [
            'user_id' => $this->user_id,
            'subject' => $this->subject,
        ]);
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     */
    public function save(): void
    {
        $this->remove();

        $this->database->insert('token', [
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'token' => $this->token,
            'expires' => $this->expires->format('Y-m-d H:i:s'),
        ]);
    }
}
