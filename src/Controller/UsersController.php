<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Service\KeycloakService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class  UsersController extends AbstractController
{
    private $keycloakService;
    private $entityManager;

    public function __construct(KeycloakService $keycloakService, EntityManagerInterface $entityManager)
    {
        $this->keycloakService = $keycloakService;
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'app_users_gets', methods: ['GET'])]
    public function listUser()
    {
        return new JsonResponse(['message' => 'List of users']);
    }

    #[Route('/admin/users', name: 'app_users_admin_gets', methods: ['GET'])]
    public function listUserAdmin()
    {
        return new JsonResponse(['message' => 'List of users admin']);
    }

    #[Route('/users', name: 'app_users_client', methods: ['POST'])]
    public function createUserClient(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$email || !$password) {
            return new JsonResponse(['error' => 'Invalid input'], 400);
        }

        // Criar o usuário no Keycloak
        $idKeycloak = $this->keycloakService->createUser($username, $email, $password);

        if (!$idKeycloak) {
            return new JsonResponse(['error' => 'Failed to create user in Keycloak'], 500);
        }

        //$this->keycloakService->assignRoleToUser($idKeycloak, ['ROLE_CLIENT', 'ROLE_FUNC']);

        // Criar o usuário no banco de dados local
        $user = new User();
        $user->setIdKeycloak($idKeycloak);

        // Adicionar as roles locais
        $roleClient = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_USER']);
        //$roleFunc = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_FUNC']);

        if ($roleClient) {
            $user->addRole($roleClient);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User created successfully', 'idKeycloak' => $idKeycloak], 201);
    }

    #[Route('/admin/users', name: 'app_users_admin', methods: ['POST'])]
    public function createUserAdmin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$email || !$password) {
            return new JsonResponse(['error' => 'Invalid input'], 400);
        }

        // Criar o usuário no Keycloak
        $idKeycloak = $this->keycloakService->createUser($username, $email, $password);

        if (!$idKeycloak) {
            return new JsonResponse(['error' => 'Failed to create user in Keycloak'], 500);
        }

        //$this->keycloakService->assignRoleToUser($idKeycloak, ['ROLE_CLIENT', 'ROLE_FUNC']);

        // Criar o usuário no banco de dados local
        $user = new User();
        $user->setIdKeycloak($idKeycloak);

        // Adicionar as roles locais
        $roleClient = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_USER']);
        $roleFunc = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_FUNC']);

        if ($roleFunc) {
            $user->addRole($roleFunc);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User created successfully', 'idKeycloak' => $idKeycloak], 201);
    }
}
