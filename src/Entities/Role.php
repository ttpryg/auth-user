<?php

namespace Ttpryg\AuthUser\Entities;

use DateTimeImmutable;
use DateTimeInterface;

class Role
{
    private readonly ?DateTimeInterface $createdAt;

    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly ?string $description = null,
        private int|string|null $id = null,
        ?DateTimeInterface $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
