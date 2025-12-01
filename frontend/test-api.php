<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <script src="assets/js/api-helper.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            margin-top: 0;
            color: #2f3192;
        }
        button {
            background: #2f3192;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #1f2172;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
        }
        .success {
            border-left: 4px solid #28a745;
        }
        .error {
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>🔧 API Connection Test</h1>
    
    <div class="info">
        <strong>Test Environment:</strong><br>
        Base URL: <span id="baseUrl"></span><br>
        API URL: <span id="apiUrl"></span>
    </div>

    <div class="test-section">
        <h2>1. Health Check</h2>
        <button onclick="testHealth()">Test Health Endpoint</button>
        <div id="healthResult" class="result" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h2>2. Products API</h2>
        <button onclick="testProducts()">Get Products</button>
        <button onclick="testSingleProduct()">Get Single Product</button>
        <div id="productsResult" class="result" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h2>3. Categories API</h2>
        <button onclick="testCategories()">Get Categories</button>
        <div id="categoriesResult" class="result" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h2>4. Opening Hours API</h2>
        <button onclick="testOpeningHours()">Get Opening Hours</button>
        <div id="openingHoursResult" class="result" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h2>5. Cart API (Requires Login)</h2>
        <button onclick="testCart()">Get Cart</button>
        <div id="cartResult" class="result" style="display:none;"></div>
    </div>

    <script>
        // Display environment info
        document.getElementById('baseUrl').textContent = document.querySelector('base').href;
        document.getElementById('apiUrl').textContent = window.getApiUrl('/api/');

        function displayResult(elementId, success, data) {
            const el = document.getElementById(elementId);
            el.style.display = 'block';
            el.className = 'result ' + (success ? 'success' : 'error');
            el.textContent = JSON.stringify(data, null, 2);
        }

        async function testHealth() {
            try {
                console.log('Testing health endpoint...');
                const result = await window.apiFetch(window.getApiUrl('/api/index.php?request=health'));
                displayResult('healthResult', true, result);
            } catch (error) {
                console.error('Health check failed:', error);
                displayResult('healthResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error
                });
            }
        }

        async function testProducts() {
            try {
                console.log('Testing products endpoint...');
                const result = await window.apiGet('/api/index.php', {
                    request: 'products',
                    page: 1,
                    limit: 5
                });
                displayResult('productsResult', true, {
                    count: result.products?.length || 0,
                    total: result.total,
                    sample: result.products?.[0]
                });
            } catch (error) {
                console.error('Products test failed:', error);
                displayResult('productsResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error
                });
            }
        }

        async function testSingleProduct() {
            try {
                console.log('Testing single product endpoint...');
                const result = await window.apiFetch(window.getApiUrl('/api/index.php?request=products/1'));
                displayResult('productsResult', true, result);
            } catch (error) {
                console.error('Single product test failed:', error);
                displayResult('productsResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error
                });
            }
        }

        async function testCategories() {
            try {
                console.log('Testing categories endpoint...');
                const result = await window.apiFetch(window.getApiUrl('/api/products/categories.php'));
                displayResult('categoriesResult', true, {
                    count: Array.isArray(result) ? result.length : 'N/A',
                    categories: result
                });
            } catch (error) {
                console.error('Categories test failed:', error);
                displayResult('categoriesResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error
                });
            }
        }

        async function testOpeningHours() {
            try {
                console.log('Testing opening hours endpoint...');
                const result = await window.apiFetch(window.getApiUrl('/api/opening-hours.php'));
                displayResult('openingHoursResult', true, result);
            } catch (error) {
                console.error('Opening hours test failed:', error);
                displayResult('openingHoursResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error
                });
            }
        }

        async function testCart() {
            try {
                console.log('Testing cart endpoint...');
                const result = await window.apiFetch(window.getApiUrl('/api/cart/get.php'));
                displayResult('cartResult', true, result);
            } catch (error) {
                console.error('Cart test failed:', error);
                displayResult('cartResult', false, {
                    error: error.message || 'Failed to fetch',
                    details: error,
                    note: 'This might fail if not logged in'
                });
            }
        }

        // Auto-run health check on load
        window.addEventListener('DOMContentLoaded', () => {
            console.log('API Helper loaded, running health check...');
            setTimeout(() => {
                testHealth();
            }, 500);
        });
    </script>
</body>
</html>
