<style>
    .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-top: 20px; }
    .config-card { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border-top: 4px solid var(--cycsa-azul); display: flex; flex-direction: column; overflow: hidden; height: 100%; transition: transform 0.2s, box-shadow 0.2s; }
    .config-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.05); }
    .config-card.pago { border-top-color: #3b82f6; }
    .config-card.entrega { border-top-color: #10b981; }
    .config-card.vigencia { border-top-color: #f59e0b; }
    
    .card-header-premium { padding: 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; background: #fafafa; }
    .card-header-title { font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    .card-header-icon { font-size: 18px; color: var(--cycsa-azul); }
    
    .options-list { list-style: none; padding: 0; margin: 0; flex: 1; max-height: 380px; overflow-y: auto; }
    .option-item { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 14px; color: #475569; transition: background 0.15s; }
    .option-item:last-child { border-bottom: none; }
    .option-item:hover { background: #f8fafc; color: #0f172a; }
    
    .btn-delete-option { background: none; border: none; color: #ef4444; opacity: 0.6; cursor: pointer; transition: all 0.2s; padding: 5px; font-size: 14px; border-radius: 4px; }
    .btn-delete-option:hover { opacity: 1; background: #fee2e2; }
    
    .card-footer-premium { padding: 20px; border-top: 1px solid #edf2f7; background: #fafafa; }
    .add-form { display: flex; gap: 10px; }
    .add-input { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif; transition: all 0.2s; }
    .add-input:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .btn-add-option { background: var(--cycsa-azul); color: white; border: none; width: 38px; height: 38px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: background 0.2s, transform 0.15s; }
    .btn-add-option:hover { background: #0c2766; transform: scale(1.05); }
    .btn-add-option:active { transform: scale(0.95); }
    
    .empty-state { padding: 40px 20px; text-align: center; color: #94a3b8; font-size: 13px; }
    .empty-state i { font-size: 28px; display: block; margin-bottom: 10px; opacity: 0.5; }
</style>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="/Cycsa/publico/panel" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
        <h2 style="margin: 5px 0 0 0; color: #1e293b; font-weight: 700; font-size: 24px;">Configuración de Condiciones Comerciales</h2>
    </div>
</div>

<div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; border-radius: 6px; margin-bottom: 25px; display: flex; gap: 12px; align-items: flex-start;">
    <i class="fa-solid fa-circle-info" style="color: #0284c7; font-size: 18px; margin-top: 2px;"></i>
    <div style="font-size: 13.5px; color: #0369a1; line-height: 1.5;">
        <strong>Área de Configuración de Cotizaciones:</strong> Modifica las opciones que se cargan de forma dinámica en los selectores y autocompletados de condiciones comerciales (Condición de Pago, Tiempo de Entrega y Vigencia de la Oferta) al crear o editar una cotización.
    </div>
</div>

<div class="config-grid">
    <!-- 1. Condiciones de Pago -->
    <div class="config-card pago">
        <div class="card-header-premium">
            <div class="card-header-title">
                <i class="fa-solid fa-credit-card card-header-icon" style="color: #3b82f6;"></i>
                Condiciones de Pago
            </div>
            <span id="badge-count-pago" style="background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px;"><?= count($condiciones_pago) ?></span>
        </div>
        <ul class="options-list" id="list-condicion_pago">
            <?php if (empty($condiciones_pago)): ?>
                <li class="empty-state"><i class="fa-regular fa-folder-open"></i> Sin opciones registradas</li>
            <?php else: ?>
                <?php foreach ($condiciones_pago as $item): ?>
                    <li class="option-item" id="option-<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-delete-option" onclick="eliminarOp(<?= $item['id'] ?>, 'pago')" title="Eliminar opción">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <div class="card-footer-premium">
            <form onsubmit="agregarOp(event, 'condicion_pago', 'pago')" class="add-form">
                <input type="text" placeholder="Ej: 30% anticipo 70% contra entrega..." class="add-input" required id="input-condicion_pago">
                <button type="submit" class="btn-add-option" title="Agregar opción">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- 2. Tiempos de Entrega -->
    <div class="config-card entrega">
        <div class="card-header-premium">
            <div class="card-header-title">
                <i class="fa-solid fa-truck-ramp-box card-header-icon" style="color: #10b981;"></i>
                Tiempos de Entrega
            </div>
            <span id="badge-count-entrega" style="background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px;"><?= count($tiempos_entrega) ?></span>
        </div>
        <ul class="options-list" id="list-tiempo_entrega">
            <?php if (empty($tiempos_entrega)): ?>
                <li class="empty-state"><i class="fa-regular fa-folder-open"></i> Sin opciones registradas</li>
            <?php else: ?>
                <?php foreach ($tiempos_entrega as $item): ?>
                    <li class="option-item" id="option-<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-delete-option" onclick="eliminarOp(<?= $item['id'] ?>, 'entrega')" title="Eliminar opción">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <div class="card-footer-premium">
            <form onsubmit="agregarOp(event, 'tiempo_entrega', 'entrega')" class="add-form">
                <input type="text" placeholder="Ej: 4 a 6 días hábiles..." class="add-input" required id="input-tiempo_entrega">
                <button type="submit" class="btn-add-option" title="Agregar opción">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- 3. Vigencias de Oferta -->
    <div class="config-card vigencia">
        <div class="card-header-premium">
            <div class="card-header-title">
                <i class="fa-solid fa-calendar-check card-header-icon" style="color: #f59e0b;"></i>
                Vigencias de Oferta
            </div>
            <span id="badge-count-vigencia" style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px;"><?= count($vigencias_oferta) ?></span>
        </div>
        <ul class="options-list" id="list-vigencia_oferta">
            <?php if (empty($vigencias_oferta)): ?>
                <li class="empty-state"><i class="fa-regular fa-folder-open"></i> Sin opciones registradas</li>
            <?php else: ?>
                <?php foreach ($vigencias_oferta as $item): ?>
                    <li class="option-item" id="option-<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-delete-option" onclick="eliminarOp(<?= $item['id'] ?>, 'vigencia')" title="Eliminar opción">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <div class="card-footer-premium">
            <form onsubmit="agregarOp(event, 'vigencia_oferta', 'vigencia')" class="add-form">
                <input type="text" placeholder="Ej: 10 días calendario..." class="add-input" required id="input-vigencia_oferta">
                <button type="submit" class="btn-add-option" title="Agregar opción">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

    function agregarOp(event, tipo, idPrefijo) {
        event.preventDefault();
        const input = document.getElementById(`input-${tipo}`);
        const valor = input.value.trim();
        if (!valor) return;

        // Deshabilitar submit temporal
        const form = event.target;
        const btn = form.querySelector('button');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('tipo', tipo);
        formData.append('valor', valor);

        fetch('/Cycsa/publico/configuracion/agregar-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }

            // Insertar opción en la lista del DOM
            const list = document.getElementById(`list-${tipo}`);
            const emptyEl = list.querySelector('.empty-state');
            if (emptyEl) emptyEl.remove();

            const li = document.createElement('li');
            li.className = 'option-item';
            li.id = `option-${data.id}`;
            li.innerHTML = `
                <span>${escapeHtml(data.valor)}</span>
                <button type="button" class="btn-delete-option" onclick="eliminarOp(${data.id}, '${idPrefijo}')" title="Eliminar opción">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            `;
            list.appendChild(li);

            // Incrementar contador
            const badge = document.getElementById(`badge-count-${idPrefijo}`);
            badge.textContent = parseInt(badge.textContent) + 1;

            input.value = '';
            input.focus();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al agregar la opción.');
        });
    }

    function eliminarOp(id, idPrefijo) {
        if (!confirm('¿Está seguro de que desea eliminar esta condición comercial?')) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);

        fetch('/Cycsa/publico/configuracion/eliminar-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const item = document.getElementById(`option-${id}`);
            if (item) {
                // Obtener contenedor y tipo para comprobar si queda vacío
                const list = item.parentElement;
                item.remove();

                if (list.children.length === 0) {
                    list.innerHTML = '<li class="empty-state"><i class="fa-regular fa-folder-open"></i> Sin opciones registradas</li>';
                }

                // Decrementar contador
                const badge = document.getElementById(`badge-count-${idPrefijo}`);
                badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al eliminar la opción.');
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
