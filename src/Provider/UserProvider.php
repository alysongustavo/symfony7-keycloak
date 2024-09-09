<?php

namespace App\Provider;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{

    private ManagerRegistry $em;

    public function __construct(ManagerRegistry $registry)
    {
        $this->em = $registry;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // TODO: Implement refreshUser() method.
    }
    public function supportsClass(string $class): bool
    {
        // TODO: Implement supportsClass() method.
    }
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->em->getRepository(User::class)->findOneBy(['idKeycloak' => $identifier]);
    }
}