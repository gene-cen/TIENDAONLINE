<div class="border-end bg-white" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom bg-primary text-white p-3">
        <div class="fw-bold">CENCOCAL MENU</div>
    </div>
    
    <div class="list-group list-group-flush">
        
        <a href="<?= BASE_URL ?>perfil" class="list-group-item list-group-item-action list-group-item-light p-3">
            <i class="bi bi-person-circle me-2"></i> Mi Perfil
        </a>

        <?php if(isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
            <div class="sidebar-heading border-bottom bg-light text-muted small p-2 px-3">
                ADMINISTRACIÓN
            </div>
            <a href="<?= BASE_URL ?>admin/dashboard" class="list-group-item list-group-item-action list-group-item-light p-3">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>admin/productos" class="list-group-item list-group-item-action list-group-item-light p-3">
                <i class="bi bi-box-seam me-2"></i> Inventario
            </a>
            <a href="<?= BASE_URL ?>admin/ventas" class="list-group-item list-group-item-action list-group-item-light p-3">
                <i class="bi bi-receipt me-2"></i> Ventas
            </a>
        <?php endif; ?>

        <div class="sidebar-heading border-bottom bg-light text-muted small p-2 px-3">
            CATÁLOGO
        </div>
        <a href="<?= BASE_URL ?>home" class="list-group-item list-group-item-action list-group-item-light p-3">
            <i class="bi bi-shop me-2"></i> Todo
        </a>
        
        <?php if(isset($categorias) && !empty($categorias)): ?>
            <?php foreach($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>categoria/<?= $cat['id'] ?>" class="list-group-item list-group-item-action list-group-item-light p-3 ps-4">
                    • <?= $cat['nombre'] ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>