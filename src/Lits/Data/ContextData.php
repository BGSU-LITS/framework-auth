<?php

declare(strict_types=1);

namespace Lits\Data;

use Jasny\Auth\ContextInterface;
use Lits\Database;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

use function Latitude\QueryBuilder\field;

final class ContextData extends DatabaseData implements ContextInterface
{
    public ?int $user_id = null;
    public ?string $role_id = null;

    public function __construct(
        public string $context,
        Settings $settings,
        Database $database,
    ) {
        parent::__construct($settings, $database);
    }

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database,
    ): self {
        if (!isset($row['context'])) {
            throw new InvalidDataException('The context must be specified');
        }

        $context = new static(\trim($row['context']), $settings, $database);
        $context->user_id = self::findRowInt($row, 'user_id');
        $context->setRoleIdFromRow($row);

        return $context;
    }

    public function findRoleId(int $user_id): void
    {
        if ($this->user_id === $user_id) {
            return;
        }

        $this->user_id = $user_id;
        $this->role_id = null;

        $statement = $this->database->execute(
            $this->database->query
                ->select()
                ->from($this->database->prefix . 'context')
                ->where(field('user_id')->eq($this->user_id))
                ->andWhere(field('context')->eq($this->context)),
        );

        /** @var array<string, string|null>|null $row */
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (\is_array($row)) {
            $this->setRoleIdFromRow($row);
        }
    }

    #[\Override]
    public function getAuthId(): ?string
    {
        return null;
    }

    /** @param array<string, string|null> $row */
    public function setRoleIdFromRow(array $row): void
    {
        $this->role_id = self::findRowString($row, 'role_id');

        if ($this->role_id === '') {
            $this->role_id = null;
        }
    }
}
