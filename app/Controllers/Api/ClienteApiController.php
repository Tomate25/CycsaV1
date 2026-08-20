<?php

namespace Cycsa\App\Controllers\Api;

use Cycsa\App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class ClienteApiController
 * @package App\Controllers\Api
 */
class ClienteApiController
{
    protected ClienteService $service;

    public function __construct(ClienteService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }
}
