<?php

namespace App\Services;

use App\Repositories\LimsRepository;

/**
 * Class LimsService
 * @package App\Services
 */
class LimsService
{
    protected LimsRepository $repository;

    /**
     * LimsService constructor.
     * @param LimsRepository $repository
     */
    public function __construct(LimsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function processSolicitudCiegaRTFM60(array $data)
    {
        // Lógica de procesamiento de solicitud ciega
        return $this->repository->saveSolicitud($data);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function generateComprobanteRTFM13(int $id)
    {
        // Lógica de generación de comprobante
        return $this->repository->getComprobante($id);
    }
}
