<?php

namespace App\Service;

use App\Document\Credit;
use Doctrine\ODM\MongoDB\DocumentManager;

class CreditService
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function getOrCreateCredit(int $userId): Credit
    {
        $credit = $this->dm->getRepository(Credit::class)->findOneBy(['userId' => $userId]);
        if (!$credit) {
            $credit = (new Credit())->setUserId($userId)->setAmount(20); 
            $this->dm->persist($credit);
            $this->dm->flush();
        }
        return $credit;
    }

    public function addCredits(int $userId, float $amount): void
    {
        $credit = $this->getOrCreateCredit($userId);
        $credit->addCredits($amount);
        $this->dm->flush();
    }

    public function removeCredits(int $userId, float $amount): bool
    {
        $credit = $this->getOrCreateCredit($userId);
        if ($credit->getAmount() < $amount) {
            return false;
        }

        $credit->removeCredits($amount);
        $this->dm->flush();
        return true;
    }

    public function getUserCredits(int $userId): float
    {
        return $this->getOrCreateCredit($userId)->getAmount();
    }
}
