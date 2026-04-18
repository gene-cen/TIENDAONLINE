<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda CENCOCAL</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>css/styles.css?v=3.0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<?php
// Lógica para detectar si es admin
$claseBody = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 'admin-body' : '';
?>

<body class="<?= $claseBody ?>">
    <svg style="display:none" aria-hidden="true">
        <defs>
            <filter id="protanopia">
                <feColorMatrix type="matrix" values="0.567,0.433,0,0,0 0.558,0.442,0,0,0 0,0.242,0.758,0,0 0,0,0,1,0" />
            </filter>
            <filter id="deuteranopia">
                <feColorMatrix type="matrix" values="0.625,0.375,0,0,0 0.7,0.3,0,0,0 0,0.3,0.7,0,0 0,0,0,1,0" />
            </filter>
            <filter id="tritanopia">
                <feColorMatrix type="matrix" values="0.95,0.05,0,0,0 0,0.433,0.567,0,0 0,0.475,0.525,0,0 0,0,0,1,0" />
            </filter>
        </defs>
    </svg>

  <script>
        (function() {
            try {
                const saved = localStorage.getItem('cenco_accessibility');
                if (saved) {
                    const settings = JSON.parse(saved);
                    // 1. Filtros de color
                    if (settings.filter) document.documentElement.classList.add('filter-' + settings.filter);
                    // 2. Modo oscuro y contraste
                    if (settings.activeClasses) settings.activeClasses.forEach(c => document.body.classList.add(c));
                    // 3. Tamaño de texto (Ahora apunta al HTML)
                    if (settings.textLevel > 0) document.documentElement.classList.add('access-lvl-' + settings.textLevel);
                }
            } catch (e) { console.error("Error cargando accesibilidad", e); }
        })();
    </script>