-- SCHEMA EXPORT FOR DRAWSQL (CYCSA ERP & LIMS)

CREATE TABLE `bancos_cuentas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta_contable` int(11) DEFAULT NULL,
  `banco_nombre` varchar(150) NOT NULL,
  `numero_cuenta` varchar(100) NOT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'C$',
  `saldo_inicial` decimal(14,2) NOT NULL DEFAULT 0.00,
  `saldo_actual` decimal(14,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bancos_cuenta_contable` (`id_cuenta_contable`),
  CONSTRAINT `fk_bancos_cuenta_contable` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bancos_transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_banco_cuenta` int(11) NOT NULL,
  `tipo_transaccion` enum('DEPOSITO','RETIRO','CHEQUE','TRANSFERENCIA') NOT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `beneficiario` varchar(255) DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('Emitido','Cobrado','Anulado','Conciliado') NOT NULL DEFAULT 'Emitido',
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_transacciones_banco` (`id_banco_cuenta`),
  CONSTRAINT `fk_transacciones_banco` FOREIGN KEY (`id_banco_cuenta`) REFERENCES `bancos_cuentas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bitacora` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_nombre` varchar(150) NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `id_referencia` int(11) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_cliente` varchar(50) DEFAULT NULL,
  `codigo_cliente` varchar(50) DEFAULT NULL,
  `nombre_razon_social` varchar(150) NOT NULL,
  `identificacion` varchar(50) DEFAULT NULL COMMENT 'RUC o Cédula',
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `primer_apellido` varchar(100) DEFAULT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `sucursal_sede` varchar(100) DEFAULT NULL,
  `clasificacion` varchar(100) DEFAULT NULL,
  `sub_clasificacion` varchar(100) DEFAULT NULL,
  `vendedor` varchar(100) DEFAULT NULL,
  `numero_cedula` varchar(50) DEFAULT NULL,
  `numero_ruc` varchar(50) DEFAULT NULL,
  `contacto` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `cuenta_cxc` varchar(100) DEFAULT NULL,
  `cuenta_cxp` varchar(100) DEFAULT NULL,
  `exonerado_impuestos` tinyint(1) DEFAULT 0,
  `cuenta_ingresos_exonerados` varchar(100) DEFAULT NULL,
  `exportacion` tinyint(1) DEFAULT 0,
  `tipo_moneda` tinyint(1) DEFAULT 1,
  `activar_prorroga_credito` tinyint(1) DEFAULT 0,
  `limite_credito` decimal(15,2) DEFAULT 0.00,
  `dias_credito` int(11) DEFAULT 0,
  `facturas_vencidas_permitidas` int(11) DEFAULT 0,
  `descuento_automatico` tinyint(1) DEFAULT 0,
  `porcentaje_descuento` decimal(5,2) DEFAULT 0.00,
  `predeterminado_pos` tinyint(1) DEFAULT 0,
  `facturacion_correo` tinyint(1) DEFAULT 0,
  `contacto_nombre` varchar(100) DEFAULT NULL,
  `contacto_apellido` varchar(100) DEFAULT NULL,
  `contacto_cargo` varchar(100) DEFAULT NULL,
  `contacto_correo` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=458 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `configuracion_comercial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL COMMENT 'condicion_pago, tiempo_entrega, vigencia_oferta',
  `valor` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cotizacion_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `descripcion_ensayo` varchar(255) NOT NULL,
  `codigo_servicio` varchar(100) DEFAULT NULL,
  `norma_astm` varchar(150) DEFAULT NULL,
  `formato_reporte` varchar(150) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `resultados_json` text DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `id_cotizacion` (`id_cotizacion`),
  KEY `fk_detalles_productos` (`id_producto`),
  CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalles_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cotizacion_versiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `datos_json` longtext NOT NULL,
  `motivo_cambio` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cotizacion` (`id_cotizacion`),
  CONSTRAINT `cotizacion_versiones_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL COMMENT 'Ej: COT-2026-0001',
  `id_cliente` int(11) NOT NULL,
  `tipo_moneda` int(11) DEFAULT 1,
  `id_usuario_creador` int(11) NOT NULL,
  `id_usuario_revisor` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `atencion_a` varchar(150) NOT NULL,
  `nombre_proyecto` varchar(200) NOT NULL,
  `direccion_proyecto` text NOT NULL,
  `prioridad` enum('Normal','Media','Alta') DEFAULT 'Normal',
  `fecha_limite` date DEFAULT NULL,
  `condicion_pago` varchar(100) NOT NULL,
  `tiempo_entrega` varchar(100) DEFAULT NULL COMMENT 'Ej: 5 a 7 dias habiles',
  `vigencia_oferta` varchar(100) DEFAULT NULL COMMENT 'Ej: 15 dias calendario',
  `estado` enum('Borrador','En Revision','Observada','Aprobada Internamente','Enviada al Cliente','Aprobada por Cliente','Rechazada por Cliente') DEFAULT 'Borrador',
  `motivo_observacion` text DEFAULT NULL,
  `motivo_rechazo_cliente` text DEFAULT NULL,
  `configuracion_notas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Guarda las leyendas fijas' CHECK (json_valid(`configuracion_notas`)),
  `contactos` text DEFAULT NULL,
  `token_seguridad` varchar(64) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `descuento` decimal(12,2) DEFAULT 0.00,
  `exonerado` tinyint(1) DEFAULT 0,
  `exoneracion_no` varchar(100) DEFAULT NULL,
  `impuesto` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_entrega` date DEFAULT NULL,
  `fecha_seguimiento` date DEFAULT NULL,
  `estado_operativo` enum('Pendiente','En Proceso','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `metodo_pago` varchar(100) DEFAULT NULL,
  `id_banco_cuenta` int(11) DEFAULT NULL,
  `referencia_pago` varchar(150) DEFAULT NULL,
  `dias_credito` int(11) DEFAULT 30,
  `efectivo_vuelto` decimal(12,2) DEFAULT NULL,
  `efectivo_recibido` decimal(12,2) DEFAULT NULL,
  `monto_credito` decimal(12,2) DEFAULT 0.00,
  `monto_pago_inmediato` decimal(12,2) DEFAULT 0.00,
  `porcentaje_pago_inmediato` decimal(5,2) DEFAULT 100.00,
  `notas_operativas` text DEFAULT NULL,
  `transferencia_referencia` varchar(100) DEFAULT NULL,
  `transferencia_comprobante` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario_creador` (`id_usuario_creador`),
  KEY `id_usuario_revisor` (`id_usuario_revisor`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `cotizaciones_ibfk_3` FOREIGN KEY (`id_usuario_revisor`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cuentas_contables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('MAYOR','DETALLE') NOT NULL,
  `categoria` enum('ACTIVO','PASIVO','CAPITAL','INGRESO','EGRESO') NOT NULL,
  `id_padre` int(11) DEFAULT NULL,
  `tipo_cuenta_detalle` varchar(255) DEFAULT NULL,
  `tipo_cuenta_mayor` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_cuentas_padre` (`id_padre`),
  CONSTRAINT `fk_cuentas_padre` FOREIGN KEY (`id_padre`) REFERENCES `cuentas_contables` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=362 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cuentas_por_cobrar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) DEFAULT NULL,
  `id_cuenta_contable` int(11) DEFAULT NULL,
  `factura_numero` varchar(100) NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `saldo` decimal(14,2) NOT NULL,
  `estado` enum('Pendiente','Parcial','Pagado','Vencido') NOT NULL DEFAULT 'Pendiente',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cxc_cliente` (`id_cliente`),
  KEY `fk_cxc_cuenta_contable` (`id_cuenta_contable`),
  CONSTRAINT `fk_cxc_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_cuenta_contable` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cuentas_por_pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_nombre` varchar(255) NOT NULL,
  `id_cuenta_contable` int(11) DEFAULT NULL,
  `factura_numero` varchar(100) NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `saldo` decimal(14,2) NOT NULL,
  `estado` enum('Pendiente','Parcial','Pagado','Vencido') NOT NULL DEFAULT 'Pendiente',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cxp_cuenta_contable` (`id_cuenta_contable`),
  CONSTRAINT `fk_cxp_cuenta_contable` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ensayo_edades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `id_detalle_cotizacion` int(11) NOT NULL,
  `identificador_especimen` varchar(10) NOT NULL,
  `edad_dias` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `fecha_ensaye_real` date DEFAULT NULL,
  `carga_lbs` decimal(10,2) DEFAULT NULL,
  `area_in2` decimal(8,3) DEFAULT NULL,
  `resistencia_psi` decimal(8,2) DEFAULT NULL,
  `resistencia_kgcm2` decimal(8,2) DEFAULT NULL,
  `porcentaje_diseno` decimal(5,2) DEFAULT NULL,
  `cumple_norma` tinyint(1) DEFAULT 1,
  `estado` enum('Programado','Listo para Ensaye','Completado','Omitido') DEFAULT 'Programado',
  `usuario_ensayador` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_lote` (`id_lote`),
  KEY `id_detalle_cotizacion` (`id_detalle_cotizacion`),
  KEY `usuario_ensayador` (`usuario_ensayador`),
  CONSTRAINT `ensayo_edades_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lotes_muestras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ensayo_edades_ibfk_2` FOREIGN KEY (`id_detalle_cotizacion`) REFERENCES `cotizacion_detalles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ensayo_edades_ibfk_3` FOREIGN KEY (`usuario_ensayador`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ensayos_parametros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `edad_evaluada` int(11) NOT NULL,
  `porcentaje_minimo_esperado` decimal(5,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `ensayos_parametros_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formatos_ensayos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `codigo_formato` varchar(100) DEFAULT NULL,
  `procedimientos` text DEFAULT NULL,
  `archivo_markdown` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `hojas_solicitud` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_os` int(11) NOT NULL,
  `fecha_hora_llegada_laboratorio` datetime DEFAULT NULL,
  `codigo_documento` varchar(50) DEFAULT 'CYCSA-RT-FM-13',
  `nombre_empresa_o_cliente` varchar(255) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `direccion_proyecto` text DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `nombre_persona_entrega_muestra` varchar(150) DEFAULT NULL,
  `naturaleza_muestra` varchar(100) DEFAULT NULL,
  `procedencia_punto_muestreo` varchar(255) DEFAULT NULL,
  `nombre_persona_toma_muestra` varchar(150) DEFAULT NULL,
  `fecha_hora_toma_muestra` datetime DEFAULT NULL,
  `muestras_json` longtext DEFAULT NULL,
  `req_resistencia_concreto` tinyint(1) DEFAULT 0,
  `req_resistencia_adoquin` tinyint(1) DEFAULT 0,
  `req_resistencia_bloques` tinyint(1) DEFAULT 0,
  `req_otros_concreto` text DEFAULT NULL,
  `req_granulometria` tinyint(1) DEFAULT 0,
  `req_limites_atterberg` tinyint(1) DEFAULT 0,
  `req_humedad` tinyint(1) DEFAULT 0,
  `req_resistencia_corte` tinyint(1) DEFAULT 0,
  `req_clasificacion_sucs_hr` tinyint(1) DEFAULT 0,
  `req_proctor_sm` tinyint(1) DEFAULT 0,
  `req_infiltracion` tinyint(1) DEFAULT 0,
  `req_cbr` tinyint(1) DEFAULT 0,
  `req_densidad` tinyint(1) DEFAULT 0,
  `req_otros_suelo` text DEFAULT NULL,
  `req_otros_materiales` tinyint(1) DEFAULT 0,
  `descripcion_otros_analisis` text DEFAULT NULL,
  `analisis_adicionales` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `incluir_cumplimiento_pdf` tinyint(1) DEFAULT 0,
  `nombre_recibe_cycsa` varchar(150) DEFAULT NULL,
  `firma_recibe_cycsa` tinyint(1) DEFAULT 0,
  `firma_cliente` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `condicion_muestreo_datos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_os` (`id_os`),
  CONSTRAINT `hojas_solicitud_ibfk_1` FOREIGN KEY (`id_os`) REFERENCES `ordenes_servicio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `informes_control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `codigo_informe` varchar(50) NOT NULL,
  `version` int(11) DEFAULT 0,
  `codigo_completo` varchar(60) NOT NULL,
  `tipo_informe` enum('Parcial','Consolidado') DEFAULT 'Parcial',
  `edad_evaluada` int(11) DEFAULT NULL,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado_aprobacion` enum('Pendiente','Revisado','Aprobado','Rechazado','Reemplazado') DEFAULT 'Pendiente',
  `revisado_por` int(11) DEFAULT NULL,
  `aprobado_por` int(11) DEFAULT NULL,
  `motivo_reemplazo` text DEFAULT NULL,
  `observaciones_supervisor` text DEFAULT NULL,
  `ocultar_columna_cumplimiento` tinyint(1) NOT NULL DEFAULT 0,
  `ruta_archivo_pdf` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_completo` (`codigo_completo`),
  KEY `id_lote` (`id_lote`),
  KEY `revisado_por` (`revisado_por`),
  KEY `aprobado_por` (`aprobado_por`),
  CONSTRAINT `informes_control_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lotes_muestras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `informes_control_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `informes_control_ibfk_3` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lotes_muestras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_recepcion` int(11) NOT NULL,
  `nombre_lote` varchar(150) NOT NULL,
  `diseno_resistencia` varchar(100) DEFAULT NULL,
  `fecha_moldeo` date NOT NULL,
  `revenimiento_in` decimal(5,2) DEFAULT NULL,
  `revenimiento_cm` decimal(5,2) DEFAULT NULL,
  `temperatura_c` decimal(4,1) DEFAULT NULL,
  `procedimiento_muestreo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_recepcion` (`id_recepcion`),
  CONSTRAINT `lotes_muestras_ibfk_1` FOREIGN KEY (`id_recepcion`) REFERENCES `recepcion_muestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ordenes_servicio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_os` varchar(50) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `tipo_contrato` enum('Puntual','Mensual') DEFAULT 'Puntual',
  `fecha_emision` date NOT NULL,
  `estado` varchar(100) DEFAULT 'Estado 1: Recepcion',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_muestreo` date DEFAULT NULL,
  `hora_muestreo` time DEFAULT NULL,
  `tecnico_muestreo` varchar(150) DEFAULT NULL,
  `vehiculo_muestreo` varchar(100) DEFAULT NULL,
  `motivo_observacion` text DEFAULT NULL,
  `notas_supervisor` text DEFAULT NULL,
  `requiere_muestreo` tinyint(1) DEFAULT 0,
  `fecha_registro_campo` datetime DEFAULT NULL,
  `horas_espera_requeridas` int(11) NOT NULL DEFAULT 24,
  `hoja_campo_codigo` varchar(100) DEFAULT NULL,
  `hoja_campo_operador` varchar(150) DEFAULT NULL,
  `hoja_campo_notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_os` (`codigo_os`),
  KEY `id_cotizacion` (`id_cotizacion`),
  CONSTRAINT `ordenes_servicio_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `partidas_diario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_partida` varchar(50) NOT NULL,
  `fecha` date NOT NULL,
  `concepto` text NOT NULL,
  `origen` varchar(100) NOT NULL DEFAULT 'MANUAL',
  `origen_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `num_partida` (`num_partida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `partidas_diario_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `id_cuenta_contable` int(11) NOT NULL,
  `debe` decimal(14,2) NOT NULL DEFAULT 0.00,
  `haber` decimal(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `id_partida` (`id_partida`),
  KEY `id_cuenta_contable` (`id_cuenta_contable`),
  CONSTRAINT `partidas_diario_detalles_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas_diario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partidas_diario_detalles_ibfk_2` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_item` varchar(50) DEFAULT NULL,
  `formato_id` int(11) DEFAULT NULL,
  `tipo_muestra` varchar(100) DEFAULT NULL,
  `matriz_tipo` varchar(100) DEFAULT NULL,
  `tipo_muestreo` varchar(100) DEFAULT NULL,
  `ensayo_servicio` text DEFAULT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `condiciones_muestra` text DEFAULT NULL,
  `codigo_servicio` varchar(100) DEFAULT NULL,
  `estatus` varchar(50) DEFAULT 'No acreditado',
  `norma_astm` varchar(255) DEFAULT NULL,
  `procedimiento_muestreo` varchar(255) DEFAULT NULL,
  `codigo_hoja_campo` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_productos_formatos` (`formato_id`),
  CONSTRAINT `fk_productos_formatos` FOREIGN KEY (`formato_id`) REFERENCES `formatos_ensayos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `recepcion_muestras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_os` int(11) NOT NULL,
  `correlativo_anual` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `tipo_muestra` enum('Campo','Laboratorio') NOT NULL DEFAULT 'Laboratorio',
  `codigo_muestra` varchar(50) NOT NULL,
  `id_cilindro` varchar(100) DEFAULT NULL,
  `is_qa_qc` tinyint(1) NOT NULL DEFAULT 0,
  `replica_codigo` varchar(20) DEFAULT NULL,
  `is_sealed` tinyint(1) NOT NULL DEFAULT 1,
  `codigo_campo` varchar(50) NOT NULL,
  `fecha_recepcion` datetime NOT NULL,
  `recibido_por` int(11) NOT NULL,
  `entregado_por` varchar(150) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('Registrado','En Laboratorio','Finalizado') DEFAULT 'Registrado',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_muestra` (`codigo_muestra`),
  KEY `id_os` (`id_os`),
  KEY `recibido_por` (`recibido_por`),
  CONSTRAINT `recepcion_muestras_ibfk_1` FOREIGN KEY (`id_os`) REFERENCES `ordenes_servicio` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recepcion_muestras_ibfk_2` FOREIGN KEY (`recibido_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `permisos` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `secuencias_muestras` (
  `anio` int(11) NOT NULL,
  `tipo_muestra` enum('Campo','Laboratorio') NOT NULL,
  `ultimo_correlativo` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`anio`,`tipo_muestra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tecnicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `permisos` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado` tinyint(4) DEFAULT 0,
  `debe_cambiar_password` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(50) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

