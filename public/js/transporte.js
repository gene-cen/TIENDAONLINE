/**
 * ARCHIVO: transporte.js
 * Lógica para la aplicación móvil de transportistas.
 */

function fotoLista(input, id) {
    if (input.files && input.files[0]) {
        const zone = document.getElementById('zone-' + id);
        const icon = document.getElementById('icon-' + id);
        const text = document.getElementById('text-' + id);
        
        if(zone) zone.classList.add('foto-ok');
        if(icon) icon.className = 'bi bi-check-circle-fill fs-1 d-block mb-2';
        if(text) text.innerText = '¡Foto Capturada Correctamente!';
    }
}

function procesarEntrega(event, id) {
    event.preventDefault();
    const btn = event.target.querySelector('button');
    const form = event.target;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> GUARDANDO...';

    if (!navigator.geolocation) {
        Swal.fire('Error', 'Dispositivo sin GPS compatible.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
        return;
    }

    navigator.geolocation.getCurrentPosition((pos) => {
        form.querySelector('.lat').value = pos.coords.latitude;
        form.querySelector('.lng').value = pos.coords.longitude;

        fetch(window.BASE_URL + 'transporte/finalizarEntrega', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: '¡ÉXITO!',
                    text: 'Entrega registrada en el sistema.',
                    icon: 'success',
                    confirmButtonColor: '#76C043',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    const card = document.getElementById('orden-' + id);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => card.remove(), 300);
                    }
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
            }
        });
    }, (err) => {
        Swal.fire('Se requiere GPS', 'Por favor activa la ubicación de tu celular.', 'warning');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-all fs-4"></i> ENTREGAR';
    }, { enableHighAccuracy: true });
}