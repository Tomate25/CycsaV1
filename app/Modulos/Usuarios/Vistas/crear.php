<div style="max-width: 600px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/usuarios" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 600px; margin: 0 auto;">
    
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h2 style="margin: 0; color: #333; font-size: 20px;">Registrar Nuevo Usuario</h2>
        <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Ingresa los datos para dar acceso a un nuevo miembro del equipo.</p>
    </div>

    <form action="/Cycsa/publico/usuarios/crear" method="POST">
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nombre Completo</label>
            <input type="text" name="nombre" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Correo Electrónico</label>
            <input type="email" name="email" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Contraseña de Acceso</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Rol en el Sistema</label>
            <select name="id_rol" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; background-color: white;">
                <option value="">Selecciona un nivel de acceso...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= htmlspecialchars($rol['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- 🛡️ SECCIÓN DINÁMICA DE PERMISOS -->
        <div id="seccion-permisos" style="margin-bottom: 25px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; background-color: #f8fafc; display: none;">
            <h4 style="margin: 0 0 12px 0; color: #103487; font-size: 14.5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Configuración de Permisos
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.4;">Marca los módulos y las acciones específicas a las que este usuario tendrá acceso en la aplicación. Los permisos se pre-cargan automáticamente según el rol seleccionado.</p>
            
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
                        <input type="checkbox" name="permisos[clientes][crear_editar]" value="1" checked style="accent-color: #103487; margin-top: 3px;">
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
                        <input type="checkbox" name="permisos[cotizaciones][crear_editar]" value="1" checked style="accent-color: #103487; margin-top: 3px;">
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
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Acceso para visualizar el catálogo de cuentas, balance general, estado de resultados y registro diario.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[contabilidad][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Gestionar Contabilidad y Asientos</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite registrar asientos manuales, conciliar bancos, editar catálogo y sincronizar diario.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modulo: Operaciones -->
            <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <strong style="font-size: 13.5px; color: #1e293b; display: block; margin-bottom: 10px;"><i class="fa-solid fa-calendar-days"></i> Módulo Operaciones (Calendario):</strong>
                <div style="display: flex; flex-direction: column; gap: 12px; padding-left: 5px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[operaciones][ver]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Ver Calendario de Ensayos</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite consultar la programación mensual y cronograma de ensayos de laboratorio.</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[operaciones][crear_editar]" value="1" style="accent-color: #103487; margin-top: 3px;">
                        <div>
                            <span style="font-size: 13px; font-weight: 600; color: #334155; display: block;">Gestionar y Re-agendar Ensayos</span>
                            <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.3;">Permite modificar las fechas del cronograma de ensayos y dar aprobaciones operativas.</span>
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

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectRol = document.querySelector('select[name="id_rol"]');
                const seccionPermisos = document.getElementById('seccion-permisos');
                
                // Mapear los permisos por defecto de cada rol
                const rolesPermisos = <?= json_encode(array_column($roles, 'permisos', 'id')) ?>;
                
                function togglePermisos() {
                    // Mostrar permisos si hay rol seleccionado y NO es Administrador (ID = 1)
                    if (selectRol.value !== '' && selectRol.value != '1') {
                        seccionPermisos.style.display = 'block';
                    } else {
                        seccionPermisos.style.display = 'none';
                    }
                }
                
                function cargarPermisosRol() {
                    const idRol = selectRol.value;
                    if (!idRol || idRol == '1') return;
                    
                    let permisos = {};
                    const rawPermisos = rolesPermisos[idRol];
                    if (rawPermisos) {
                        try {
                            permisos = typeof rawPermisos === 'string' ? JSON.parse(rawPermisos) : rawPermisos;
                        } catch (e) {
                            console.error("Error al parsear permisos del rol:", e);
                            return;
                        }
                    }
                    
                    const checkboxes = seccionPermisos.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(cb => {
                        const name = cb.name; // Ej: "permisos[clientes][ver]"
                        const match = name.match(/permisos\[([^\]]+)\]\[([^\]]+)\]/);
                        if (match) {
                            const modulo = match[1];
                            const accion = match[2];
                            if (permisos && permisos[modulo] && permisos[modulo][accion] !== undefined) {
                                cb.checked = (permisos[modulo][accion] == 1 || permisos[modulo][accion] === true);
                            } else {
                                cb.checked = false;
                            }
                        }
                    });
                }
                
                if (selectRol) {
                    selectRol.addEventListener('change', function() {
                        togglePermisos();
                        cargarPermisosRol();
                    });
                    togglePermisos();
                }
            });
        </script>

        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="/Cycsa/publico/usuarios" style="padding: 10px 20px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: background 0.3s;">Guardar Usuario</button>
        </div>
    </form>

</div>