<div id="tab-carrinho" class="spa-tab" style="display: none;">
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
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho', 'warning')"><i class="bi bi-trash"></i></button>
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
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho', 'warning')"><i class="bi bi-trash"></i></button>
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
            <button class="remove-btn" title="Remover item" onclick="showToast('Item removido do carrinho', 'warning')"><i class="bi bi-trash"></i></button>
        </div>

    </div>

    <div class="cart-footer">
        <a href="#" onclick="switchTab('tab-catalogo', 'Catálogo de Itens', 'nav-catalogo')" class="continue-btn">Continuar escolhendo</a>
        <button class="checkout-btn" onclick="openCheckoutModal()">
            Finalizar Pedido <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</div>