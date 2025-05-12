<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Credit
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: 'int')]
    private int $userId;

    #[MongoDB\Field(type: 'float')]
    private float $amount = 0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function addCredits(float $credits): self
    {
        $this->amount += $credits;
        return $this;
    }

    public function removeCredits(float $credits): self
    {
        $this->amount -= $credits;
        return $this;
    }
}
