require('dotenv').config();
const express = require('express');
const axios   = require('axios');
const cors    = require('cors');
const app     = express();

app.use(cors());
app.use(express.json());

const OPENWEATHER_KEY = process.env.OPENWEATHER_KEY;
const PAYPAL_CLIENT_ID = process.env.PAYPAL_CLIENT_ID;
const PAYPAL_SECRET    = process.env.PAYPAL_SECRET;
const PAYPAL_API       = 'https://api-m.sandbox.paypal.com';

// ── Clima ──────────────────────────────────────────────
app.get('/api/weather', async (req, res) => {
    const { lat, lon } = req.query;
    if (!lat || !lon) {
        return res.status(400).json({ error: 'Se requieren lat y lon' });
    }
    try {
        const response = await axios.get(
            `https://api.openweathermap.org/data/2.5/weather`,
            { params: { lat, lon, appid: OPENWEATHER_KEY, units: 'metric', lang: 'es' } }
        );
        res.json(response.data);
    } catch (e) {
        console.error('Weather error:', e.response?.data || e.message);
        res.status(500).json({ error: e.message });
    }
});

// ── Ubicación por IP ───────────────────────────────────
app.get('/api/ip-location', async (req, res) => {
    let clientIp = req.headers['x-forwarded-for'] || req.socket.remoteAddress || '';
    if (clientIp === '::1' || clientIp === '127.0.0.1') clientIp = '8.8.8.8';
    if (clientIp.includes(',')) clientIp = clientIp.split(',')[0].trim();

    try {
        const geo = await axios.get(
            `http://ip-api.com/json/${clientIp}?fields=status,lat,lon,city,country`
        );
        if (geo.data.status === 'success') {
            res.json({ lat: geo.data.lat, lon: geo.data.lon, city: `${geo.data.city}, ${geo.data.country}` });
        } else {
            res.json({ lat: 19.4326, lon: -99.1332, city: 'Ciudad de México (fallback)' });
        }
    } catch (error) {
        console.error('ip-api error:', error.message);
        res.json({ lat: 19.4326, lon: -99.1332, city: 'Ciudad de México (fallback)' });
    }
});

// ── PayPal: obtener token ──────────────────────────────
async function getPayPalToken() {
    const auth = Buffer.from(`${PAYPAL_CLIENT_ID}:${PAYPAL_SECRET}`).toString('base64');
    const response = await axios.post(
        `${PAYPAL_API}/v1/oauth2/token`,
        'grant_type=client_credentials',
        { headers: { 'Authorization': `Basic ${auth}`, 'Content-Type': 'application/x-www-form-urlencoded' } }
    );
    return response.data.access_token;
}

// ── PayPal: crear orden ────────────────────────────────
// FIX: ahora acepta `currency` desde el body para que coincida
// con el currency del SDK de PayPal cargado en el frontend.
// Si el frontend usa &currency=MXN, el body debe enviar currency:'MXN'
app.post('/api/create-paypal-order', async (req, res) => {
    // currency por defecto MXN para coincidir con el SDK del frontend
    const { amount, product, currency = 'MXN' } = req.body;

    if (!amount || !product) {
        return res.status(400).json({ error: 'Faltan amount o product' });
    }

    try {
        const token    = await getPayPalToken();
        const response = await axios.post(
            `${PAYPAL_API}/v2/checkout/orders`,
            {
                intent: 'CAPTURE',
                purchase_units: [{
                    amount: {
                        currency_code: currency,   // ← usa la moneda recibida del frontend
                        value: parseFloat(amount).toFixed(2)
                    },
                    description: product
                }]
            },
            { headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' } }
        );
        res.json({ id: response.data.id });
    } catch (e) {
        console.error('create-order error:', e.response?.data || e.message);
        res.status(500).json({ error: e.response?.data?.message || e.message });
    }
});

// ── PayPal: capturar orden ─────────────────────────────
app.post('/api/capture-paypal-order', async (req, res) => {
    const { orderID } = req.body;
    if (!orderID) return res.status(400).json({ error: 'Falta orderID' });

    try {
        const token    = await getPayPalToken();
        const response = await axios.post(
            `${PAYPAL_API}/v2/checkout/orders/${orderID}/capture`,
            {},
            { headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' } }
        );
        res.json(response.data);
    } catch (e) {
        console.error('capture error:', e.response?.data || e.message);
        res.status(500).json({ error: e.response?.data?.message || e.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`Node API corriendo en puerto ${PORT}`);
});