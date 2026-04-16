require('dotenv').config();
const express = require('express');
const axios = require('axios');
const cors = require('cors');
const app = express();
app.use(cors());
app.use(express.json());

const OPENWEATHER_KEY = process.env.OPENWEATHER_KEY;
const PAYPAL_CLIENT_ID = process.env.PAYPAL_CLIENT_ID;
const PAYPAL_SECRET = process.env.PAYPAL_SECRET;
const PAYPAL_API = 'https://api-m.sandbox.paypal.com';

//Endpoint clima
app.get('/api/weather', async (req, res) => {
    const { lat, lon } = req.query;
    try {
        const response = await axios.get(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${OPENWEATHER_KEY}&units=metric&lang=es`);
        res.json(response.data);
    } catch(e) { res.status(500).json({ error: e.message }); }
});

//PayPal para obtener token
async function getPayPalToken() {
    const auth = Buffer.from(`${PAYPAL_CLIENT_ID}:${PAYPAL_SECRET}`).toString('base64');
    const response = await axios.post(`${PAYPAL_API}/v1/oauth2/token`, 'grant_type=client_credentials', {
        headers: { 'Authorization': `Basic ${auth}`, 'Content-Type': 'application/x-www-form-urlencoded' }
    });
    return response.data.access_token;
}

//Crear orden
app.post('/api/create-paypal-order', async (req, res) => {
    const { amount, product } = req.body;
    const token = await getPayPalToken();
    const response = await axios.post(`${PAYPAL_API}/v2/checkout/orders`, {
        intent: 'CAPTURE',
        purchase_units: [{ amount: { currency_code: 'MXN', value: amount }, description: product }]
    }, { headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' } });
    res.json({ id: response.data.id });
});

// Capturar orden
app.post('/api/capture-paypal-order', async (req, res) => {
    const { orderID } = req.body;
    const token = await getPayPalToken();
    const response = await axios.post(`${PAYPAL_API}/v2/checkout/orders/${orderID}/capture`, {}, {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    res.json(response.data);
});

// Nuevo endpoint: obtener ubicación aproximada por IP del cliente
app.get('/api/ip-location', async (req, res) => {
    // Obtener la IP real del cliente (detrás de proxy o no)
    let clientIp = req.headers['x-forwarded-for'] || req.socket.remoteAddress;
    // Si es IPv6 loopback o local, usar una IP pública de prueba
    if (clientIp === '::1' || clientIp === '127.0.0.1') {
        clientIp = '8.8.8.8'; // IP de ejemplo (Google DNS)
    }
    // Limpiar si hay múltiples IPs
    if (clientIp.includes(',')) clientIp = clientIp.split(',')[0].trim();
    
    try {
        // Usamos ip-api.com (gratuito, sin clave, permite hasta 45 peticiones por minuto desde una IP)
        const geoResponse = await axios.get(`http://ip-api.com/json/${clientIp}?fields=status,lat,lon,city,country`);
        if (geoResponse.data.status === 'success') {
            res.json({
                lat: geoResponse.data.lat,
                lon: geoResponse.data.lon,
                city: `${geoResponse.data.city}, ${geoResponse.data.country}`
            });
        } else {
            // Fallback: coordenadas de Ciudad de México
            res.json({ lat: 19.4326, lon: -99.1332, city: "Ciudad de México (fallback)" });
        }
    } catch (error) {
        console.error('Error en ip-api:', error.message);
        res.json({ lat: 19.4326, lon: -99.1332, city: "Ciudad de México (fallback por error)" });
    }
});

app.listen(3000, '0.0.0.0', () => {
    console.log('Node API corriendo en puerto 3000');
});