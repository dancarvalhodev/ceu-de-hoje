const button = document.querySelector('[data-geolocation]');

if (button) {
    button.addEventListener('click', fillWithCurrentPosition);
}

function fillWithCurrentPosition() {
    const status = document.querySelector('[data-geolocation-status]');

    if (!('geolocation' in navigator)) {
        status.textContent = 'Seu navegador não informa a localização.';
        return;
    }

    status.textContent = 'Buscando sua localização...';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
            status.textContent = 'Localização preenchida.';
        },
        () => {
            status.textContent = 'Não foi possível obter a localização. Confira a permissão do navegador.';
        },
    );
}
