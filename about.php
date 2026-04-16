<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>
<h2>VideoStore ZZZ.</h2>
<p>Fundada por un grupo de amigos con gusto a los gachas, VideoStore ZZZ ofrece lo ultimo en series, peliculas y de todo, si no lo tenemos se lo conseguimos.<br>
contacto_zzzetoso@videostorezzz.com  +52 111 222 3333</p>

<div id="companyMap" class="map-container"></div>

<!--Mapa-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
//Ubicación de la empresa
const companyLat = 19.4326;
const companyLng = -99.1332;
const map = L.map('companyMap').setView([companyLat, companyLng], 16);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);
L.marker([companyLat, companyLng]).addTo(map)
    .bindPopup('<b>VideoStore ZZZ</b><br>Central de cintas magnéticas')
    .openPopup();
</script>

<style>
#companyMap { height: 400px; margin: 20px 0; border-radius: 12px; border: 1px solid #05d9e8; }
</style>

<?php include 'includes/footer.php'; ?>