<main id="view-carrinho" class="main-view cart-main-container" style="display:none;">
    <h2 class="cart-title"><i class="bi bi-cart3"></i> Seu Carrinho</h2>
    <div class="cart-list">
        <p style="text-align:center; padding: 40px; color: #666;">Seu carrinho está vazio.</p>
    </div>

    <div class="cart-footer">
        <a href="#" class="continue-btn" onclick="switchAppView('view-catalogo', document.getElementById('nav-catalogo-btn'))">Continuar escolhendo</a>
        <a href="#" class="checkout-btn" onclick="openCheckoutModal(event)">
            Finalizar Pedido <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</main>
