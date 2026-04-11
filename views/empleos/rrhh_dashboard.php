<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-black text-cenco-indigo mb-1"><i class="bi bi-people-fill me-2"></i>Gestión de Reclutamiento</h2>
            <p class="text-muted mb-0">Panel exclusivo de Recursos Humanos</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= BASE_URL ?>empleos/dashboardRRHH" class="btn btn-outline-danger fw-bold rounded-pill shadow-sm hover-scale">
                <i class="bi bi-eraser me-1"></i>Limpiar Filtros
            </a>
            <button type="button" onclick="exportarExcel()" class="btn btn-success fw-bold rounded-pill shadow-sm hover-scale">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar a Excel
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="formFiltros" action="<?= BASE_URL ?>empleos/dashboardRRHH" method="GET" class="row g-2 align-items-end">
                
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Sucursal</label>
                    <select name="sucursal" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="Femacal" <?= ($_GET['sucursal']??'')=='Femacal'?'selected':'' ?>>Femacal</option>
                        <option value="Prat" <?= ($_GET['sucursal']??'')=='Prat'?'selected':'' ?>>Prat</option>
                        <option value="Bodega Nogales" <?= ($_GET['sucursal']??'')=='Bodega Nogales'?'selected':'' ?>>Nogales</option>
                        <option value="Casa Matriz" <?= ($_GET['sucursal']??'')=='Casa Matriz'?'selected':'' ?>>Casa Matriz</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Estado</label>
                    <select name="estado" class="form-select border-cenco-indigo" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente" <?= ($_GET['estado']??'')=='Pendiente'?'selected':'' ?>>Pendientes</option>
                        <option value="En Revisión" <?= ($_GET['estado']??'')=='En Revisión'?'selected':'' ?>>Considerados</option>
                        <option value="Descartado" <?= ($_GET['estado']??'')=='Descartado'?'selected':'' ?>>Descartados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Sexo</label>
                    <select name="sexo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="Masculino" <?= ($_GET['sexo']??'')=='Masculino'?'selected':'' ?>>Masculino</option>
                        <option value="Femenino" <?= ($_GET['sexo']??'')=='Femenino'?'selected':'' ?>>Femenino</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Desde (Fecha)</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($_GET['fecha_inicio']??'') ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Hasta (Fecha)</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($_GET['fecha_fin']??'') ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="small text-muted fw-bold">Ordenamiento</label>
                    <select name="orden" class="form-select" onchange="this.form.submit()">
                        <option value="asc" <?= ($_GET['orden']??'')=='asc'?'selected':'' ?>>Más antiguas primero</option>
                        <option value="desc" <?= ($_GET['orden']??'')=='desc'?'selected':'' ?>>Más recientes primero</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive custom-scrollbar">
            <table class="table align-middle mb-0" style="white-space: nowrap;">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold">Postulante</th>
                        <th class="py-3 text-muted small fw-bold">RUT</th>
                        <th class="py-3 text-muted small fw-bold text-center">Edad</th>
                        <th class="py-3 text-muted small fw-bold">Ubicación</th>
                        <th class="py-3 text-muted small fw-bold">Cargo</th>
                        <th class="py-3 text-muted small fw-bold">Celular</th>
                        <th class="py-3 text-muted small fw-bold">Email</th>
                        <th class="py-3 text-muted small fw-bold">Decisión RRHH</th>
                        <th class="pe-4 py-3 text-end text-muted small fw-bold">CV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($postulaciones)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i> No hay postulaciones con estos filtros.</td></tr>
                    <?php else: foreach($postulaciones as $p): 
                        
                        // Íconos y colores por Sucursal
                        $iconoSucursal = 'bi-geo-alt-fill'; 
                        if ($p['sucursal'] === 'Prat' || $p['sucursal'] === 'Femacal') $iconoSucursal = 'bi-shop';
                        elseif ($p['sucursal'] === 'Casa Matriz') $iconoSucursal = 'bi-building-fill';
                        elseif ($p['sucursal'] === 'Bodega Nogales') $iconoSucursal = 'bi-boxes';

                        // Colores de Fila según Estado
                        $bgRow = '';
                        $selectColor = 'bg-light text-dark';
                        if ($p['estado'] === 'Descartado') {
                            $bgRow = 'bg-danger bg-opacity-10'; // Fila Roja
                            $selectColor = 'bg-danger text-white border-danger';
                        } elseif ($p['estado'] === 'En Revisión') {
                            $bgRow = 'bg-success bg-opacity-10'; // Fila Verde
                            $selectColor = 'bg-success text-white border-success';
                        }
                    ?>
                        <tr class="<?= $bgRow ?>">
                            
                            <td class="ps-4">
                                <div class="fw-bold text-dark">
                                    <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?>
                                    <?php if($p['nacionalidad'] !== 'Chilena'): ?>
                                        <span class="badge bg-warning text-dark ms-1" title="Permiso: <?= htmlspecialchars($p['permiso_trabajo']) ?>">Ext.</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y H:i', strtotime($p['fecha_postulacion'])) ?></small>
                            </td>

                            <td class="text-secondary fw-medium"><?= htmlspecialchars($p['rut']) ?></td>

                            <td class="text-center text-secondary"><?= htmlspecialchars($p['edad']) ?></td>

                            <td>
                                <span class="badge bg-cenco-indigo text-white rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.75rem; font-weight: 600;">
                                    <i class="bi <?= $iconoSucursal ?> me-1"></i> <?= htmlspecialchars($p['sucursal']) ?>
                                </span>
                            </td>

                            <td><span class="fw-bold text-secondary" style="font-size: 0.85rem;"><?= htmlspecialchars($p['cargo_nombre']) ?></span></td>

                            <td><i class="bi bi-whatsapp text-success me-1"></i>+56 9 <?= htmlspecialchars($p['telefono']) ?></td>

                            <td><a href="mailto:<?= htmlspecialchars($p['email']) ?>" class="text-decoration-none small text-cenco-indigo"><?= htmlspecialchars($p['email']) ?></a></td>

                           <td>
                                <select class="form-select form-select-sm fw-bold shadow-sm <?= $selectColor ?>" style="cursor: pointer; min-width: 140px;" onchange="cambiarEstadoPostulacion(<?= $p['id'] ?>, this.value)">
                                    <option value="Pendiente" class="bg-white text-dark" <?= $p['estado']=='Pendiente'?'selected':'' ?>>⏳ Pendiente</option>
                                    <option value="En Revisión" class="bg-white text-dark" <?= $p['estado']=='En Revisión'?'selected':'' ?>>✅ Considerando</option>
                                    <option value="Descartado" class="bg-white text-dark" <?= $p['estado']=='Descartado'?'selected':'' ?>>❌ Descartado</option>
                                </select>
                            </td>

                            <td class="text-end pe-4">
                                <?php if(!empty($p['ruta_cv'])): ?>
                                    <a href="<?= BASE_URL . htmlspecialchars($p['ruta_cv']) ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm py-1 px-2"><i class="bi bi-file-pdf-fill"></i> CV</a>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Sin CV</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <div class="card-footer bg-white border-top py-3">
                <?php 
                $paramsUrl = $_GET;
                unset($paramsUrl['page']);
                $queryString = http_build_query($paramsUrl);
                $prefix = !empty($queryString) ? '&' : '';
                ?>
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-pill" href="?page=<?= $pagina_actual - 1 ?><?= $prefix . $queryString ?>">Anterior</a>
                        </li>
                        <?php 
                        $rango = 2; 
                        for ($i = 1; $i <= $total_paginas; $i++): 
                            if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)):
                        ?>
                            <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                                <a class="page-link <?= ($pagina_actual == $i) ? 'bg-cenco-indigo border-cenco-indigo' : 'text-muted' ?>" href="?page=<?= $i ?><?= $prefix . $queryString ?>"><?= $i ?></a>
                            </li>
                            <?php elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1): ?>
                                <li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>
                        <?php endif; endfor; ?>
                        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-pill" href="?page=<?= $pagina_actual + 1 ?><?= $prefix . $queryString ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Filtro a Excel
function exportarExcel() {
    const form = document.getElementById('formFiltros');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '<?= BASE_URL ?>empleos/exportarExcelRRHH?' + params;
}

// Magia AJAX: Cambiar Estado en Vivo
function cambiarEstadoPostulacion(id, nuevoEstado) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', nuevoEstado);

    fetch('<?= BASE_URL ?>empleos/cambiarEstado', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Recargamos sutilmente para aplicar los colores de la fila
            window.location.reload(); 
        } else {
            Swal.fire('Error', 'No se pudo actualizar el estado.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Hubo un problema de conexión.', 'error');
    });
}
</script>