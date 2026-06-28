<div style="max-width: 650px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/roles" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista de roles</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 650px; margin: 0 auto;">
    
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h2 style="margin: 0; color: #333; font-size: 20px;">Crear Nuevo Rol de Usuario</h2>
        <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Define el nombre, responsabilidades y los permisos asignados por defecto al nuevo rol.</p>
    </div>

    <form action="/Cycsa/publico/roles/crear" method="POST">
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a; font-size: 14px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nombre del Rol</label>
            <input type="text" name="nombre" required placeholder="Ej. Encargado de Bodega, Auditor de Calidad..." style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Descripción de Funciones</label>
            <textarea name="descripcion" placeholder="Explica de forma resumida qué responsabilidades o puestos abarca este rol en la empresa..." style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; height: 80px; resize: vertical;"></textarea>
        </div>

        <!-- 🛡️ SECCIÓN DE CONFIGURACIÓN DE PERMISOS PREDETERMINADOS -->
        <div style="margin-bottom: 30px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; background-color: #f8fafc;">
            <h4 style="margin: 0 0 12px 0; color: #103487; font-size: 14.5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Permisos Predeterminados del Rol
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.4;">Marca los accesos y acciones que los usuarios asignados a este rol tendrán por defecto.</p>
            
            <!-- Modulo: Clientes -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-users"></i> Módulo Clientes:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[clientes][ver]" value="1" checked style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Visualizar Clientes</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite buscar, filtrar y consultar la lista general de clientes registrados en el sistema.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[clientes][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Registrar / Editar Clientes</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite dar de alta nuevos clientes y modificar sus fichas de información fiscal o de contacto.</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Modulo: Productos / Ensayos -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-flask-vial"></i> Módulo Productos / Catálogo de Ensayos:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[productos][ver]" value="1" checked style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Catálogo General</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Habilita la consulta de la lista de ensayos y servicios disponibles con sus normas y códigos.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[productos][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Crear / Editar / Eliminar Ensayos</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite agregar nuevos ensayos al catálogo, editar sus precios, normas ASTM y parámetros técnicos.</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Modulo: Cotizaciones -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-file-invoice-dollar"></i> Módulo Cotizaciones:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[cotizaciones][ver]" value="1" checked style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Cotizaciones</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Habilita el acceso de lectura al listado histórico de cotizaciones de la empresa.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[cotizaciones][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Crear / Editar Cotizaciones</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite elaborar cotizaciones, configurar sus ensayos, agregar descuentos y enviar propuestas a revisión.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[cotizaciones][aprobar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Supervisar / Aprobar Cotizaciones</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Faculta la revisión formal de cotizaciones comerciales para aprobarlas, rechazarlas o solicitar correcciones.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modulo: Inventario -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-boxes-stacked"></i> Módulo Inventario:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[inventario][ver]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Existencias / Stock</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite consultar el inventario de materiales, reactivos y consumibles en las diferentes bodegas.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[inventario][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Registrar Movimientos y Bodegas</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite asentar entradas, salidas, ajustes de inventario y aprobar transferencias físicas entre bodegas.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modulo: Compras -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-cart-shopping"></i> Módulo Compras:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[compras][ver]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Consultar Compras</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Acceso para ver el registro histórico de solicitudes y órdenes de compra con proveedores.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[compras][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Crear y Generar Órdenes de Compra</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite confeccionar nuevas solicitudes de compra, revisar costos y tramitar la firma/aprobación final de órdenes.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modulo: Contabilidad -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-calculator"></i> Módulo Contabilidad:</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[contabilidad][ver]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Informes Contables</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Acceso para visualizar reportes financieros, flujos de caja, conciliación de saldos e informes contables.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modulo: Laboratorio -->
            <div style="margin-bottom: 5px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-microscope"></i> Módulo Laboratorio (Ensayos):</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[laboratorio][ver]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Muestras y Ensayos</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Habilita el seguimiento al estado y avance de las muestras ingresadas en el laboratorio para control de calidad.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[laboratorio][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Cargar Resultados y Generar Informes</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Autoriza a los analistas de calidad a ingresar los datos medidos y generar los informes oficiales de resultados de ensayos.</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="/Cycsa/publico/roles" style="padding: 10px 20px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: background 0.3s;">Guardar Rol</button>
        </div>
    </form>
</div>
