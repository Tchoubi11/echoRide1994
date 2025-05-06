<?php


namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Utilisateur();
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setEmail('admin@echoride.com');
        $admin->setPseudo('admin');
        $admin->setTelephone('0102030405');
        $admin->setAdresse('HQ EcoRide');
        $admin->setDateNaissance(new \DateTime('1990-01-01'));
        $admin->setTypeUtilisateur('admin');
        $admin->setRoles(['ROLE_ADMIN']);

        // Mot de passe
        $hashedPassword = $this->hasher->hashPassword($admin, 'Admin22.');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);
        $manager->flush();
    }
}
