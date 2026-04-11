<div class="col-12 w-100 mt-4">
    <nav class="border-top pt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>home/catalogo?<?= http_build_query(array_merge($_GET, ['p' => max(1, $pagina - 1)])) ?>" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <?php
            $rango = 2;
            $queryParams = $_GET;

            // Mostrar la página 1 y puntos suspensivos si estamos muy lejos del inicio
            if ($pagina > $rango + 1) {
                $queryParams['p'] = 1;
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                if ($pagina > $rango + 2) {
                    echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                }
            }

            // Mostrar el rango de páginas cercanas a la actual
            for ($i = max(1, $pagina - $rango); $i <= min($total_paginas, $pagina + $rango); $i++) {
                $queryParams['p'] = $i;
                $active = ($i == $pagina) ? 'active' : '';
                echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $i . '</a></li>';
            }

            // Mostrar puntos suspensivos y la última página si estamos muy lejos del final
            if ($pagina < $total_paginas - $rango) {
                if ($pagina < $total_paginas - $rango - 1) {
                    echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                }
                $queryParams['p'] = $total_paginas;
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $total_paginas . '</a></li>';
            }
            ?>

            <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>home/catalogo?<?= http_build_query(array_merge($_GET, ['p' => min($total_paginas, $pagina + 1)])) ?>" aria-label="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>