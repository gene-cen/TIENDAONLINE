<?php

namespace App\Models;

use Exception;

class ERPLink
{
    /**
     * Sincroniza los archivos CSV desde el servidor FTP externo
     * a la carpeta local erp_data del proyecto.
     */
    public function sincronizarArchivos()
    {
        // 1. Cargamos credenciales desde el entorno (.env)
        $ftp_host = $_ENV['FTP_HOST'] ?? '201.148.105.109';
        $ftp_user = $_ENV['FTP_USER'] ?? '';
        $ftp_pass = $_ENV['FTP_PASS'] ?? '';

        // 2. Definimos y validamos la ruta local (erp_data)
        // dirname(__DIR__, 2) nos sube de 'app/Models' a la raíz del proyecto
        $local_path = dirname(__DIR__, 2) . '/erp_data/';
        
        if (!is_dir($local_path)) {
            throw new Exception("ERROR CRÍTICO: La carpeta local no existe en: " . $local_path);
        }

        // 3. Establecemos la conexión FTP
        $conn_id = ftp_connect($ftp_host);
        $login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);

        if (!$conn_id || !$login_result) {
            throw new Exception("ERROR DE CONEXIÓN: No se pudo autenticar en el FTP de Cencocal.");
        }

        // 4. Modo pasivo obligatorio para servidores con Firewall/cPanel
        ftp_pasv($conn_id, true);

        // --- INICIO DIAGNÓSTICO (Opcional, puedes comentarlo luego) ---
        $directorio_actual = ftp_pwd($conn_id);
        $lista_remota = ftp_nlist($conn_id, ".");
        
        echo "<h3>--- Diagnóstico de Sincronización FTP ---</h3>";
        echo "Carpeta remota: " . $directorio_actual . "<br>";
        echo "Archivos detectados en servidor:<pre>";
        print_r($lista_remota);
        echo "</pre><hr>";
        // --- FIN DIAGNÓSTICO ---

        // 5. Lista maestra de archivos según tu FileZilla
        $archivos_a_bajar = [
            '29_productos_web.csv',
            '29_productos_web2.csv',
            '28_productos_web.csv',
            '28_productos_web2.csv',
            '10_productos_web.csv',
            '10_productos_web2.csv',
            '08_productos_web.csv',
            '08_productos_web2.csv'
        ];

        $descargados = 0;

        foreach ($archivos_a_bajar as $archivo) {
            $local_file = $local_path . $archivo;
            
            // Intentamos la descarga (usamos el nombre directo del archivo como ruta remota)
            if (ftp_get($conn_id, $local_file, $archivo, FTP_BINARY)) {
                echo "✅ Sincronizado: $archivo <br>";
                $descargados++;
            } else {
                echo "❌ Error al bajar: $archivo (Posiblemente no existe en el servidor)<br>";
            }
        }

        ftp_close($conn_id);

        if ($descargados === 0) {
            throw new Exception("No se pudo descargar ningún archivo del ERP. Revisa la ruta remota.");
        }

        return true;
    }
}