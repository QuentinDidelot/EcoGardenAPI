<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        $conseils = [
            1 => "Conseil de janvier : Préparez votre jardin pour l'hiver.",
            2 => "Conseil de février : Taillez les arbres et arbustes.",
            3 => "Conseil de mars : Semez les premières plantes.",
            4 => "Conseil d'avril : Plantez les légumes résistants.",
            5 => "Conseil de mai : Arrosez régulièrement vos plantations.",
            6 => "Conseil de juin : Protégez vos plantes des insectes.",
            7 => "Conseil de juillet : Récoltez vos fruits et légumes.",
            8 => "Conseil d'août : Faites attention à la sécheresse.",
            9 => "Conseil de septembre : Préparez le jardin pour l'automne.",
            10 => "Conseil d'octobre : Plantez des bulbes pour le printemps.",
            11 => "Conseil de novembre : Protégez vos plantes du gel.",
            12 => "Conseil de décembre : Nettoyez vos outils de jardin."
        ];

        foreach ($conseils as $mois => $texte) {
            $advice = new Advice();
            $advice->setAdviceText($texte);
            $advice->setMonth($mois);
            
            $manager->persist($advice);
        }

        $usersData = [
            [
                'email' => 'admin@admin.com',
                'password' => 'admin123',
                'roles' => ['ROLE_ADMIN'],
                'postCode' => 71300
            ],
            [
                'email' => 'user@user.com',
                'password' => 'user123',
                'roles' => ['ROLE_USER'],
                'postCode' => 33000
            ],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPostCode($userData['postCode']);

            // Hachage du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
