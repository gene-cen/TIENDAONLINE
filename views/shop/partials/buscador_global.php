<div class="container-fluid px-3 px-xl-5 my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="position-relative">
                <form onsubmit="event.preventDefault(); window.location.href='<?= BASE_URL ?>buscar/' + document.getElementById('inputBusqueda').value.trim().toLowerCase().replace(/\s+/g, '-');" class="d-flex shadow-sm rounded-pill bg-white overflow-hidden border border-1 border-secondary border-opacity-25" autocomplete="off">
                    
                    <input type="text" id="inputBusqueda" class="form-control border-0 shadow-none ps-3 ps-md-4 py-2 py-md-3 fs-6 fs-md-5 text-truncate" placeholder="¿Qué estás buscando hoy?" required onkeyup="if(typeof buscarPredictivo === 'function') buscarPredictivo(this.value)">
                    
                    <button type="submit" class="btn btn-cenco-green rounded-pill px-3 px-md-5 fw-black d-flex align-items-center m-1 fs-6 fs-md-5 text-white flex-shrink-0">
                        <i class="bi bi-search me-1 me-md-2 d-none d-sm-inline"></i> Buscar
                    </button>

                </form>
                <ul id="lista-predictiva" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000; top: 100%; left: 0; border-radius: 0 0 15px 15px; overflow: hidden;"></ul>
            </div>
        </div>
    </div>
</div>