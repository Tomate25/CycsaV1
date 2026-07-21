<?php

namespace App\Services;

use App\Repositories\ClienteRepository;

/**
 * Class ClienteService
 * @package App\Services
 */
class ClienteService
{
    protected ClienteRepository $repository;

    public function __construct(ClienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): array
    {
        return $this->repository->all();
    }
}
