<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>

<h2>Mapa interactivo. Pa moverle por si gustas</h2>
<div id="map" class="map-container"></div>
<div id="placesList" style="background: #11131f; padding: 15px; border-radius: 12px; margin-top: 20px;">
    <h3>Tiendas y lugares de interes cercanos</h3>
    <ul id="placesUl"><li>Cargando lugares cercanos...</li></ul>
</div>

<!--Mapa interactivo-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const NODE_API = '<?php echo getenv('NODE_API_URL') ?: 'http://localhost:3000'; ?>';
    let map;
    let userMarker;

    function initMap(lat, lng, ciudad) {
        map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        userMarker = L.marker([lat, lng], {
            icon: L.divIcon({ className: 'custom-marker', html: '📍', iconSize: [20, 20] })
        }).addTo(map).bindPopup(`Tu ubicación aproximada: ${ciudad}`).openPopup();

        fetchNearbyPlaces(lat, lng);
    }

    async function fetchNearbyPlaces(lat, lng) {
        const radius = 500;
        const query = `
            [out:json];
            (
              node["shop"](around:${radius},${lat},${lng});
              node["amenity"="cinema"](around:${radius},${lat},${lng});
              node["shop"="video"](around:${radius},${lat},${lng});
              way["shop"](around:${radius},${lat},${lng});
              way["amenity"="cinema"](around:${radius},${lat},${lng});
            );
            out body;
        `;
        const overpassUrl = 'https://overpass-api.de/api/interpreter';
        try {
            const response = await fetch(overpassUrl, {
                method: 'POST',
                body: `data=${encodeURIComponent(query)}`,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            const data = await response.json();
            displayPlaces(data.elements);
        } catch (error) {
            console.error('Error al cargar lugares:', error);
            document.getElementById('placesUl').innerHTML = '<li>Error al cargar lugares cercanos.</li>';
        }
    }

    function displayPlaces(places) {
        const ul = document.getElementById('placesUl');
        ul.innerHTML = '';
        if (!places || places.length === 0) {
            ul.innerHTML = '<li>No se encontraron tiendas o cines cercanos.</li>';
            return;
        }
        
        let count = 0;
        places.forEach(place => {
            let name = place.tags?.name || place.tags?.shop || place.tags?.amenity || 'Lugar sin nombre';
            let lat = place.lat;
            let lon = place.lon;
            if (place.center) {
                lat = place.center.lat;
                lon = place.center.lon;
            }
            if (lat && lon && name) {
                // Agregar marcador
                L.marker([lat, lon]).addTo(map).bindPopup(name);
                // Listar en el panel
                const li = document.createElement('li');
                li.textContent = `${name}`;
                ul.appendChild(li);
                count++;
            }
        });
        if (count === 0) ul.innerHTML = '<li>No se encontraron lugares con nombre.</li>';
    }

    //Obtener ubicación por IP desde el servidor Node
    function getLocationFromServer() {
        fetch(`${NODE_API}/api/ip-location`)
            .then(response => response.json())
            .then(data => {
                initMap(data.lat, data.lon, data.city);
            })
            .catch(err => {
                console.error('Error al obtener ubicación por IP:', err);
                // Fallback: Ciudad de México
                initMap(19.4326, -99.1332, "Ciudad de México (fallback)");
            });
    }

    //Iniciar en caso de no tener permisos.
    getLocationFromServer();
</script>

<style>
.custom-marker {
    background: none;
    border: none;
    font-size: 24px;
    filter: drop-shadow(0 0 4px #05d9e8);
}
.leaflet-container {
    background: #0a0c12;
    border-radius: 12px;
}
</style>

<?php include 'includes/footer.php'; ?>