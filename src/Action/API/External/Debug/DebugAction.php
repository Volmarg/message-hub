<?php

namespace App\Action\API\External\Debug;

use App\Attributes\IsApiRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains some debugging logic
 */
#[Route("/api/external", name: "api_external_")]
class DebugAction extends AbstractController
{

    #[Route("/debug/ping", name: "debug_pint", methods: [Request::METHOD_GET])]
    #[IsApiRoute]
    public function ping(): JsonResponse
    {
        return $this->json(['ok']);
    }
}