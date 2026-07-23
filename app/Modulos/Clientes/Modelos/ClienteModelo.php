<?php

namespace Cycsa\Modulos\Clientes\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class ClienteModelo extends ModeloBase {
    
    // 🔍 OBTENER TODOS LOS CLIENTES (CON FILTRO DE BÚSQUEDA)
    public function obtenerTodos(string $busqueda = ''): array {
        if ($busqueda !== '') {
            $sql = "SELECT * 
                    FROM clientes 
                    WHERE nombre_razon_social LIKE :q1 
                       OR identificacion LIKE :q2 
                       OR email LIKE :q3
                       OR vendedor LIKE :q4
                       OR clasificacion LIKE :q5
                    ORDER BY id ASC";
            
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino,
                'q4' => $termino,
                'q5' => $termino
            ]);
        } else {
            $sql = "SELECT * FROM clientes ORDER BY id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✏️ OBTENER UN SOLO CLIENTE POR SU ID
    public function obtenerPorId(int $id) {
        $sql = "SELECT * FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✏️ OBTENER UN SOLO CLIENTE POR SU IDENTIFICACIÓN (RUC o Cédula)
    public function obtenerPorIdentificacion(string $identificacion) {
        $sql = "SELECT * FROM clientes WHERE identificacion = :identi1 OR numero_ruc = :identi2 OR numero_cedula = :identi3 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $val = trim($identificacion);
        $stmt->execute([
            'identi1' => $val,
            'identi2' => $val,
            'identi3' => $val
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🛡️ CONTROL DE DUPLICADOS: Verificar si el correo ya existe
    public function emailExiste(string $email, int $id_excluir = 0): bool {
        if (empty(trim($email))) return false;
        $sql = "SELECT COUNT(*) FROM clientes WHERE email = :email AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email), 'id' => $id_excluir]); 
        return $stmt->fetchColumn() > 0;
    }

    // 🛡️ CONTROL DE DUPLICADOS: Verificar si la identificación ya existe
    public function identificacionExiste(string $identificacion, int $id_excluir = 0): bool {
        if (empty(trim($identificacion))) return false;
        $sql = "SELECT COUNT(*) FROM clientes WHERE identificacion = :identificacion AND id != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['identificacion' => trim($identificacion), 'id' => $id_excluir]);
        return $stmt->fetchColumn() > 0;
    }

    // 💾 GUARDAR NUEVO CLIENTE
    public function guardar(array $datos): bool {
        // Generar el nombre razon social dinámicamente si es Natural
        $nombre_razon_social = trim($datos['nombre_cliente'] ?? '');
        if (($datos['tipo_cliente'] ?? '') === 'Natural') {
            $parts = [trim($datos['nombre_cliente'] ?? '')];
            if (!empty($datos['primer_apellido'])) {
                $parts[] = trim($datos['primer_apellido']);
            }
            if (!empty($datos['segundo_apellido'])) {
                $parts[] = trim($datos['segundo_apellido']);
            }
            $nombre_razon_social = implode(' ', $parts);
        } else {
            $nombre_razon_social = trim($datos['nombre_cliente'] ?? '');
        }

        // Definir la identificacion principal
        $identificacion = '';
        if (!empty($datos['numero_ruc'])) {
            $identificacion = trim($datos['numero_ruc']);
        } elseif (!empty($datos['numero_cedula'])) {
            $identificacion = trim($datos['numero_cedula']);
        }

        $sql = "INSERT INTO clientes (
                    tipo_cliente, codigo_cliente, activo, nombre_razon_social, nombre_cliente,
                    primer_apellido, segundo_apellido, sucursal_sede, clasificacion, sub_clasificacion,
                    vendedor, numero_cedula, numero_ruc, identificacion, contacto, direccion, notas,
                    telefono, fax, email, cuenta_cxc, cuenta_cxp, exonerado_impuestos, cuenta_ingresos_exonerados,
                    exportacion, tipo_moneda, activar_prorroga_credito, limite_credito, dias_credito,
                    facturas_vencidas_permitidas, descuento_automatico, porcentaje_descuento, predeterminado_pos,
                    facturacion_correo, contacto_nombre, contacto_apellido, contacto_cargo, contacto_correo
                ) VALUES (
                    :tipo_cliente, :codigo_cliente, :activo, :nombre_razon_social, :nombre_cliente,
                    :primer_apellido, :segundo_apellido, :sucursal_sede, :clasificacion, :sub_clasificacion,
                    :vendedor, :numero_cedula, :numero_ruc, :identificacion, :contacto, :direccion, :notas,
                    :telefono, :fax, :email, :cuenta_cxc, :cuenta_cxp, :exonerado_impuestos, :cuenta_ingresos_exonerados,
                    :exportacion, :tipo_moneda, :activar_prorroga_credito, :limite_credito, :dias_credito,
                    :facturas_vencidas_permitidas, :descuento_automatico, :porcentaje_descuento, :predeterminado_pos,
                    :facturacion_correo, :contacto_nombre, :contacto_apellido, :contacto_cargo, :contacto_correo
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo_cliente'                 => !empty($datos['tipo_cliente']) ? trim($datos['tipo_cliente']) : 'Jurídico',
            'codigo_cliente'               => !empty($datos['codigo_cliente']) ? trim($datos['codigo_cliente']) : null,
            'activo'                       => isset($datos['activo']) ? (int)$datos['activo'] : 1,
            'nombre_razon_social'          => $nombre_razon_social,
            'nombre_cliente'               => trim($datos['nombre_cliente'] ?? ''),
            'primer_apellido'              => !empty($datos['primer_apellido']) ? trim($datos['primer_apellido']) : null,
            'segundo_apellido'             => !empty($datos['segundo_apellido']) ? trim($datos['segundo_apellido']) : null,
            'sucursal_sede'                => !empty($datos['sucursal_sede']) ? trim($datos['sucursal_sede']) : null,
            'clasificacion'                => !empty($datos['clasificacion']) ? trim($datos['clasificacion']) : null,
            'sub_clasificacion'            => !empty($datos['sub_clasificacion']) ? trim($datos['sub_clasificacion']) : null,
            'vendedor'                     => !empty($datos['vendedor']) ? trim($datos['vendedor']) : null,
            'numero_cedula'                => !empty($datos['numero_cedula']) ? trim($datos['numero_cedula']) : null,
            'numero_ruc'                   => !empty($datos['numero_ruc']) ? trim($datos['numero_ruc']) : null,
            'identificacion'               => $identificacion,
            'contacto'                     => !empty($datos['contacto']) ? trim($datos['contacto']) : null,
            'direccion'                    => !empty($datos['direccion']) ? trim($datos['direccion']) : null,
            'notas'                        => !empty($datos['notas']) ? trim($datos['notas']) : null,
            'telefono'                     => !empty($datos['telefono']) ? trim($datos['telefono']) : null,
            'fax'                          => !empty($datos['fax']) ? trim($datos['fax']) : null,
            'email'                        => !empty($datos['email']) ? trim($datos['email']) : null,
            'cuenta_cxc'                   => !empty($datos['cuenta_cxc']) ? trim($datos['cuenta_cxc']) : '1010201 / CLIENTES NACIONALES',
            'cuenta_cxp'                   => !empty($datos['cuenta_cxp']) ? trim($datos['cuenta_cxp']) : '2010303 / ANTICIPO CLIENTES',
            'exonerado_impuestos'          => isset($datos['exonerado_impuestos']) ? (int)$datos['exonerado_impuestos'] : 0,
            'cuenta_ingresos_exonerados'   => !empty($datos['cuenta_ingresos_exonerados']) ? trim($datos['cuenta_ingresos_exonerados']) : null,
            'exportacion'                  => isset($datos['exportacion']) ? (int)$datos['exportacion'] : 0,
            'tipo_moneda'                  => isset($datos['tipo_moneda']) ? (int)$datos['tipo_moneda'] : 1,
            'activar_prorroga_credito'     => isset($datos['activar_prorroga_credito']) ? (int)$datos['activar_prorroga_credito'] : 0,
            'limite_credito'               => !empty($datos['limite_credito']) ? (float)str_replace(',', '', $datos['limite_credito']) : 0.00,
            'dias_credito'                 => !empty($datos['dias_credito']) ? (int)$datos['dias_credito'] : 0,
            'facturas_vencidas_permitidas' => !empty($datos['facturas_vencidas_permitidas']) ? (int)$datos['facturas_vencidas_permitidas'] : 0,
            'descuento_automatico'         => isset($datos['descuento_automatico']) ? (int)$datos['descuento_automatico'] : 0,
            'porcentaje_descuento'         => !empty($datos['porcentaje_descuento']) ? (float)$datos['porcentaje_descuento'] : 0.00,
            'predeterminado_pos'           => isset($datos['predeterminado_pos']) ? (int)$datos['predeterminado_pos'] : 0,
            'facturacion_correo'           => isset($datos['facturacion_correo']) ? (int)$datos['facturacion_correo'] : 0,
            'contacto_nombre'              => !empty($datos['contacto_nombre']) ? trim($datos['contacto_nombre']) : null,
            'contacto_apellido'            => !empty($datos['contacto_apellido']) ? trim($datos['contacto_apellido']) : null,
            'contacto_cargo'               => !empty($datos['contacto_cargo']) ? trim($datos['contacto_cargo']) : null,
            'contacto_correo'              => !empty($datos['contacto_correo']) ? trim($datos['contacto_correo']) : null
        ]);
    }

    // ✏️ ACTUALIZAR CLIENTE EXISTENTE
    public function actualizar(int $id, array $datos): bool {
        // Generar el nombre razon social dinámicamente si es Natural
        $nombre_razon_social = trim($datos['nombre_cliente'] ?? '');
        if (($datos['tipo_cliente'] ?? '') === 'Natural') {
            $parts = [trim($datos['nombre_cliente'] ?? '')];
            if (!empty($datos['primer_apellido'])) {
                $parts[] = trim($datos['primer_apellido']);
            }
            if (!empty($datos['segundo_apellido'])) {
                $parts[] = trim($datos['segundo_apellido']);
            }
            $nombre_razon_social = implode(' ', $parts);
        } else {
            $nombre_razon_social = trim($datos['nombre_cliente'] ?? '');
        }

        // Definir la identificacion principal
        $identificacion = '';
        if (!empty($datos['numero_ruc'])) {
            $identificacion = trim($datos['numero_ruc']);
        } elseif (!empty($datos['numero_cedula'])) {
            $identificacion = trim($datos['numero_cedula']);
        }

        $sql = "UPDATE clientes SET 
                    tipo_cliente = :tipo_cliente, 
                    codigo_cliente = :codigo_cliente, 
                    activo = :activo, 
                    nombre_razon_social = :nombre_razon_social, 
                    nombre_cliente = :nombre_cliente,
                    primer_apellido = :primer_apellido, 
                    segundo_apellido = :segundo_apellido, 
                    sucursal_sede = :sucursal_sede, 
                    clasificacion = :clasificacion, 
                    sub_clasificacion = :sub_clasificacion,
                    vendedor = :vendedor, 
                    numero_cedula = :numero_cedula, 
                    numero_ruc = :numero_ruc, 
                    identificacion = :identificacion, 
                    contacto = :contacto, 
                    direccion = :direccion, 
                    notas = :notas,
                    telefono = :telefono, 
                    fax = :fax, 
                    email = :email, 
                    cuenta_cxc = :cuenta_cxc, 
                    cuenta_cxp = :cuenta_cxp, 
                    exonerado_impuestos = :exonerado_impuestos, 
                    cuenta_ingresos_exonerados = :cuenta_ingresos_exonerados,
                    exportacion = :exportacion, 
                    tipo_moneda = :tipo_moneda, 
                    activar_prorroga_credito = :activar_prorroga_credito, 
                    limite_credito = :limite_credito, 
                    dias_credito = :dias_credito,
                    facturas_vencidas_permitidas = :facturas_vencidas_permitidas, 
                    descuento_automatico = :descuento_automatico, 
                    porcentaje_descuento = :porcentaje_descuento, 
                    predeterminado_pos = :predeterminado_pos,
                    facturacion_correo = :facturacion_correo, 
                    contacto_nombre = :contacto_nombre, 
                    contacto_apellido = :contacto_apellido, 
                    contacto_cargo = :contacto_cargo, 
                    contacto_correo = :contacto_correo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo_cliente'                 => !empty($datos['tipo_cliente']) ? trim($datos['tipo_cliente']) : 'Jurídico',
            'codigo_cliente'               => !empty($datos['codigo_cliente']) ? trim($datos['codigo_cliente']) : null,
            'activo'                       => isset($datos['activo']) ? (int)$datos['activo'] : 1,
            'nombre_razon_social'          => $nombre_razon_social,
            'nombre_cliente'               => trim($datos['nombre_cliente'] ?? ''),
            'primer_apellido'              => !empty($datos['primer_apellido']) ? trim($datos['primer_apellido']) : null,
            'segundo_apellido'             => !empty($datos['segundo_apellido']) ? trim($datos['segundo_apellido']) : null,
            'sucursal_sede'                => !empty($datos['sucursal_sede']) ? trim($datos['sucursal_sede']) : null,
            'clasificacion'                => !empty($datos['clasificacion']) ? trim($datos['clasificacion']) : null,
            'sub_clasificacion'            => !empty($datos['sub_clasificacion']) ? trim($datos['sub_clasificacion']) : null,
            'vendedor'                     => !empty($datos['vendedor']) ? trim($datos['vendedor']) : null,
            'numero_cedula'                => !empty($datos['numero_cedula']) ? trim($datos['numero_cedula']) : null,
            'numero_ruc'                   => !empty($datos['numero_ruc']) ? trim($datos['numero_ruc']) : null,
            'identificacion'               => $identificacion,
            'contacto'                     => !empty($datos['contacto']) ? trim($datos['contacto']) : null,
            'direccion'                    => !empty($datos['direccion']) ? trim($datos['direccion']) : null,
            'notas'                        => !empty($datos['notas']) ? trim($datos['notas']) : null,
            'telefono'                     => !empty($datos['telefono']) ? trim($datos['telefono']) : null,
            'fax'                          => !empty($datos['fax']) ? trim($datos['fax']) : null,
            'email'                        => !empty($datos['email']) ? trim($datos['email']) : null,
            'cuenta_cxc'                   => !empty($datos['cuenta_cxc']) ? trim($datos['cuenta_cxc']) : '1010201 / CLIENTES NACIONALES',
            'cuenta_cxp'                   => !empty($datos['cuenta_cxp']) ? trim($datos['cuenta_cxp']) : '2010303 / ANTICIPO CLIENTES',
            'exonerado_impuestos'          => isset($datos['exonerado_impuestos']) ? (int)$datos['exonerado_impuestos'] : 0,
            'cuenta_ingresos_exonerados'   => !empty($datos['cuenta_ingresos_exonerados']) ? trim($datos['cuenta_ingresos_exonerados']) : null,
            'exportacion'                  => isset($datos['exportacion']) ? (int)$datos['exportacion'] : 0,
            'tipo_moneda'                  => isset($datos['tipo_moneda']) ? (int)$datos['tipo_moneda'] : 1,
            'activar_prorroga_credito'     => isset($datos['activar_prorroga_credito']) ? (int)$datos['activar_prorroga_credito'] : 0,
            'limite_credito'               => !empty($datos['limite_credito']) ? (float)str_replace(',', '', $datos['limite_credito']) : 0.00,
            'dias_credito'                 => !empty($datos['dias_credito']) ? (int)$datos['dias_credito'] : 0,
            'facturas_vencidas_permitidas' => !empty($datos['facturas_vencidas_permitidas']) ? (int)$datos['facturas_vencidas_permitidas'] : 0,
            'descuento_automatico'         => isset($datos['descuento_automatico']) ? (int)$datos['descuento_automatico'] : 0,
            'porcentaje_descuento'         => !empty($datos['porcentaje_descuento']) ? (float)$datos['porcentaje_descuento'] : 0.00,
            'predeterminado_pos'           => isset($datos['predeterminado_pos']) ? (int)$datos['predeterminado_pos'] : 0,
            'facturacion_correo'           => isset($datos['facturacion_correo']) ? (int)$datos['facturacion_correo'] : 0,
            'contacto_nombre'              => !empty($datos['contacto_nombre']) ? trim($datos['contacto_nombre']) : null,
            'contacto_apellido'            => !empty($datos['contacto_apellido']) ? trim($datos['contacto_apellido']) : null,
            'contacto_cargo'               => !empty($datos['contacto_cargo']) ? trim($datos['contacto_cargo']) : null,
            'contacto_correo'              => !empty($datos['contacto_correo']) ? trim($datos['contacto_correo']) : null,
            'id'                           => $id
        ]);
    }
}