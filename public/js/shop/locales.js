// public/js/shop/locales.js
document.addEventListener("DOMContentLoaded", function() {
    // Leemos la variable global inyectada desde PHP
    if (!window.LocalesData) return;
    const locales = window.LocalesData;

    locales.forEach((local, index) => {
        const lat = parseFloat(local.latitud) || -33.4489;
        const lng = parseFloat(local.longitud) || -70.6693;
        const mapId = 'map-' + index;

        if (document.getElementById(mapId)) {
            // Configuramos zoomControl en false si estamos en móvil
            const isMobile = window.innerWidth < 768;
            const map = L.map(mapId, {
                zoomControl: !isMobile,
                scrollWheelZoom: false // Evita que la página haga scroll al pasar el dedo/mouse
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const redIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            // Sanitización básica
            const nombreLimpio = local.nombre ? local.nombre.replace(/</g, "&lt;").replace(/>/g, "&gt;") : 'Sucursal';
            const direccionLimpia = local.direccion ? local.direccion.replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';

            L.marker([lat, lng], { icon: redIcon })
                .addTo(map)
                .bindPopup(`<b>${nombreLimpio}</b><br>${direccionLimpia}`)
                .openPopup();

            // Fix visual para que cargue bien al renderizar
            setTimeout(() => { map.invalidateSize(); }, 500);
        }
    });
});