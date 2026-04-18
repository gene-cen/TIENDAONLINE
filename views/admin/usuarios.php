<style>
    .bg-soft-blue {
        background-color: #f0f8ff !important;
        border: 1px solid #cfe2ff !important;
    }

    .form-label-custom {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--cenco-indigo);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-vertical-center td {
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-4 py-4 bg-light min-vh-100">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>Gestión de Usuarios</h2>
            <p class="text-secondary mb-0">Administra colaboradores, clientes y accesos al sistema.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <button class="btn btn-primary fw-bold px-4 py-2 shadow-sm rounded-pill d-flex align-items-center" onclick="abrirModalCrear()">
                <i class="bi bi-person-plus-fill me-2"></i> Nuevo Usuario
            </button>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form id="formFiltros" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label-custom mb-1 text-muted" style="font-size: 0.7rem;">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="filtro_busqueda" class="form-control border-start-0 ps-0" placeholder="Nombre, RUT o Email...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom mb-1 text-muted" style="font-size: 0.7rem;">Rol</label>
                    <select id="filtro_rol" class="form-select filter-trigger">
                        <option value="">Todos</option>
                        <?php foreach ($roles_disponibles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['nombre_rol'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom mb-1 text-muted" style="font-size: 0.7rem;">Sucursal</label>
                    <select id="filtro_sucursal" class="form-select filter-trigger">
                        <option value="">Todas</option>
                        <option value="10">Villa Alemana (10)</option>
                        <option value="29">La Calera (29)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom mb-1 text-muted" style="font-size: 0.7rem;">Estado</label>
                    <select id="filtro_estado" class="form-select filter-trigger">
                        <option value="">Cualquiera</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom mb-1 text-muted" style="font-size: 0.7rem;">VIP</label>
                    <select id="filtro_vip" class="form-select filter-trigger">
                        <option value="">Todos</option>
                        <option value="1">Solo Confianza</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="button" class="btn btn-outline-danger fw-bold" onclick="limpiarFiltros()" title="Limpiar Filtros">
                        <i class="bi bi-eraser-fill"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-hover table-vertical-center mb-0 bg-white">
        <thead class="bg-light text-cenco-indigo">
            <tr style="font-size:0.9rem;">
                <th class="ps-4 py-3">Usuario / Email</th>
                <th class="py-3">RUT / Identidad</th>
                <th class="py-3 text-center">Rol y Sucursal</th>
                <th class="py-3 text-center">Estado / VIP</th>
                <th class="py-3 text-end pe-4">Acción</th>
            </tr>
        </thead>
        <tbody id="tablaUsuariosBody">
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center p-3 bg-light border-top">
    <div class="text-muted small fw-bold" id="infoPaginacion">Cargando...</div>
    <nav>
        <ul class="pagination pagination-sm mb-0 shadow-sm" id="paginacionUsuarios">
        </ul>
    </nav>
</div>

</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white">
                <h5 class="modal-title fw-black text-cenco-indigo" id="modalTitulo">
                    <i class="bi bi-person-gear me-2 text-primary"></i>Gestión de Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formUsuario">
                    <input type="hidden" id="edit_id">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-1">Nombre Completo *</label>
                            <input type="text" id="edit_nombre" class="form-control text-capitalize" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom mb-1">RUT *</label>
                            <input type="text" id="edit_rut" class="form-control" placeholder="Ej: 11.111.111-1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom mb-1">Correo Electrónico *</label>
                        <input type="email" id="edit_email" class="form-control" required>
                    </div>

                    <div class="mb-3" id="box_password" style="display: none;">
                        <label class="form-label-custom mb-1 text-danger">Contraseña Inicial *</label>
                        <input type="text" id="edit_password" class="form-control border-danger">
                        <small class="text-muted" style="font-size: 0.7rem;">El usuario usará esta clave para su primer acceso.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom mb-1">Razón Social / Giro (Solo Clientes)</label>
                        <input type="text" id="edit_razon" class="form-control" placeholder="Opcional">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label-custom mb-1">Rol *</label>
                            <select id="edit_rol_id" class="form-select fw-bold text-cenco-indigo" onchange="evaluarRolSucursal()">
                                <?php foreach ($roles_disponibles as $rol): ?>
                                    <option value="<?= $rol['id'] ?>"><?= $rol['nombre_rol'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="box_sucursal">
                            <label class="form-label-custom mb-1">Sucursal Asig.</label>
                            <select id="edit_sucursal" class="form-select">
                                <option value="">Ninguna</option>
                                <option value="10">Villa Alemana</option>
                                <option value="29">La Calera</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom mb-1">Estado</label>
                            <select id="edit_estado" class="form-select border-success">
                                <option value="1">🟢 Activo</option>
                                <option value="0">🔴 Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-success-subtle p-3 rounded-3 border border-success mb-4 shadow-sm" id="box_confianza">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between p-0">
                            <label class="form-check-label fw-bold text-success" for="edit_confianza">
                                <i class="bi bi-star-fill text-warning me-1"></i> Cliente de Confianza
                                <span class="d-block small fw-normal text-dark opacity-75 mt-1" style="font-size: 0.75rem;">Permite crédito o contra entrega.</span>
                            </label>
                            <input class="form-check-input fs-3 ms-3" type="checkbox" id="edit_confianza" style="cursor:pointer;">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm" id="btnGuardarUsuario" onclick="guardarUsuario()">
                            Guardar Usuario <i class="bi bi-check2-circle ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // Variables globales para paginación y retardo de búsqueda
    let paginaActual = 1;
    let timerBusqueda;

    // =========================================================
    // 1. INICIALIZACIÓN Y EVENTOS INTELIGENTES
    // =========================================================
    document.addEventListener("DOMContentLoaded", () => {
        // Cargar primera página al inicio
        filtrarUsuarios();

        // A) Evento de teclado inteligente (Búsqueda)
        // Espera 400ms después de que dejas de escribir para buscar (No requiere botón "Buscar")
        document.getElementById('filtro_busqueda').addEventListener('input', function() {
            clearTimeout(timerBusqueda);
            timerBusqueda = setTimeout(() => {
                paginaActual = 1; // Siempre vuelve a la pág 1 al buscar
                filtrarUsuarios();
            }, 400);
        });

        // B) Eventos de Cambio (Selects)
        // Detecta automáticamente cuando cambias Rol, Sucursal, Estado o VIP
        const selectsFiltro = document.querySelectorAll('#formFiltros select');
        selectsFiltro.forEach(select => {
            select.addEventListener('change', () => {
                paginaActual = 1;
                filtrarUsuarios();
            });
        });
    });

    // =========================================================
    // 2. FUNCIONES DE FILTROS Y PAGINACIÓN
    // =========================================================

    // Limpiar todos los filtros (La Gomita)
    function limpiarFiltros() {
        document.getElementById('formFiltros').reset();
        paginaActual = 1;
        filtrarUsuarios();
    }

    // Cambiar de página
    function cambiarPagina(pag) {
        paginaActual = pag;
        filtrarUsuarios();
    }

    // Motor principal de búsqueda
    function filtrarUsuarios() {
        const tbody = document.getElementById('tablaUsuariosBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><span class="spinner-border text-primary"></span> Buscando...</td></tr>';

        // Recopilamos todos los datos, incluyendo la página actual
        const data = {
            busqueda: document.getElementById('filtro_busqueda').value,
            rol_id: document.getElementById('filtro_rol').value,
            sucursal_id: document.getElementById('filtro_sucursal').value,
            estado: document.getElementById('filtro_estado').value,
            confianza: document.getElementById('filtro_vip').value,
            pagina: paginaActual
        };

        fetch('<?= BASE_URL ?>admin/usuarios/filtrar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                tbody.innerHTML = '';

                // Si no hay resultados
                if (response.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron usuarios con esos filtros.</td></tr>';
                    if (document.getElementById('infoPaginacion')) {
                        document.getElementById('infoPaginacion').innerText = '0 registros encontrados';
                        document.getElementById('paginacionUsuarios').innerHTML = '';
                    }
                    return;
                }

                // Si hay resultados, dibujamos las filas
                response.data.forEach(u => {
                    let badgeRol = 'bg-secondary';
                    if (u.rol_id == 1 || u.rol_id == 2) badgeRol = 'bg-danger';
                    if (u.rol_id == 5) badgeRol = 'bg-info';

                    let badgeEstado = u.estado == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                    let badgeVIP = u.es_cliente_confianza == 1 ? '<span class="badge border border-warning text-warning ms-1"><i class="bi bi-star-fill"></i> VIP</span>' : '';
                    let txtSucursal = u.nombre_sucursal ? `<div class="small text-muted mt-1"><i class="bi bi-shop"></i> Suc. ${u.sucursal_asignada}</div>` : '';

                    tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark">${u.nombre || 'Sin Nombre'}</div>
                            <div class="text-muted small"><i class="bi bi-envelope me-1"></i>${u.email}</div>
                        </td>
                        <td class="py-3">
                            <div class="font-monospace small fw-bold">${u.rut || '---'}</div>
                            <div class="text-muted small fst-italic">${u.razon_social || ''}</div>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge ${badgeRol} shadow-sm">${u.nombre_rol || 'Sin Rol'}</span>
                            ${txtSucursal}
                        </td>
                        <td class="text-center py-3">
                            ${badgeEstado} ${badgeVIP}
                        </td>
                        <td class="text-end pe-4 py-3">
                            <button class="btn btn-light border text-primary px-3 py-1 shadow-sm transition-hover" onclick="abrirModalUsuario(${u.id})" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                `;
                });

                // Dibujar la Paginación visual
                if (response.paginacion) {
                    renderizarPaginacion(response.paginacion);
                }
            })
            .catch(error => {
                console.error("Error en fetch:", error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Ocurrió un error al cargar los datos.</td></tr>';
            });
    }

    // Dibujar los botones numéricos de paginación
    function renderizarPaginacion(pagData) {
        const infoDiv = document.getElementById('infoPaginacion');
        const pagUl = document.getElementById('paginacionUsuarios');

        if (!infoDiv || !pagUl) return;

        infoDiv.innerText = `Mostrando pág ${pagData.pagina_actual} de ${pagData.total_paginas} (${pagData.total_registros} registros)`;

        if (pagData.total_paginas <= 1) {
            pagUl.innerHTML = '';
            return;
        }

        let ul = '';
        const tp = pagData.total_paginas;
        const pa = pagData.pagina_actual;

        // Botón "Anterior"
        ul += `<li class="page-item ${pa === 1 ? 'disabled' : ''}">
                 <button class="page-link" onclick="cambiarPagina(${pa - 1})">&laquo;</button>
               </li>`;

        // Mostrar un máximo de 5 botones para no desbordar
        let inicio = Math.max(1, pa - 2);
        let fin = Math.min(tp, pa + 2);

        for (let i = inicio; i <= fin; i++) {
            ul += `<li class="page-item ${i === pa ? 'active' : ''}">
                     <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
                   </li>`;
        }

        // Botón "Siguiente"
        ul += `<li class="page-item ${pa === tp ? 'disabled' : ''}">
                 <button class="page-link" onclick="cambiarPagina(${pa + 1})">&raquo;</button>
               </li>`;

        pagUl.innerHTML = ul;
    }

    // =========================================================
    // 3. LÓGICA DE MODALES Y GESTIÓN (CREATE / UPDATE)
    // =========================================================

    function evaluarRolSucursal() {
        const rolId = parseInt(document.getElementById('edit_rol_id').value);
        // Roles que necesitan sucursal (Ej: 2 = Admin, 4 = Vendedor, 5 = Transporte)
        const rolesConSucursal = [2, 4, 5];

        if (rolesConSucursal.includes(rolId)) {
            document.getElementById('box_sucursal').style.display = 'block';
        } else {
            document.getElementById('box_sucursal').style.display = 'none';
            document.getElementById('edit_sucursal').value = "";
        }

        document.getElementById('box_confianza').style.display = (rolId === 6) ? 'block' : 'none';
    }

    function abrirModalCrear() {
        document.getElementById('formUsuario').reset();
        document.getElementById('edit_id').value = '';
        document.getElementById('edit_email').disabled = false;
        document.getElementById('box_password').style.display = 'block';
        document.getElementById('modalTitulo').innerHTML = '<i class="bi bi-person-plus-fill me-2 text-success"></i>Nuevo Usuario';

        evaluarRolSucursal();
        new bootstrap.Modal(document.getElementById('modalUsuario')).show();
    }

    function abrirModalUsuario(id) {
        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading()
            }
        });

        fetch('<?= BASE_URL ?>admin/usuarios/get', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id
                })
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.status === 'success') {
                    const u = data.data;
                    document.getElementById('edit_id').value = u.id;
                    document.getElementById('edit_email').value = u.email;
                    document.getElementById('edit_email').disabled = true;
                    document.getElementById('box_password').style.display = 'none';

                    document.getElementById('edit_nombre').value = u.nombre;
                    document.getElementById('edit_rut').value = u.rut;
                    document.getElementById('edit_razon').value = u.razon_social || '';
                    document.getElementById('edit_rol_id').value = u.rol_id;

                    // Asegurar que si es null, se seleccione vacío ""
                    document.getElementById('edit_sucursal').value = u.sucursal_asignada ? u.sucursal_asignada : "";

                    document.getElementById('edit_estado').value = u.estado;
                    document.getElementById('edit_confianza').checked = (u.es_cliente_confianza == 1);

                    document.getElementById('modalTitulo').innerHTML = '<i class="bi bi-person-gear me-2 text-primary"></i>Editar Usuario';

                    evaluarRolSucursal();
                    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
                }
            });
    }

    function guardarUsuario() {
        const id = document.getElementById('edit_id').value;
        const endpoint = id ? 'update' : 'crear_colaborador';

        const data = {
            id: id,
            nombre: document.getElementById('edit_nombre').value,
            rut: document.getElementById('edit_rut').value,
            email: document.getElementById('edit_email').value,
            password: document.getElementById('edit_password').value,
            razon_social: document.getElementById('edit_razon').value,
            rol_id: document.getElementById('edit_rol_id').value,
            sucursal_asignada: document.getElementById('edit_sucursal').value,
            estado: document.getElementById('edit_estado').value,
            es_cliente_confianza: document.getElementById('edit_confianza').checked ? 1 : 0
        };

        if (data.nombre.trim() === '' || data.email.trim() === '') {
            Swal.fire('Atención', 'Nombre y Email son obligatorios.', 'warning');
            return;
        }

        const btn = document.getElementById('btnGuardarUsuario');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        btn.disabled = true;

        fetch(`<?= BASE_URL ?>admin/usuarios/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                    filtrarUsuarios(); // Recargar en vivo
                } else {
                    Swal.fire('Error', response.msg, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Fallo al procesar.', 'error'))
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }
</script>