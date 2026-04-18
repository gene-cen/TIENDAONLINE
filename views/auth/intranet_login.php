<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Colaboradores | Cencocal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css">
    
    <style>
        /* Un fondo oscuro y elegante para los trabajadores */
        body {
            background-color: #2A1B5E; /* Tu cenco-indigo */
            margin: 0;
            overflow: hidden;
        }
        
        /* 🔥 Convertimos el wrapper en la pantalla completa para que el CSS de accesibilidad lo reconozca */
        #wrapper {
            background-color: #2A1B5E;
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
    </style>

    <svg style="display:none" aria-hidden="true">
        <defs>
            <filter id="protanopia"><feColorMatrix type="matrix" values="0.567,0.433,0,0,0 0.558,0.442,0,0,0 0,0.242,0.758,0,0 0,0,0,1,0" /></filter>
            <filter id="deuteranopia"><feColorMatrix type="matrix" values="0.625,0.375,0,0,0 0.7,0.3,0,0,0 0,0.3,0.7,0,0 0,0,0,1,0" /></filter>
            <filter id="tritanopia"><feColorMatrix type="matrix" values="0.95,0.05,0,0,0 0,0.433,0.567,0,0 0,0.475,0.525,0,0 0,0,0,1,0" /></filter>
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
                    // 3. Tamaño de texto
                    if (settings.textLevel > 0) document.documentElement.classList.add('access-lvl-' + settings.textLevel);
                }
            } catch (e) { console.error("Error cargando accesibilidad", e); }
        })();
    </script>
</head>
<body>

    <div id="wrapper">
        <div class="login-card p-4 p-md-5 mx-3">
            
            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal" style="max-height: 80px;" class="mb-3">
                <h4 class="fw-bold text-cenco-indigo mb-0">Portal Colaboradores</h4>
                <p class="text-muted small">Acceso exclusivo para personal autorizado</p>
            </div>

            <form action="<?= BASE_URL ?>auth/login" method="POST">
                
                <input type="hidden" name="login_source" value="intranet">
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control rounded-4" id="email_intranet" name="email" placeholder="nombre@cencocal.cl" required>
                    <label for="email_intranet"><i class="bi bi-person-badge me-2 text-muted"></i>Correo Corporativo</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control rounded-4" id="password_intranet" name="password" placeholder="Contraseña" required>
                    <label for="password_intranet"><i class="bi bi-key me-2 text-muted"></i>Contraseña</label>
                </div>

                <button type="submit" class="btn btn-lg w-100 rounded-pill text-white fw-bold shadow-sm hover-scale" style="background-color: #61A60E;"> 
                    Ingresar al Sistema <i class="bi bi-arrow-right-circle ms-2"></i>
                </button>

            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="<?= BASE_URL ?>home" class="text-decoration-none text-muted small hover-text-cenco-indigo">
                    <i class="bi bi-arrow-left me-1"></i> Volver a la Tienda Pública
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if(isset($_GET['msg']) && $_GET['msg'] === 'no_autorizado'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso Denegado',
                    text: 'No es una cuenta de personal autorizado. Por favor, ingresa desde la tienda principal.',
                    confirmButtonColor: '#d33'
                }).then(() => {
                    // Limpia la URL para que no vuelva a saltar la alerta al recargar
                    window.history.replaceState(null, null, window.location.pathname);
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>