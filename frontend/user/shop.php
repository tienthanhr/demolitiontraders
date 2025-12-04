<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    
    <!-- Load API Helper -->
    <script src="assets/js/api-helper.js?v=1"></script>
    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
    
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="assets/css/csp-fixes.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1 id="page-title">Shop All Products</h1>
            <nav class="breadcrumb" id="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>Shop</span>
            </nav>
        </div>
    </div>
    
    <div class="shop-page">
        <div class="container">
            <!-- Filter Section (Full Width) -->
            <div class="filter-box" style="padding: 32px 32px 24px 32px;">
                <h2 class="filter-title">Filter</h2>
                <div class="filter-row" style="gap: 32px; display: flex; flex-wrap: wrap; align-items: stretch;">
                    <div class="filter-group" style="min-width:180px; margin-bottom: 18px;">
                        <label for="category-select">Category</label>
                        <select id="category-select" name="category" class="filter-select" autocomplete="off">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div id="dimension-row" style="display:flex; gap:24px; width:100%; max-width:700px; margin-bottom:0; display:none;">
                        <!-- Width Slider -->
                        <div class="filter-group" id="measurements-group" style="min-width:180px; flex:1;">
                            <label for="width-slider">Width (mm)</label>
                            <div id="width-slider"></div>
                            <span id="width-value">0 - 8000 mm</span>
                        </div>
                        <!-- Height Slider -->
                        <div class="filter-group" id="height-group" style="min-width:180px; flex:1;">
                            <label for="height-slider" style="margin-top:10px;">Height (mm)</label>
                            <div id="height-slider"></div>
                            <span id="height-value">0 - 8000 mm</span>
                        </div>
                    </div>
                    <div class="filter-group" id="measurements-group" style="display: none; min-width:260px;">
                        <label for="width-slider">Width (mm)</label>
                        <div id="width-slider"></div>
                        <span id="width-value">300 - 3000 mm</span>
                        <label for="height-slider" style="margin-top:10px;">Height (mm)</label>
                        <div id="height-slider"></div>
                        <span id="height-value">300 - 3000 mm</span>
                    </div>
                    <div class="filter-group" id="treatment-group" style="display: none; min-width:120px; margin-bottom: 18px;">
                        <label for="treatment-select">Treated/Untreated</label>
                        <select id="treatment-select" name="treatment" class="filter-select" autocomplete="off">
                            <option value="">All</option>
                            <option value="treated">Treated</option>
                            <option value="untreated">Untreated</option>
                        </select>
                    </div>
                    <div class="filter-group" id="thickness-group" style="display: none; min-width:120px; margin-bottom: 18px;">
                        <label for="thickness-select">Thickness</label>
                        <select id="thickness-select" name="thickness" class="filter-select" autocomplete="off">
                            <option value="">All</option>
                        </select>
                    </div>
                    <!-- Price Slider below width/height -->
                    <div class="filter-group" style="min-width:220px; max-width:400px; margin-bottom: 18px; width:100%; display:flex; flex-direction:column; justify-content:flex-end;">
                        <label for="price-slider" style="margin-bottom:4px;">Price Range ($NZD)</label>
                        <div style="display:flex; flex-direction:column; justify-content:flex-end;">
                            <div id="price-slider"></div>
                            <div style="text-align:center; margin-top:8px;">
                                <span id="price-value">0 - 10,000</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Keywords at the bottom -->
                    <div class="filter-group" style="flex:1; min-width:220px; margin-bottom: 18px; width:100%; display:flex; flex-direction:column; justify-content:flex-end;">
                        <label for="keywords-input">Keywords</label>
                        <input type="text" id="keywords-input" name="keywords" class="filter-input" placeholder="Search products..." autocomplete="off" style="width:100%; height:44px; font-size:1.1em; padding: 0 16px;">
                    </div>
                    <div class="filter-group" style="display:flex; flex-direction:column; justify-content:flex-end; min-width:160px; margin-bottom: 18px;">
                        <label for="search-btn">&nbsp;</label>
                        <button id="search-btn" name="search-btn" class="btn btn-primary" style="width: 100%; height:44px; font-size:1.1em; align-self:flex-end;">SEARCH</button>
                    </div>
                </div>
            </div>
            
            <!-- Sort By Section -->
            <div class="shop-header">
                <div class="results-info">
                    <span id="results-count">Loading...</span>
                </div>
                <div class="shop-controls">
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by" name="sort" onchange="applyFilters()" autocomplete="off">
                        <option value="">Default</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A to Z</option>
                        <option value="name_desc">Name: Z to A</option>
                    </select>
                </div>
            </div>

            <!-- Pagination Top -->
            <div class="pagination" id="pagination-top"></div>
            <br></br>
            <!-- Products Grid -->
            <div class="products-grid" id="products-grid">
                <!-- Products loaded via JavaScript -->
            </div>

            <!-- Pagination Bottom -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
<?php include '../components/toast-notification.php'; ?>
    
    <!-- Chỉ giữ 1 dòng CDN noUiSlider, đặt trước main.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
    <script src="assets/js/main.js"></script>
    <style>
        /* Responsive filter sliders */
        .filter-group #width-slider,
        .filter-group #height-slider {
            max-width: 260px;
            min-width: 120px;
            width: 100%;
        }
        .filter-group #price-slider {
            max-width: 380px;
            min-width: 120px;
            width: 100%;
        }
    /* Ẩn tooltip số trên hai đầu slider price */
    #price-slider .noUi-tooltip,
    #width-slider .noUi-tooltip,
    #height-slider .noUi-tooltip {
        display: none !important;
    }
    /* Thu nhỏ slider và handle */
    #width-slider .noUi-handle,
    #height-slider .noUi-handle,
    #price-slider .noUi-handle {
        width: 18px;
        height: 18px;
        top: -6px;
        border-radius: 8px;
    }
    #width-slider,
    #height-slider,
    #price-slider {
        height: 8px;
        margin-top: 8px;
        margin-bottom: 8px;
    }
    #width-value, #height-value, #price-value {
        font-size: 14px;
        margin-top: 0;
        margin-bottom: 8px;
    }
    /* Hide spinner arrows for pagination input */
    .pagination input[type=number]::-webkit-inner-spin-button, 
    .pagination input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .pagination input[type=number] {
        -moz-appearance: textfield;
    }
    </style>
    <script>
        let currentPage = 1;
        let cartItems = [];
        
        // Update breadcrumb based on selected category
        function updateBreadcrumb() {
            const categorySelect = document.getElementById('category-select');
            const breadcrumb = document.getElementById('breadcrumb');
            const pageTitle = document.getElementById('page-title');
            
            if (!categorySelect || categorySelect.value === '') {
                breadcrumb.innerHTML = '<a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>Shop</span>';
                if (pageTitle) pageTitle.textContent = 'Shop All Products';
            } else {
                const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
                breadcrumb.innerHTML = '<a href="<?php echo userUrl('index.php'); ?>">Home</a> / <a href="<?php echo userUrl('shop.php'); ?>">Shop</a> / <span>' + categoryName + '</span>';
                if (pageTitle) pageTitle.textContent = categoryName;
            }
        }
        
        // Load cart items
        async function loadCartItems() {
            try {
                const data = await apiFetch(getApiUrl('/api/cart/list.php'));
                if (data.success && Array.isArray(data.data)) {
                    cartItems = data.data.map(item => item.product_id);
                } else {
                    cartItems = [];
                }
            } catch (error) {
                console.error('Error loading cart items:', error);
                cartItems = [];
            }
        }
        
        // Load categories
        async function loadCategories() {
            try {
                const data = await apiFetch(getApiUrl('/api/index.php?request=categories'));
                
                console.log('Categories response:', data);
                
                const categories = data.data || data;
                
                // Populate category dropdown
                const categorySelect = document.getElementById('category-select');
                if (Array.isArray(categories)) {
                    categorySelect.innerHTML = '<option value="">All Categories</option>' +
                        categories.map(cat => '<option value="' + cat.id + '" data-slug="' + cat.slug + '">' + cat.name + '</option>').join('');
                    
                    // Check if there's a category parameter in the URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const categorySlug = urlParams.get('category');
                    const searchKeyword = urlParams.get('search');
                    
                    if (categorySlug) {
                        // Find option by slug and select it
                        const options = categorySelect.querySelectorAll('option');
                        for (let option of options) {
                            if (option.getAttribute('data-slug') === categorySlug) {
                                categorySelect.value = option.value;
                                handleCategoryChange();
                                break;
                            }
                        }
                    }
                    
                    if (searchKeyword) {
                        const keywordsInput = document.getElementById('keywords-input');
                        if (keywordsInput) {
                            keywordsInput.value = searchKeyword;
                        }
                    }
                } else {
                    console.error('Categories not an array:', categories);
                }
                
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }
        
        // Handle category change
       function handleCategoryChange() {
    const categorySelect = document.getElementById('category-select');
    const selectedValue = categorySelect.value;
    const selectedText = categorySelect.options[categorySelect.selectedIndex].text.toLowerCase();
    // Hide all dynamic filter groups
    document.getElementById('treatment-group').style.display = 'none';
    document.getElementById('thickness-group').style.display = 'none';
    document.getElementById('dimension-row').style.display = 'none';
    // Show relevant filters based on category
    if (selectedText.includes('plywood') || selectedText.includes('timber') || selectedText.includes('wood')) {
        document.getElementById('treatment-group').style.display = 'block';
        document.getElementById('thickness-group').style.display = 'block';
    }
    if (
        selectedText.includes('door') ||
        selectedText.includes('window') ||
        selectedText.includes('sliding door')
    ) {
        document.getElementById('dimension-row').style.display = 'flex';
    }

    // Nếu chọn All Categories thì remove param category khỏi URL
    const url = new URL(window.location.href);
    url.searchParams.delete('category');
    // Nếu có search thì giữ lại, không thì về shop.php
    const searchVal = document.getElementById('keywords-input')?.value || '';
    if (selectedValue === '') {
        if (searchVal) {
            url.search = 'search=' + encodeURIComponent(searchVal);
        } else {
            window.location.href = BASE_PATH + 'shop.php';
            return;
        }
    } else {
        url.searchParams.set('category', selectedValue);
        if (searchVal) url.searchParams.set('search', searchVal);
    }
    window.history.replaceState({}, '', url.pathname + url.search);
    updateBreadcrumb();
    applyFilters();
}
        
        // Load provducts
        async function loadProducts() {
            console.log('[SHOP] loadProducts called, currentPage:', currentPage);
            try {
                const params = new URLSearchParams(window.location.search);
                params.set('page', currentPage);
                
                // Get selected category
                const categorySelect = document.getElementById('category-select');
                if (categorySelect && categorySelect.value) {
                    params.set('category', categorySelect.value);
                }
                
                // Get treatment filter
                const treatmentSelect = document.getElementById('treatment-select');
                if (treatmentSelect && treatmentSelect.value) {
                    params.set('treatment', treatmentSelect.value);
                }
                
                // Get price range (slider)
                if (window.priceSlider) {
                    const priceVals = priceSlider.get();
                    params.set('min_price', Math.round(priceVals[0]));
                    params.set('max_price', Math.round(priceVals[1]));
                }
                // Get measurements (sliders)
                if (window.widthSlider && window.widthSlider.target.parentElement.style.display !== 'none') {
                    const widthVals = widthSlider.get();
                    params.set('min_width', Math.round(widthVals[0]));
                    params.set('max_width', Math.round(widthVals[1]));
                }
                if (window.heightSlider && window.heightSlider.target.parentElement.style.display !== 'none') {
                    const heightVals = heightSlider.get();
                    params.set('min_height', Math.round(heightVals[0]));
                    params.set('max_height', Math.round(heightVals[1]));
                }
                
                // Get keywords
                const keywordsInput = document.getElementById('keywords-input');
                if (keywordsInput && keywordsInput.value) {
                    params.set('search', keywordsInput.value);
                }
                
                // Get sort
                const sortBy = document.getElementById('sort-by');
                if (sortBy && sortBy.value) params.set('sort', sortBy.value);
                
                console.log('Fetching products with params:', params.toString());

                const apiPath = '/api/index.php?request=products&' + params.toString();
                const apiUrl = getApiUrl(apiPath);
                console.log('API URL:', apiUrl);
                const data = await apiFetch(apiUrl);
                
                console.log('Products response:', data);
                
                // Parse width/height from product name if needed
                function parseWidthHeight(name) {
                    // Tìm pattern A x B (A là width, B là height)
                    const regex = /([0-9]{3,5})\s*[xX×]\s*([0-9]{3,5})/;
                    const match = name.match(regex);
                    if (match) {
                        return {
                            width: parseInt(match[1], 10),
                            height: parseInt(match[2], 10)
                        };
                    }
                    return null;
                }

                // Lọc sản phẩm theo width/height nếu filter đang bật
                // Default width/height range
                let minWidth = 0, maxWidth = 8000, minHeight = 0, maxHeight = 8000;
                let filterWidth = false, filterHeight = false;
                if (window.widthSlider && window.widthSlider.target.parentElement.style.display !== 'none') {
                    const widthVals = widthSlider.get();
                    minWidth = Math.round(widthVals[0]);
                    maxWidth = Math.round(widthVals[1]);
                    // Only filter if user changed from default
                    filterWidth = !(minWidth === 0 && maxWidth === 8000);
                }
                if (window.heightSlider && window.heightSlider.target.parentElement.style.display !== 'none') {
                    const heightVals = heightSlider.get();
                    minHeight = Math.round(heightVals[0]);
                    maxHeight = Math.round(heightVals[1]);
                    filterHeight = !(minHeight === 0 && maxHeight === 8000);
                }

                // Hiển thị sản phẩm
                const container = document.getElementById('products-grid');
                let products = data.data || [];
                // Chỉ lọc nếu user đã chỉnh filter width/height
                if (filterWidth || filterHeight) {
                    products = products.filter(product => {
                        const dims = parseWidthHeight(product.name);
                        if (!dims) return false;
                        let ok = true;
                        if (filterWidth) {
                            ok = ok && dims.width >= minWidth && dims.width <= maxWidth;
                        }
                        if (filterHeight) {
                            ok = ok && dims.height >= minHeight && dims.height <= maxHeight;
                        }
                        return ok;
                    });
                }
                if (!products.length) {
                    container.innerHTML = '<p class="no-results">No products found matching your criteria.</p>';
                } else {
                    container.innerHTML = products.map(product => {
                        // Handle image with fallback - check for old/invalid paths
                        let imageUrl = product.image;
                        const logoPath = 'assets/images/logo.png';
                        
                        // Use logo for missing, invalid, or old upload paths
                        if (!imageUrl || 
                            imageUrl.trim() === '' || 
                            imageUrl === 'assets/images/logo.png' ||
                            imageUrl.includes('null') ||
                            imageUrl === 'null' ||
                            imageUrl.includes('/demolitiontraders/uploads/') ||  // Old upload path
                            imageUrl.startsWith('/uploads/')) {  // Direct uploads path (likely missing)
                            imageUrl = logoPath;
                        }
                        
                        const newBadge = product.condition_type === 'new' ? '<span class="badge badge-new">NEW</span>' : '';
                        const recycledBadge = product.condition_type === 'recycled' ? '<span class="badge badge-recycled">RECYCLED</span>' : '';
                        const outOfStockBadge = product.stock_quantity === 0 ? '<span class="badge badge-out-of-stock">Out of Stock</span>' : '';
                        
                        // Check if product is in cart
                        const isInCart = cartItems.includes(product.id);
                        let cartButton = '';
                        if (product.stock_quantity > 0) {
                            if (isInCart) {
                                cartButton = '<button class="btn btn-secondary" disabled><i class="fas fa-check"></i> Already in Cart</button>';
                            } else {
                                cartButton = '<button class="btn btn-cart" onclick="addToCart(' + product.id + ')"><i class="fas fa-shopping-cart"></i> Add to Cart</button>';
                            }
                        }
                        
                        // Escape HTML to prevent XSS
                        const escapedName = product.name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                        
                        return '<div class="product-card">' +
                            '<a href="' + BASE_PATH + 'product-detail.php?id=' + product.id + '">' +
                                '<div class="product-image">' +
                                    '<img src="' + imageUrl + '" alt="' + escapedName + '" onerror="this.src=\'assets/images/logo.png\'">' +
                                    newBadge + recycledBadge + outOfStockBadge +
                                '</div>' +
                                '<div class="product-info">' +
                                    '<h3 class="product-name">' + escapedName + '</h3>' +
                                    '<p class="product-price">$' + parseFloat(product.price).toFixed(2) + '</p>' +
                                '</div>' +
                            '</a>' +
                            cartButton +
                        '</div>';
                    }).join('');
                }
                
                // Update results count
                if (data.pagination) {
                    document.getElementById('results-count').textContent = 
                        'Showing ' + data.data.length + ' of ' + data.pagination.total + ' products';
                    
                    // Update pagination
                    updatePagination(data.pagination);
                } else {
                    document.getElementById('results-count').textContent = 
                        'Showing ' + (data.data ? data.data.length : 0) + ' products';
                }
                
            } catch (error) {
                console.error('Error loading products:', error);
                document.getElementById('products-grid').innerHTML = 
                    '<p class="error">Error loading products: ' + error.message + '<br>Please check console for details.</p>';
            }
        }
        
        // Update pagination
        function updatePagination(pagination) {
            const containerBottom = document.getElementById('pagination');
            const containerTop = document.getElementById('pagination-top');

            if (!pagination || pagination.total_pages <= 1) {
                if (containerBottom) containerBottom.innerHTML = '';
                if (containerTop) containerTop.innerHTML = '';
                return;
            }

            let htmlBottom = '';
            let htmlTop = '';

            // Helper to add ... with input trigger
            function getEllipsis(id, scroll) {
                return `<span class="pagination-ellipsis" data-scroll="${scroll}" data-id="${id}" tabindex="0" style="cursor:pointer;user-select:none;">...</span>`;
            }

            // Bottom pagination: scroll (default)
            if (pagination.current_page > 1) {
                htmlBottom += '<button onclick="changePage(' + (pagination.current_page - 1) + ', true)">Previous</button>';
            }
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    htmlBottom += '<button class="active">' + i + '</button>';
                } else if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    htmlBottom += '<button onclick="changePage(' + i + ', true)">' + i + '</button>';
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    htmlBottom += getEllipsis('bottom', true);
                }
            }
            if (pagination.current_page < pagination.total_pages) {
                htmlBottom += '<button onclick="changePage(' + (pagination.current_page + 1) + ', true)">Next</button>';
            }

            // Top pagination: no scroll
            if (pagination.current_page > 1) {
                htmlTop += '<button onclick="changePage(' + (pagination.current_page - 1) + ', false)">Previous</button>';
            }
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    htmlTop += '<button class="active">' + i + '</button>';
                } else if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    htmlTop += '<button onclick="changePage(' + i + ', false)">' + i + '</button>';
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    htmlTop += getEllipsis('top', false);
                }
            }
            if (pagination.current_page < pagination.total_pages) {
                htmlTop += '<button onclick="changePage(' + (pagination.current_page + 1) + ', false)">Next</button>';
            }

            if (containerBottom) containerBottom.innerHTML = htmlBottom;
            if (containerTop) containerTop.innerHTML = htmlTop;

            // Add event for ... to show input
            function addEllipsisInputHandler(container, scroll) {
                if (!container) return;
                container.querySelectorAll('.pagination-ellipsis').forEach(function(el) {
                    el.addEventListener('click', function handler(e) {
                        // Prevent double input
                        if (el.parentElement.querySelector('input[type=number]')) return;
                        const input = document.createElement('input');
                        input.type = 'number';
                        input.min = 1;
                        input.max = pagination.total_pages;
                        input.placeholder = 'Page';
                        input.style.width = '48px';
                        input.style.margin = '0 4px';
                        input.style.fontSize = '1em';
                        input.style.textAlign = 'center';
                        el.replaceWith(input);
                        input.focus();
                        input.addEventListener('keydown', function(ev) {
                            if (ev.key === 'Enter') {
                                let val = parseInt(input.value, 10);
                                if (!isNaN(val) && val >= 1 && val <= pagination.total_pages) {
                                    changePage(val, scroll);
                                } else {
                                    restoreEllipsis();
                                }
                            }
                        });
                        input.addEventListener('blur', function() {
                            let val = parseInt(input.value, 10);
                            if (!isNaN(val) && val >= 1 && val <= pagination.total_pages) {
                                changePage(val, scroll);
                            } else {
                                restoreEllipsis();
                            }
                        });
                        function restoreEllipsis() {
                            const newEllipsis = document.createElement('span');
                            newEllipsis.className = 'pagination-ellipsis';
                            newEllipsis.setAttribute('data-scroll', scroll);
                            newEllipsis.setAttribute('data-id', (input.parentElement && input.parentElement.id === 'pagination-top') ? 'top' : 'bottom');
                            newEllipsis.tabIndex = 0;
                            newEllipsis.style.cursor = 'pointer';
                            newEllipsis.style.userSelect = 'none';
                            newEllipsis.textContent = '...';
                            input.replaceWith(newEllipsis);
                            // Re-attach handler
                            addEllipsisInputHandler(container, scroll);
                        }
                    });
                });
            }
            addEllipsisInputHandler(containerBottom, true);
            addEllipsisInputHandler(containerTop, false);
        }
        
        // Change page
        function changePage(page) {
            var scroll = true;
            if (arguments.length > 1) scroll = arguments[1];
            currentPage = page;
            loadProducts();
            if (scroll) {
                // Scroll to the Shop All Products title
                var pageTitle = document.getElementById('page-title');
                if (pageTitle) {
                    var rect = pageTitle.getBoundingClientRect();
                    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    window.scrollTo({ top: rect.top + scrollTop - 16, behavior: 'smooth' });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }
        
        // Apply filters
        function applyFilters() {
            currentPage = 1;
            loadProducts();
        }
        
        // Add to cart function
        async function addToCart(productId) {
            try {
                const response = await apiFetch(getApiUrl('/api/cart/add.php'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                if (response.success) {
                    // Trigger cart state update
                    localStorage.setItem('cartUpdated', Date.now().toString());
                    document.dispatchEvent(new Event('cartUpdated'));
                    
                    // Show success message
                    alert('Product added to cart!');
                    
                    // Reload cart items and products to update button states
                    await loadCartItems();
                    loadProducts();
                } else {
                    alert(data.message || 'Error adding product to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                alert('Error adding product to cart');
            }
        }
        
        // Tìm kiếm khi nhấn Enter ở ô keywords
        document.addEventListener('DOMContentLoaded', async function() {
            // Restore native fetch if extensions overrode it
            if (window.nativeFetch) {
                window.fetch = window.nativeFetch;
            }
            
            document.getElementById('keywords-input').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyFilters();
                }
            });
                        // Init noUiSlider for price
                        window.priceSlider = noUiSlider.create(document.getElementById('price-slider'), {
                            start: [0, 10000],
                            connect: true,
                            step: 10,
                            range: { min: 0, max: 10000 },
                            tooltips: [false, false],
                            format: {
                                to: v => Math.round(v),
                                from: v => Number(v)
                            }
                        });
                        priceSlider.on('update', function(values) {
                            document.getElementById('price-value').textContent = values[0] + ' - ' + values[1];
                        });
                        priceSlider.on('change', applyFilters);

                        // Init noUiSlider for width
                        window.widthSlider = noUiSlider.create(document.getElementById('width-slider'), {
                            start: [0, 8000],
                            connect: true,
                            step: 10,
                            range: { min: 0, max: 8000 },
                            tooltips: [false, false],
                            format: {
                                to: v => Math.round(v),
                                from: v => Number(v)
                            }
                        });
                        widthSlider.on('update', function(values) {
                            document.getElementById('width-value').textContent = values[0] + ' - ' + values[1] + ' mm';
                        });
                        widthSlider.on('change', applyFilters);

                        // Init noUiSlider for height
                        window.heightSlider = noUiSlider.create(document.getElementById('height-slider'), {
                            start: [0, 8000],
                            connect: true,
                            step: 10,
                            range: { min: 0, max: 8000 },
                            tooltips: [false, false],
                            format: {
                                to: v => Math.round(v),
                                from: v => Number(v)
                            }
                        });
                        heightSlider.on('update', function(values) {
                            document.getElementById('height-value').textContent = values[0] + ' - ' + values[1] + ' mm';
                        });
                        heightSlider.on('change', applyFilters);
            // Add condition filter listeners
            document.querySelectorAll('input[name="condition"]').forEach(input => {
                input.addEventListener('change', applyFilters);
            });
            
            // Load cart items, categories, then products
            await loadCartItems();
            await loadCategories();
            loadProducts();
            
            // Listen for cart updates from other pages
            window.addEventListener('storage', async function(e) {
                if (e.key === 'cartUpdated') {
                    await loadCartItems();
                    loadProducts();
                }
            });
            
            // Check for cart updates when returning to this page
            let lastCheckTime = 0;
            window.addEventListener('focus', async function() {
                const now = Date.now();
                if (now - lastCheckTime < 2000) return;
                lastCheckTime = now;
                
                const lastUpdate = localStorage.getItem('cartUpdated');
                if (lastUpdate && now - parseInt(lastUpdate) < 10000) {
                    await loadCartItems();
                    loadProducts();
                }
            });
        });
    </script>
    
    <!-- Load shop events handler (CSP compliant) -->
    <script src="<?php echo asset('assets/js/shop-events.js'); ?>"></script>
</body>
</html>
