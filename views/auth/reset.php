<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña | Cencocal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* === PALETA CORPORATIVA CENCOCAL === */
        :root {
            --cenco-red: #d32f2f;
            --cenco-green: #2e7d32;
            --cenco-black: #212121;
            --cenco-indigo: #1a237e;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-weight: 400;
        }

        /* Tarjeta Principal */
        .card-cenco {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            background: white;
            /* IMPORTANTE: Quitamos overflow:hidden para que la mascota no se corte */
            /* overflow: hidden; <--- ELIMINADO */
        }

        /* Barra Tricolor Superior */
        .flag-stripe {
            height: 8px;
            width: 100%;
            background: linear-gradient(to right, 
                var(--cenco-black) 33%, 
                var(--cenco-red) 33%, var(--cenco-red) 66%, 
                var(--cenco-green) 66%);
            
            /* Agregamos bordes redondeados superiores aquí manualmente */
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        /* Contenedor de la Mascota */
        .mascot-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
            margin-top: -85px; /* Hacemos que salga más hacia arriba */
            z-index: 10; /* Aseguramos que esté por encima de la tarjeta */
        }

        /* Efecto de brillo verde detrás */
        .glow-effect {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 130px;
            height: 130px;
            background-color: var(--cenco-green);
            opacity: 0.2; /* Un poco más visible */
            border-radius: 50%;
            filter: blur(25px);
            z-index: -1; /* Detrás de la imagen */
        }

        .cencocalin-mascot {
            width: 150px;
            position: relative;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.15));
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }
        .cencocalin-mascot:hover {
            transform: scale(1.05) rotate(0deg);
        }

        /* Tipografía */
        .title-cenco {
            color: var(--cenco-indigo);
            font-weight: 700; /* Un poco más de peso para el título */
            letter-spacing: -0.5px;
        }

        /* Inputs Modernos */
        .form-control {
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
            padding-left: 15px;
            border-left: none; /* Quitamos borde izquierdo para unir con el ícono */
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--cenco-green);
            box-shadow: none; /* Quitamos el glow azul por defecto */
        }
        
        /* Contenedor del input para el borde focus */
        .input-group:focus-within {
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.15);
            border-radius: 0.375rem;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-right: none;
            color: var(--cenco-green);
        }
        
        /* Al hacer foco, cambiamos el color del borde del input group text también */
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--cenco-green);
            background-color: #fff;
        }

        /* Botón Verde */
        .btn-cenco-green {
            background-color: var(--cenco-green);
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-cenco-green:hover {
            background-color: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
            color: white;
        }

        .link-back {
            color: #6c757d;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .link-back:hover {
            color: var(--cenco-indigo);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">
    
    <div class="container" style="max-width: 450px;">
        
        <div class="text-center mb-5">
            <img src="<?= BASE_URL ?>img/logo.png" alt="Cencocal" style="height: 50px; opacity: 0.9;">
        </div>

        <div style="margin-top: 60px;"> 
            <div class="card card-cenco">
                
                <div class="flag-stripe"></div>

                <div class="card-body p-4 pt-0 text-center">
                    
                    <div class="mascot-container">
                        <div class="glow-effect"></div>
                        <img src="<?= BASE_URL ?>img/cencocalin/cencocalin_seguridad.png" alt="Cencocalín" class="cencocalin-mascot">
                    </div>

                    <h3 class="title-cenco mb-2">Crea tu nueva clave</h3>
                    <p class="text-muted mb-4 small">Ingresa una contraseña segura para proteger tu cuenta.</p>
                    
                    <form action="<?= BASE_URL ?>auth/update-password" method="POST" class="text-start px-2">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label small text-muted text-uppercase ms-1">Nueva Contraseña</label>
                            <div class="input-group rounded-3 overflow-hidden">
                                <span class="input-group-text"><i class="bi bi-lock-fill fs-5"></i></span>
                                <input type="password" 
                                       name="password" 
                                       class="form-control form-control-lg" 
                                       minlength="6" 
                                       placeholder="Mínimo 6 caracteres" 
                                       required
                                       autofocus>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-cenco-green w-100 rounded-pill shadow-sm d-flex align-items-center justify-content-center gap-2">
                            RESTABLECER CONTRASEÑA <i class="bi bi-check-circle-fill"></i>
                        </button>
                    </form>

                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>" class="text-decoration-none link-back d-inline-flex align-items-center">
                            <i class="bi bi-arrow-left me-2"></i> Volver a la tienda
                        </a>
                    </div>

                </div>
            </div>
        </div>
        
        <div class="text-center mt-5 text-muted small opacity-75">
            &copy; <?= date('Y') ?> Cencocal S.A. <br>Todos los derechos reservados.
        </div>
    </div>

</body>
</html>