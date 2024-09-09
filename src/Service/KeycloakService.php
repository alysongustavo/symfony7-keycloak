<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class KeycloakService
{

    private $client;
    private $keycloakUrl;
    private $realm;
    private $clientId;
    private $clientSecret;

    private $adminToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->keycloakUrl = $_ENV['KEYCLOAK_URL'];
        $this->realm = $_ENV['KEYCLOAK_REALM'];
        $this->clientId = $_ENV['KEYCLOAK_CLIENT_ID'];
        $this->clientSecret = $_ENV['KEYCLOAK_CLIENT_SECRET'];
        $this->adminToken = $this->getAdminToken();  // Obtém o token de admin
    }

    public function getAdminToken(): ?string
    {
        try {
            $response = $this->client->post($this->keycloakUrl . '/realms/' . $this->realm . '/protocol/openid-connect/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;
        } catch (GuzzleException $e) {
            return null;
        }
    }

    public function createUser(string $username, string $email, string $password): ?string
    {
        $accessToken = $this->getAdminToken();

        if (!$accessToken) {
            return null;
        }

        try {
            $response = $this->client->post($this->keycloakUrl . '/admin/realms/' . $this->realm . '/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $username,
                    'email' => $email,
                    'enabled' => true,
                    'emailVerified' => true,
                    'credentials' => [
                        [
                            'type' => 'password',
                            'value' => $password,
                            'temporary' => false,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 201) {
                // Extrai o ID do Keycloak da resposta do cabeçalho
                $locationHeader = $response->getHeader('Location')[0];
                $idKeycloak = basename($locationHeader); // ID é a última parte da URL
                return $idKeycloak;
            }

            return null;
        } catch (GuzzleException $e) {
            dd($e);
            return null;
        }
    }

    public function assignRoleToUser(string $userId, array $roles)
    {
        foreach ($roles as $roleName) {
            // Obter a role do Keycloak
            $response = $this->client->get($this->keycloakUrl . '/admin/realms/' . $this->realm . '/roles/' . $roleName, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->adminToken,
                ],
            ]);

            $role = json_decode($response->getBody(), true);

            $roles[] = [
                'id' => $role['id'],
                'name' => $role['name'],
            ];

            // Atribuir a role ao usuário
            $responsePost = $this->client->post($this->keycloakUrl . '/admin/realms/' . $this->realm . '/users/' . $userId . '/role-mappings/realm', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->adminToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    $roles,
                ],
            ]);

            dd($responsePost);
        }
    }



}