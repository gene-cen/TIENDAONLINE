<div class="row justify-content-center">
    <div class="col-md-8">
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'guardado'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ✅ <strong>¡Cambios guardados!</strong> Tus datos han sido actualizados correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white p-3 d-flex align-items-center justify-content-between">
                <h4 class="mb-0 fw-bold">Mi Perfil</h4>
                <i class="bi bi-person-badge-fill fs-4"></i>
            </div>
            
            
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>perfil/guardar" method="POST">
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-center border-end">
                            <img src="<?= BASE_URL ?>img/mascota.png" class="img-fluid rounded-circle bg-light p-3 mb-3" style="width: 150px;">
                            <h5 class="fw-bold"><?= htmlspecialchars($usuario->nombre) ?></h5>
                            <span class="badge bg-secondary"><?= strtoupper($usuario->rol) ?></span>
                            
                            <div class="mt-4 text-start">
                                <label class="small text-muted fw-bold">RUT (No editable)</label>
                                <input type="text" class="form-control form-control-sm bg-light mb-2" value="<?= $usuario->rut ?? 'No registrado' ?>" readonly>
                                
                                <label class="small text-muted fw-bold">Email (No editable)</label>
                                <input type="text" class="form-control form-control-sm bg-light" value="<?= $usuario->email ?>" readonly>
                            </div>
                        </div>

                        <div class="col-md-8 ps-md-4">
                            <h5 class="text-primary mb-3 border-bottom pb-2">Editar Información</h5>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre Completo / Razón Social</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario->nombre) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario->telefono ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Dirección de Despacho</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($usuario->direccion ?? '') ?>" placeholder="Calle, Número, Comuna...">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Última actualización: Hoy</small>
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="bi bi-save me-2"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>