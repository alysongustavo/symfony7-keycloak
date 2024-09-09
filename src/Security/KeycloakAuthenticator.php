<?php

namespace App\Security;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class KeycloakAuthenticator extends OAuth2Authenticator
{

    private $provider;

    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry, ManagerRegistry $registry)
    {
        $this->clientRegistry = $clientRegistry;
        $this->provider = $this->clientRegistry->getClient('keycloak');
        $this->provider->setAsStateless();
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function getCredentials(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (str_starts_with($authorizationHeader, 'Bearer ')) {
            return substr($authorizationHeader, 7);
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function getClientRegistry(): \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('keycloak');
    }

    public function authenticate(Request $request): Passport
    {

        try {
            $credential = $this->getCredentials($request);
            // Cria um objeto AccessToken manualmente usando o JWT
            $accessToken = new AccessToken(['access_token' => $credential]);
            $resourceOwner = $this->provider->fetchUserFromToken($accessToken);

            // 3. Decodificar o token e pegar o ID do usuário
            $userInfo = $resourceOwner->toArray();

            $userId = $userInfo['sub']; // Usualmente o campo 'sub' contém o ID do usuário

            // Extraindo roles do realm
            $realmRoles = $userInfo['realm_access']['roles'] ?? [];

            // Extraindo roles do cliente (ajuste conforme a estrutura do seu token)
            $clientRoles = $userInfo['resource_access']['roles'] ?? [];

            // Combinar todas as roles (roles do realm e do client)
            $roles = [];
            $roles = array_merge($realmRoles, $clientRoles);

            // Cria um UserBadge passando o identificador do usuário (ex.: userId)
            $userBadge = new UserBadge($userId, function ($userIdentifier) use ($roles) {
                // Carrega o usuário diretamente do banco de dados ou do token JWT
                $user = new User();

                // Itera sobre cada role, cria um objeto Role e o adiciona ao usuário
                foreach ($roles as $roleName) {
                    $role = new Role(); // Supondo que você tenha uma classe Role
                    $role->setName($roleName); // Defina o nome da role
                    $user->addRole($role); // Adiciona o objeto Role ao usuário
                }

                return $user;  // Retorna o objeto User que implementa UserInterface
            });

            $selfValidatingPassport = new SelfValidatingPassport($userBadge);

            return $selfValidatingPassport;

        } catch (\Exception $e) {
            throw new AuthenticationException('Token JWT inválido: ' . $e->getMessage());
        }
    }
}