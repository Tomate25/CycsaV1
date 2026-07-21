<?php

namespace App\Services;

use App\Repositories\CotizacionRepository;

/**
 * Class CotizacionService
 * @package App\Services
 */
class CotizacionService
{
    protected CotizacionRepository $repository;

    /**
     * CotizacionService constructor.
     * @param CotizacionRepository $repository
     */
    public function __construct(CotizacionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->repository->all();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
