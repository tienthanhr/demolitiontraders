<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 10px 0 40px 0;
            align-items: stretch;
            padding-left: 0;
            margin-left: 200px;
        }
        .cart-items {
            background: white;
            padding: 0 25px 25px 0;
            border-radius: 0 10px 10px 0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: 0;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.13);
            border-left: 4px solid #2f3192;
            display: flex;
            flex-direction: column;
            min-height: 420px;
            height: 100%;
            justify-content: stretch;
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 24px;
            border-bottom: 1px solid #eee;
            padding: 18px 0;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .item-details {
            flex: 1 1 0%;
            min-width: 0;
        }
        .item-details h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: bold;
            word-break: break-word;
        }
        .item-details p {
            margin: 0 0 4px 0;
            color: #444;
            font-size: 15px;
        }
        .item-price {
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
            margin-right: 24px;
            min-width: 90px;
            text-align: right;
            align-self: center;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 24px;
            align-self: center;
        }
        .quantity-control button {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 18px;
        }
        .quantity-control button:hover { background: #f5f5f5; }
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }
        .cart-item .remove-btn {
            background: none;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 28px;
            margin-left: 8px;
            display: flex;
            align-items: center;
        }
        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
            min-width: 280px;
        }
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .summary-row.total { font-size: 20px; font-weight: 700; color: #2f3192; border-top: 2px solid #2f3192; border-bottom: none; margin-top: 10px; }
        .empty-cart {
            text-align: center;
            padding: 0 20px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 350px;
        }
        .empty-cart i { font-size: 80px; color: #ddd; margin-bottom: 20px; }
        @media (max-width: 768px) {
            .cart-container { grid-template-columns: 1fr; gap: 16px; margin: 20px 0; }
            .cart-items { padding: 12px; min-height: 260px; }
            .cart-item { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; padding: 14px 0; }
            .cart-item img { width: 80px; height: 80px; margin-bottom: 6px; }
            .item-details h3 { font-size: 16px; }
            .item-price { font-size: 17px; }
            .quantity-control { width: 100%; justify-content: flex-start; gap: 6px; margin: 6px 0; }
            .quantity-control button, .quantity-control input { font-size: 16px; width: 32px; height: 32px; }
            .cart-summary { margin-top: 18px; padding: 14px; position: static; min-width: 0; }
            .summary-row { font-size: 15px; padding: 8px 0; }
            .summary-row.total { font-size: 17px; }
            .empty-cart { padding: 0 8px; min-height: 180px; }
            .empty-cart i { font-size: 60px; }
            .btn { font-size: 16px; padding: 12px 0; }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    <div class="cart-container">
        <div class="cart-items">
            <div id="cart-items"><!-- Cart items will be loaded here --></div>
        </div>
        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="summary-row" style="font-size: 11px; color: #666; border: none; padding-top: 5px;">
                <span><i class="fas fa-info-circle"></i> All prices include GST</span>
                <span></span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="total">$0.00</span>
            </div>
            <a href="checkout.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; margin-top: 20px;" id="checkout-button">
                Proceed to Checkout
            </a>
            <a href="shop.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; margin-top: 10px;">
                Continue Shopping
            </a>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        async function loadCart() {
            try {
                const response = await fetch('/demolitiontraders/api/cart/get');
                const data = await response.json();
                const container = document.getElementById('cart-items');
                const checkoutBtn = document.getElementById('checkout-button');
                if (data.items.length === 0) {
                    container.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h2>Browse items. Your cart is empty.</h2>
                            <a href="shop.php" class="btn btn-secondary" style="margin-top:16px;display:inline-block;">Browse Items</a>
                        </div>
                    `;
                    if (checkoutBtn) checkoutBtn.style.display = 'none';
                } else {
                    if (checkoutBtn) checkoutBtn.style.display = 'block';
                    container.innerHTML = data.items.map(item => `
                        <div class="cart-item">
                            <img src="${item.image || 'assets/images/no-image.jpg'}" alt="${item.name}">
                            <div class="item-details">
                                <h3>${item.name}</h3>
                                <p>${item.category_name || ''}</p>
                                <p>${item.description || ''}</p>
                            </div>
                            <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
                            <div class="quantity-control">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity}" onchange="updateQuantity(${item.product_id}, this.value)">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(${item.product_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `).join('');
                }
                // Update summary
                document.getElementById('subtotal').textContent = '$' + (data.summary?.subtotal ?? '0.00');
                document.getElementById('total').textContent = '$' + (data.summary?.total ?? '0.00');
            } catch (error) {
                console.error('Error loading cart:', error);
            }
        }
        // Gọi hàm loadCart khi trang sẵn sàng
        document.addEventListener('DOMContentLoaded', loadCart);
</script>
<script>
// Xóa sản phẩm khỏi giỏ hàng
async function removeItem(productId) {
    if (!confirm('Remove this item from your cart?')) return;
    try {
        const response = await fetch('/demolitiontraders/api/cart/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        if (response.ok) {
            loadCart();
            window.demolitionTraders?.updateCartCount?.();
        } else {
            window.demolitionTraders?.showNotification?.('Failed to remove item', 'error');
        }
    } catch (error) {
        window.demolitionTraders?.showNotification?.('Error removing item', 'error');
    }
}

// Cập nhật số lượng sản phẩm
async function updateQuantity(productId, newQty) {
    newQty = parseInt(newQty);
    if (isNaN(newQty) || newQty < 0) return;
    if (newQty === 0) {
        if (confirm('Quantity is 0. Remove this item from your cart?')) {
            await removeItem(productId);
        }
        return;
    }
    try {
        const response = await fetch('/demolitiontraders/api/cart/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: newQty })
        });
        if (response.ok) {
            loadCart();
            window.demolitionTraders?.updateCartCount?.();
        } else {
            window.demolitionTraders?.showNotification?.('Failed to update quantity', 'error');
        }
    } catch (error) {
        window.demolitionTraders?.showNotification?.('Error updating quantity', 'error');
    }
}
</script>
</body>
</html>
            <div class="cart-container">
                <div class="cart-items">
                    <div id="cart-items"><!-- Cart items will be loaded here --></div>
                </div>
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="summary-row" style="font-size: 11px; color: #666; border: none; padding-top: 5px;">
                        <span><i class="fas fa-info-circle"></i> All prices include GST</span>
                        <span></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; margin-top: 20px;" id="checkout-button">
                        Proceed to Checkout
                    </a>
                    <a href="shop.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; margin-top: 10px;">
                        Continue Shopping
                    </a>
                </div>
            </div>
            <?php include 'components/footer.php'; ?>
            <script src="assets/js/main.js"></script>
            <script>
                async function loadCart() {
                    try {
                        const response = await fetch('/demolitiontraders/api/cart/get');
                        const data = await response.json();
                        const container = document.getElementById('cart-items');
                        const checkoutBtn = document.getElementById('checkout-button');
                        if (data.items.length === 0) {
                            container.innerHTML = `
                                <div class="empty-cart">
                                    <i class="fas fa-shopping-cart"></i>
                                    <h2>Browse items. Your cart is empty.</h2>
                                    <a href="shop.php" class="btn btn-secondary" style="margin-top:16px;display:inline-block;">Browse Items</a>
                                </div>
                            `;
                            if (checkoutBtn) checkoutBtn.style.display = 'none';
                        } else {
                            if (checkoutBtn) checkoutBtn.style.display = 'block';
                            container.innerHTML = data.items.map(item => `
                                <div class="cart-item">
                                    <img src="${item.image || 'assets/images/no-image.jpg'}" alt="${item.name}">
                                    <div class="item-details">
                                        <h3>${item.name}</h3>
                                        <p class="item-price">$${parseFloat(item.price).toFixed(2)}</p>
                                    </div>
                                    <div class="quantity-control">
                                        <button onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                        <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity}" 
                                            onchange="updateQuantity(${item.product_id}, this.value)">
                                        <button onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                                    </div>
                                    <button onclick="removeItem(${item.product_id})" style="background: none; border: none; color: #f44336; cursor: pointer; font-size: 24px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `).join('');
                        }
                        // Update summary
                        document.getElementById('subtotal').textContent = '$' + (data.summary?.subtotal ?? '0.00');
                        document.getElementById('total').textContent = '$' + (data.summary?.total ?? '0.00');
                    } catch (error) {
                        console.error('Error loading cart:', error);
                    }
                }
                // Gọi hàm loadCart khi trang sẵn sàng
                document.addEventListener('DOMContentLoaded', loadCart);
            </script>
        </body>
        </html>
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-details h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .item-price {
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-control button {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 18px;
        }
        .quantity-control button:hover {
            background: #f5f5f5;
        }
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }
        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
            border-top: 2px solid #2f3192;
            border-bottom: none;
            margin-top: 10px;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
                gap: 16px;
                margin: 20px 0;
            }
            .cart-items {
                padding: 12px;
            }
            .cart-item {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 14px 0;
            }
            .cart-item img {
                width: 80px;
                height: 80px;
                margin-bottom: 6px;
            }
            .item-details h3 {
                font-size: 16px;
            }
            .item-price {
                font-size: 17px;
            }
            .quantity-control {
                width: 100%;
                justify-content: flex-start;
                gap: 6px;
                margin: 6px 0;
            }
            .quantity-control button, .quantity-control input {
                font-size: 16px;
                width: 32px;
                height: 32px;
            }
            .cart-summary {
                margin-top: 18px;
                padding: 14px;
                position: static;
            }
            .summary-row {
                font-size: 15px;
                padding: 8px 0;
            }
            .summary-row.total {
                font-size: 17px;
            }
            .empty-cart {
                padding: 30px 8px;
            }
            .empty-cart i {
                font-size: 60px;
            }
            .btn {
                font-size: 16px;
                padding: 12px 0;
            }
        }
            .cart-item {
                display: block;
                width: 100%;
                padding: 14px 0 8px 0;
                border-bottom: 1px solid #eee;
            }
            .cart-item img {
                display: block;
                width: 80px;
                height: 80px;
                margin: 0 auto 8px auto;
            }
            .item-details {
                text-align: center;
                margin-bottom: 8px;
            }
            .item-details h3 {
                font-size: 16px;
            }
            .item-price {
                font-size: 17px;
            }
            .quantity-control {
                width: 100%;
                justify-content: center;
                gap: 6px;
                margin: 6px 0 0 0;
            }
            .quantity-control button, .quantity-control input {
                font-size: 16px;
                width: 32px;
                height: 32px;
            }
            .cart-item button[onclick^="removeItem"] {
                display: block;
                margin: 10px auto 0 auto;
                font-size: 22px;
            }
                <div class="summary-row" style="font-size: 11px; color: #666; border: none; padding-top: 5px;">
                    <span><i class="fas fa-info-circle"></i> All prices include GST</span>
                    <span></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total">$0.00</span>
                </div>
                <a href="checkout.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; margin-top: 20px;" id="checkout-button">
                    Proceed to Checkout
                </a>
                <a href="shop.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; margin-top: 10px;">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        async function loadCart() {
            try {
                const response = await fetch('/demolitiontraders/api/cart/get');
                const data = await response.json();
                const container = document.getElementById('cart-items');
                const checkoutBtn = document.getElementById('checkout-button');
                if (data.items.length === 0) {
                    container.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h2>Browse items. Your cart is empty.</h2>
                            <a href="shop.php" class="btn btn-secondary" style="margin-top:16px;display:inline-block;">Browse Items</a>
                        </div>
                    `;
                    if (checkoutBtn) checkoutBtn.style.display = 'none';
                } else {
                    if (checkoutBtn) checkoutBtn.style.display = 'block';
                    container.innerHTML = data.items.map(item => `
                        <div class="cart-item">
                            <img src="${item.image || 'assets/images/no-image.jpg'}" alt="${item.name}">
                            <div class="item-details">
                                <h3>${item.name}</h3>
                                <p class="item-price">$${parseFloat(item.price).toFixed(2)}</p>
                            </div>
                            <div class="quantity-control">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity}" 
                                    onchange="updateQuantity(${item.product_id}, this.value)">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                            </div>
                            <button onclick="removeItem(${item.product_id})" style="background: none; border: none; color: #f44336; cursor: pointer; font-size: 24px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `).join('');
                }
                // Update summary
                document.getElementById('subtotal').textContent = '$' + (data.summary?.subtotal ?? '0.00');
                document.getElementById('total').textContent = '$' + (data.summary?.total ?? '0.00');
            } catch (error) {
                console.error('Error loading cart:', error);
            }
        }
                                </div>
                                <div class="quantity-control">
                                    <button onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                    <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity}" 
                                        onchange="updateQuantity(${item.product_id}, this.value)">
                                    <button onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                                </div>
                                <button onclick="removeItem(${item.product_id})" style="background: none; border: none; color: #f44336; cursor: pointer; font-size: 24px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `).join('');
                
                        // Update summary
                        document.getElementById('subtotal').textContent = '$' + data.summary.subtotal;
                        document.getElementById('total').textContent = '$' + data.summary.total;
                
                    } catch (error) {
                        console.error('Error loading cart:', error);
                    }
                }
