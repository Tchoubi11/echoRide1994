<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Entity\Utilisateur;
use App\Document\Credit;

class UserMigrationService
{
    private $em;
    private $mongoDM;

    public function __construct(EntityManagerInterface $em, DocumentManager $mongoDM)
    {
        $this->em = $em;
        $this->mongoDM = $mongoDM;
    }

    public function migrateUsersToMongoDB()
{
    
    $users = $this->em->getRepository(Utilisateur::class)->findBy([
        'id' => [1, 2, 4, 5] 
    ]);

    foreach ($users as $user) {
        // Crée un nouveau document Credit pour chaque utilisateur
        $credit = new Credit();
        $credit->setUserId($user->getId());
        $credit->setAmount(10); // Ajouter 10 crédits par défaut

        // Persist dans MongoDB
        $this->mongoDM->persist($credit);
    }

    // Flush pour enregistrer dans MongoDB
    $this->mongoDM->flush();

    return count($users) . " utilisateurs migrés vers MongoDB avec des crédits.";
}

}
