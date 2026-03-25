<main id="view-carrinho" class="main-view cart-main-container" style="display:none;">
    <h2 class="cart-title"><i class="bi bi-cart3"></i> Seu Carrinho</h2>
    <div class="cart-list">
        <div class="cart-item">
            <img src="LEDAMARELO.jpg" alt="Led Amarelo" class="cart-item-img">
            <div class="cart-item-info">
                <strong class="cart-item-name">Led Amarelo</strong>
                <span class="cart-item-code">Cód: #L001</span>
            </div>
            <div class="quantity-controls">
                <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                <input type="text" value="10" class="qty-input" readonly>
                <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
            </div>
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho.', 'warning')"><i class="bi bi-trash"></i></button>
        </div>

        <div class="cart-item">
            <img src="LEDAZUL.jpg" alt="Led Azul" class="cart-item-img">
            <div class="cart-item-info">
                <strong class="cart-item-name">Led Azul</strong>
                <span class="cart-item-code">Cód: #L002</span>
            </div>
            <div class="quantity-controls">
                <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                <input type="text" value="5" class="qty-input" readonly>
                <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
            </div>
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho.', 'warning')"><i class="bi bi-trash"></i></button>
        </div>

        <div class="cart-item">
            <img src="LEDVERDE.jpg" alt="Led Verde" class="cart-item-img">
            <div class="cart-item-info">
                <strong class="cart-item-name">Led Verde</strong>
                <span class="cart-item-code">Cód: #L003</span>
            </div>
            <div class="quantity-controls">
                <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                <input type="text" value="20" class="qty-input" readonly>
                <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
            </div>
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho.', 'warning')"><i class="bi bi-trash"></i></button>
        </div>
    </div>

    <div class="cart-footer">
        <a href="#" class="continue-btn" onclick="switchAppView('view-catalogo', document.getElementById('nav-catalogo-btn'))">Continuar escolhendo</a>
        <a href="#" class="checkout-btn" onclick="openCheckoutModal(event)">
            Finalizar Pedido <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</main>