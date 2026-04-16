<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>

<h2>Catálogo de Cassettes de Edición Limitada</h2>

<!-- Barra de búsqueda -->
<div style="margin: 20px 0; text-align: center;">
    <input type="text" id="searchInput"
           placeholder="Buscar casette..."
           style="width: 80%; max-width: 400px; background: #1a1d2b; border: 1px solid #05d9e8;
                  color: #05d9e8; padding: 10px 15px; border-radius: 30px; font-family: monospace;">
</div>

<div class="movie-grid" id="moviesGrid">
    <?php
    $productos = [
        ['id'=>1, 'name'=>'Mahou Shoujo Madoka Magica',    'price'=>180, 'img'=>'https://i.pinimg.com/1200x/c8/21/9b/c8219bfb6685029ff96e824bbb9105c1.jpg'],
        ['id'=>2, 'name'=>'Cyberpunk 2077 Edgerunners',    'price'=>200, 'img'=>'https://i.pinimg.com/1200x/3d/d4/30/3dd4305ed45d638e52a96e6e1dedb2eb.jpg'],
        ['id'=>3, 'name'=>'Promare',                        'price'=>120, 'img'=>'https://i.pinimg.com/736x/a5/81/27/a581278f7aa284625e0d56c02e32f7a6.jpg'],
        ['id'=>4, 'name'=>'Blue Period',                    'price'=>160, 'img'=>'https://i.pinimg.com/736x/f3/eb/41/f3eb4173645962c4205ccc187dbcdffd.jpg'],
        ['id'=>5, 'name'=>'Soul Eater',                     'price'=>180, 'img'=>'https://i.pinimg.com/736x/86/11/8c/86118cbc407f6f19110ada93675e6760.jpg'],
        ['id'=>6, 'name'=>'Jibaku Shounen Hanako Kun',      'price'=>180, 'img'=>'https://i.pinimg.com/736x/c7/9c/df/c79cdf0b2b92b6b503d84050d8c78ea7.jpg'],
        ['id'=>7, 'name'=>'Bleach',                         'price'=>220, 'img'=>'https://i.pinimg.com/736x/1f/c6/c5/1fc6c55bf1e8925ee00dcbf4d6046161.jpg'],
        ['id'=>8, 'name'=>'Uma Musume',                     'price'=>200, 'img'=>'https://i.pinimg.com/1200x/9f/ae/87/9fae87754323b97d61fa2071eca9c03c.jpg'],
    ];
    foreach ($productos as $prod): ?>
    <div class="movie-card"
         data-name="<?= strtolower(htmlspecialchars($prod['name'])) ?>"
         data-id="<?= $prod['id'] ?>"
         data-price="<?= $prod['price'] ?>">
        <img src="<?= $prod['img'] ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
        <h3><?= htmlspecialchars($prod['name']) ?></h3>
        <p class="price">$<?= number_format($prod['price'], 2) ?> MXN</p>
        <div class="paypal-button-container"
             id="paypal-btn-<?= $prod['id'] ?>"
             data-price="<?= $prod['price'] ?>"
             data-name="<?= htmlspecialchars($prod['name']) ?>">
        </div>
        <div id="pay-status-<?= $prod['id'] ?>" style="margin-top: 8px; font-family: monospace; font-size: 0.85rem;"></div>
    </div>
    <?php endforeach; ?>
</div>

<!--
    La moneda del SDK (&currency=MXN) debe coincidir EXACTAMENTE
    con la que el servidor Node envía a PayPal al crear la orden.
    El JS ahora envía { currency: 'MXN' } en el body para garantizarlo.
-->
<script src="https://www.paypal.com/sdk/js?client-id=<?= defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : 'AbXyNoP9kmOOz0lMg2QEE5KfQMjlnT-NWp0Eni0PowZeG2gfYx0XlxxvZ5m5ysu73-vGqGHfUwKLvEPM' ?>&currency=MXN&intent=capture"></script>

<script>
    // FIX: NODE_API apunta al servicio Node en Render.
    // En Render debes agregar la variable de entorno NODE_API_URL
    // con la URL pública de tu servicio Node, ej:
    // NODE_API_URL = https://tu-node-service.onrender.com
    const NODE_API  = '<?php echo rtrim(getenv('NODE_API_URL') ?: 'http://localhost:3000', '/'); ?>';
    const CURRENCY  = 'MXN'; // debe coincidir con &currency= del SDK de arriba

    // ── Filtro de búsqueda ────────────────────────────────
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const term = this.value.toLowerCase().trim();
        let anyVisible = false;
        document.querySelectorAll('.movie-card').forEach(card => {
            const visible = card.dataset.name.includes(term);
            card.style.display = visible ? '' : 'none';
            if (visible) anyVisible = true;
        });
        let msg = document.getElementById('noResultsMsg');
        if (!anyVisible) {
            if (!msg) {
                msg = document.createElement('p');
                msg.id = 'noResultsMsg';
                Object.assign(msg.style, { textAlign:'center', marginTop:'40px', color:'#ff2a6d', fontFamily:'monospace' });
                msg.innerText = 'No se encontraron películas con ese nombre.';
                document.getElementById('moviesGrid').after(msg);
            }
        } else { msg && msg.remove(); }
    });

    // ── Botones PayPal ────────────────────────────────────
    document.querySelectorAll('.paypal-button-container').forEach(container => {
        const price       = container.dataset.price;
        const productName = container.dataset.name;
        const productId   = container.id.replace('paypal-btn-', '');
        const statusDiv   = document.getElementById(`pay-status-${productId}`);

        paypal.Buttons({
            style: { layout:'vertical', color:'gold', shape:'rect', label:'buynow' },

            // 1. Crear orden — envía la moneda para que el servidor la use
            createOrder: async () => {
                statusDiv.innerHTML = '⏳ Creando orden...';
                try {
                    const response = await fetch(`${NODE_API}/api/create-paypal-order`, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            amount:   price,
                            product:  productName,
                            currency: CURRENCY   // ← FIX: enviamos la moneda al servidor
                        })
                    });
                    if (!response.ok) {
                        const err = await response.text();
                        throw new Error(`Servidor respondió ${response.status}: ${err}`);
                    }
                    const order = await response.json();
                    if (!order.id) throw new Error('El servidor no devolvió un ID de orden válido.');
                    statusDiv.innerHTML = '';
                    return order.id;
                } catch (err) {
                    console.error('createOrder error:', err);
                    statusDiv.innerHTML = `❌ ${err.message}`;
                    throw err;
                }
            },

            // 2. Capturar pago
            onApprove: async (data) => {
                statusDiv.innerHTML = '⏳ Procesando pago...';
                try {
                    const response = await fetch(`${NODE_API}/api/capture-paypal-order`, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ orderID: data.orderID })
                    });
                    if (!response.ok) throw new Error(`Captura falló con HTTP ${response.status}`);
                    const details = await response.json();
                    const nombre  = details.payer?.name?.given_name || 'cliente';
                    statusDiv.innerHTML = `✅ ¡Pago completado, ${nombre}! Cinta en camino 🎞️`;
                } catch (err) {
                    console.error('onApprove error:', err);
                    statusDiv.innerHTML = `❌ Error al confirmar pago: ${err.message}`;
                }
            },

            onCancel: () => { statusDiv.innerHTML = '⚠️ Pago cancelado.'; },
            onError:  (err) => {
                console.error('PayPal onError:', err);
                statusDiv.innerHTML = '❌ Error de PayPal. Revisa la consola del navegador.';
            }

        }).render(`#paypal-btn-${productId}`);
    });
</script>

<?php include 'includes/footer.php'; ?>