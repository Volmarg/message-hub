<?php

namespace App\Action\API\External\Security;

use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/external", name: "api_external_")]
class AuthorizationController
{
    /**
     * This route is only used in the Lexit package by some internal code - it crashes without this route
     *
     * @return array
     */
    #[Route('/auth/login', name: 'login', methods: ['POST'])]
    public function apiLogin(): array
    {
        return [];
    }
}
