<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    private $root_directory;
    private UserPasswordHasherInterface $hasher;
    public function __construct(string $root_directory, UserPasswordHasherInterface $hasher)
    {
        $this->root_directory = $root_directory;
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $userDatas = json_decode(file_get_contents($this->root_directory . '/src/DataFixtures/jsons/user.json'));

        foreach ($userDatas as $userData) {

            $user = new User();
            $user->setFirstname($userData->firstname);
            $user->setLastname($userData->lastname);
            $user->setUsername($userData->username);
            $user->setRoles($userData->roles);
            $user->setPassword($this->hasher->hashPassword($user, $userData->password));
            
            // Save the user if has no parent (ROLE CLIENT)
            if(is_null($userData->parent)){
                $manager->persist($user);
                $manager->flush();
            }else{
                // Link the user if has parent (ROLE CLIENT USER)
                $user->setParent($manager->getRepository(User::class)->find($userData->parent));
            }
            $manager->persist($user);
        }
        $manager->flush();
    }

}