<?php

namespace Cycsa\App\Services;

use Cycsa\App\Repositories\ClienteRepository;

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
