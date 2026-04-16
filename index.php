<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>

<h1>Bienveni3, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>

<!--boton para pedir la ubicacion, no aparece si ya se dio permisos de ubicacion cuando entras a la pagina-->
<div id="locationBanner" style="
    background: #1a1d2b;
    border: 1px solid #05d9e8;
    border-radius: 10px;
    padding: 14px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    font-family: monospace;
">
    <span style="color: #05d9e8;">
        Se necesita aceptar los permisos de ubicacion para mostrarte tu ubicacion.
    </span>
    <button id="btnPermitirUbicacion" style="
        background: #05d9e8;
        color: #0f111a;
        border: none;
        border-radius: 20px;
        padding: 8px 20px;
        font-family: monospace;
        font-weight: bold;
        cursor: pointer;
        font-size: 0.95rem;
        transition: opacity 0.2s;
    " onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
        Mostrar mi ubicacion
    </button>
</div>

<!--Mapa-->
<div id="map" class="map-container" style="height: 300px; margin-bottom: 20px; border-radius: 12px; overflow: hidden;"></div>

<!-- Panel de clima -->
<div id="weatherPanel" class="weather-box">
    <p>Cargando informacion del clima...</p>
</div>

<!--Mapa interactivo-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const NODE_API = '<?php echo getenv('NODE_API_URL') ?: 'http://localhost:3000'; ?>';
    const weatherDiv    = document.getElementById('weatherPanel');
    const banner        = document.getElementById('locationBanner');
    const btnPermitir   = document.getElementById('btnPermitirUbicacion');
    let map             = null;
    let locationGranted = false;

    //Mapa
    function initMap(lat, lng, label) {
        if (!map) {
            map = L.map('map').setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
        } else {
            map.setView([lat, lng], 14);
        }
        //Limpiar marcadores anteriores
        map.eachLayer(layer => { if (layer instanceof L.Marker) map.removeLayer(layer); });
        L.marker([lat, lng]).addTo(map)
            .bindPopup(`<b>${label}</b>`)
            .openPopup();
    }

    //Cargar el clima y el mapa con la ubicacion
    function cargarClimaYMapa(lat, lon, cityName) {
        weatherDiv.innerHTML = '<p>🌡️ Cargando clima...</p>';
        fetch(`${NODE_API}/api/weather?lat=${lat}&lon=${lon}`)
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(data => {
                const ciudad = cityName || data.name || 'Desconocida';
                weatherDiv.innerHTML = `
                    <strong>Ubicación:</strong> ${ciudad}<br>
                    <strong>Clima:</strong> ${data.weather[0].description} — ${Math.round(data.main.temp)}°C<br>
                    <strong>Humedad:</strong> ${data.main.humidity}%<br>
                    <strong>Sensación térmica:</strong> ${Math.round(data.main.feels_like)}°C
                `;
                initMap(lat, lon, ciudad);
            })
            .catch(() => {
                weatherDiv.innerHTML = '<p>❌ No se pudo obtener el clima. Verifica que el servidor Node este activo.</p>';
                initMap(lat, lon, cityName || 'Ubicación');
            });
    }

    //En caso de no dar permisos mostrar una ubicacion predeterminada
    function cargarPorIP() {
        weatherDiv.innerHTML = '<p>Obteniendo ubicacion por medio de la IP...</p>';
        fetch(`${NODE_API}/api/ip-location`)
            .then(r => r.json())
            .then(data => {
                cargarClimaYMapa(data.lat, data.lon, data.city + ' (ubicacion aproximada)');
            })
            .catch(() => {
                cargarClimaYMapa(19.4326, -99.1332, 'Ciudad de Mexico (predeterminado al no dar permisos de ubicacion)');
            });
    }

    //Solicitar geolocalizacion real
    function pedirGeolocalizacion() {
        if (!navigator.geolocation) {
            console.warn('Geolocalizacion no soportada por este navegador.');
            banner.style.display = 'none';
            cargarPorIP();
            return;
        }

        weatherDiv.innerHTML = '<p>Esperando permiso de ubicacion...</p>';

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                locationGranted = true;
                banner.style.display = 'none';
                cargarClimaYMapa(pos.coords.latitude, pos.coords.longitude, 'Tu ubicacion exacta');
            },
            (err) => {
                console.warn('Geolocalizacion denegada:', err.code, err.message);
                banner.innerHTML = `
                    <span style="color: #ff2a6d;">
                        Permiso negado. Mostrando ubicacion aproximada por IP.
                        <br><small style="color:#aaa;">Para ver tu ubicacion exacta, permite el acceso en la configuración de tu navegador y recarga la página.</small>
                    </span>
                `;
                cargarPorIP();
            },
            { timeout: 12000, enableHighAccuracy: true }
        );
    }

    //Evento para pedir permisos de ubicacion por el boton
    btnPermitir.addEventListener('click', function () {
        btnPermitir.textContent  = '⏳ Solicitando...';
        btnPermitir.disabled     = true;
        pedirGeolocalizacion();
    });

    //Al cargar la página verifica si ya se dieron los permisos de ubicacion
    //La API Permissions permite saber el estado sin disparar el popup
    if (navigator.permissions) {
        navigator.permissions.query({ name: 'geolocation' }).then(result => {
            if (result.state === 'granted') {
                banner.style.display = 'none';
                pedirGeolocalizacion();
            } else if (result.state === 'denied') {
                banner.innerHTML = `
                    <span style="color: #ff2a6d;">
                        Ubicacion bloqueada en tu navegador. Mostrando ubicacion aproximada por IP.
                        <br><small style="color:#aaa;">Para activarla: haz clic en el candado de la barra de dirección -> Permisos -> Ubicacion -> Permitir.</small>
                    </span>
                `;
                cargarPorIP();
            } else {
                cargarPorIP();
            }

            result.onchange = () => {
                if (result.state === 'granted' && !locationGranted) {
                    banner.style.display = 'none';
                    pedirGeolocalizacion();
                }
            };
        });
    } else {
        cargarPorIP();
    }
});
</script>

<?php include 'includes/footer.php'; ?>