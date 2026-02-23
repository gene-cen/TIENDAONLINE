/**
 * Lógica ESPECÍFICA del Home (Buscador Predictivo)
 * Nota: La lógica del carrito ya está en scripts.js
 */

// =========================================================
// 1. BUSCADOR PREDICTIVO
// =========================================================
let timeoutPredictivo = null;

function buscarPredictivo(texto) {
    const lista = document.getElementById('lista-predictiva');
    if (!lista) return;

    if (texto.length < 2) {
        lista.classList.add('d-none');
        lista.innerHTML = '';
        return;
    }
    
    clearTimeout(timeoutPredictivo);
    
    timeoutPredictivo = setTimeout(() => {
        // Usamos la variable global BASE_URL definida en el layout
        fetch(BASE_URL + 'home/autocomplete?q=' + encodeURIComponent(texto))
            .then(response => response.json())
            .then(d => {
                lista.innerHTML = '';
                if (d.length > 0) {
                    lista.classList.remove('d-none');
                    d.forEach(i => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action px-4 py-2 cursor-pointer border-0 border-bottom';
                        li.innerHTML = `<i class="bi bi-search me-2 text-muted small"></i> ${i.nombre_web}`;
                        li.onclick = () => {
                            const input = document.getElementById('inputBusqueda');
                            if(input) input.value = i.nombre_web;
                            lista.classList.add('d-none');
                            window.location.href = BASE_URL + 'home/buscar?q=' + encodeURIComponent(i.nombre_web);
                        };
                        lista.appendChild(li);
                    });
                } else {
                    lista.classList.add('d-none');
                }
            }).catch(e => console.error(e));
    }, 300);
}

// Cerrar lista al hacer clic fuera
document.addEventListener('click', function (e) {
    const l = document.getElementById('lista-predictiva');
    const i = document.getElementById('inputBusqueda');
    if (l && i && !i.contains(e.target) && !l.contains(e.target)) {
        l.classList.add('d-none');
    }
});