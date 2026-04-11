// public/js/shop/catalogo.js
document.addEventListener("DOMContentLoaded", function() {
    var slider = document.getElementById('price-slider');
    if (!slider) return;

    // Leemos las variables inyectadas desde PHP en window.CatalogoConfig
    var minGlobal = window.CatalogoConfig.minGlobal;
    var maxGlobal = window.CatalogoConfig.maxGlobal;
    var currentMin = window.CatalogoConfig.currentMin;
    var currentMax = window.CatalogoConfig.currentMax;

    noUiSlider.create(slider, {
        start: [currentMin, currentMax],
        connect: true,
        range: { 'min': minGlobal, 'max': maxGlobal },
        step: 100,
        format: {
            to: function(v) { return Math.round(v) },
            from: function(v) { return Number(v) }
        }
    });

    var inputMin = document.getElementById('input-min');
    var inputMax = document.getElementById('input-max');
    var labelMin = document.getElementById('label-min');
    var labelMax = document.getElementById('label-max');
    
    const formatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' });

    slider.noUiSlider.on('update', function(values, handle) {
        var value = values[handle];
        if (handle) {
            inputMax.value = value;
            labelMax.innerHTML = formatter.format(value);
        } else {
            inputMin.value = value;
            labelMin.innerHTML = formatter.format(value);
        }
    });
});