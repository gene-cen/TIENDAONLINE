<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="offcanvasCategorias" aria-labelledby="offcanvasCategoriasLabel" style="z-index: 1055;">
    <div class="offcanvas-header border-bottom bg-light p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-cenco-green p-2 rounded-circle text-white shadow-sm d-flex align-items-center justify-content-center">
                <i class="bi bi-grid-fill fs-5"></i>
            </div>
            <h5 class="offcanvas-title fw-black text-cenco-indigo mb-0" id="offcanvasCategoriasLabel">Catálogo</h5>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="<?= BASE_URL ?>home/catalogo" class="list-group-item list-group-item-action fw-bold text-cenco-indigo py-3 px-4 border-bottom">
                <i class="bi bi-collection-fill me-3 text-warning fs-5 align-middle"></i> Ver Todo el Catálogo
            </a>

            <?php if (!empty($categoriasMenu)): ?>
                <?php foreach ($categoriasMenu as $cat): ?>
                    <a href="<?= BASE_URL ?>home/catalogo?categoria=<?= urlencode($cat['nombre']) ?>" 
                       class="list-group-item list-group-item-action py-3 px-4 text-secondary hover-bg-light border-bottom transition-hover d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi <?= !empty($cat['icono']) ? htmlspecialchars($cat['icono']) : 'bi-tag' ?> me-3 text-cenco-green fs-5 opacity-75"></i>
                            
                            <span class="fw-medium text-dark"><?= htmlspecialchars($cat['nombre']) ?></span>
                        </div>
                        <i class="bi bi-chevron-right small opacity-50"></i>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-4 text-center text-muted small">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    No hay categorías disponibles en este momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>