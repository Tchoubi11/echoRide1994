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

    /**
     * Crée ou récupère un document Credit MongoDB pour un utilisateur donné,
     * avec un montant initial si le document n'existe pas.
     *
     * @param int|string $userId
     * @param float $initialAmount
     * @return Credit
     */
    public function getOrCreateCredit(int|string $userId, float $initialAmount = 20): Credit
{
    if (empty($userId) || $userId === 0 || $userId === '0') {
        throw new \InvalidArgumentException("L'ID utilisateur est invalide pour la création de crédit.");
    }

    $credit = $this->dm->getRepository(Credit::class)->findOneBy(['userId' => $userId]);

    if (!$credit) {
        $credit = new Credit();
        $credit->setUserId($userId);
        $credit->setAmount($initialAmount);
        $this->dm->persist($credit);
        $this->dm->flush();
    }

    return $credit;
}

    public function addCredits(int|string $userId, float $amount): void
    {
        $credit = $this->getOrCreateCredit($userId);
        $credit->addCredits($amount);
        $this->dm->flush();
    }

    public function removeCredits(int|string $userId, float $amount): bool
    {
        $credit = $this->getOrCreateCredit($userId);
        if ($credit->getAmount() < $amount) {
            return false;
        }
        $credit->removeCredits($amount);
        $this->dm->flush();
        return true;
    }

    public function getUserCredits(int|string $userId): float
    {
        $credit = $this->getOrCreateCredit($userId);
        return $credit->getAmount();
    }
}
