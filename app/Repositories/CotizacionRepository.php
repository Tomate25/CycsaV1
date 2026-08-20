<?php

namespace Cycsa\App\Repositories;

/**
 * Class CotizacionRepository
 * @package App\Repositories
 */
class CotizacionRepository
{
    public function all(): array
    {
        return [];
    }

    public function find(int $id)
    {
        return null;
    }

    public function create(array $data)
    {
        return true;
    }

    public function update(int $id, array $data)
    {
        return true;
    }

    public function delete(int $id): bool
    {
        return true;
    }
}
