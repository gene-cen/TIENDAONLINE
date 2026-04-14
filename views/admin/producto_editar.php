<div class="container-fluid px-4 py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-black text-cenco-indigo mb-1">
            <i class="bi bi-pencil-square me-2"></i>Editar Producto
        </h2>
        <a href="<?= BASE_URL ?>admin/productos" class="btn btn-outline-secondary fw-bold rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Volver al Catálogo
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h4 class="text-success">¡La ruta funciona!</h4>
        <p>Estás editando el producto con ID: <strong><?= $producto['id'] ?></strong></p>
        <p>Nombre: <strong><?= $producto['nombre'] ?></strong></p>
        
        </div>
</div>