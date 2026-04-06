<?php

namespace App\Entity;

interface TimestampableInterface
{
    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): static;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static;
}
