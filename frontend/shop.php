<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1 id="page-title">Shop All Products</h1>
            <nav class="breadcrumb" id="breadcrumb">
                <a href="index.php">Home</a> / <span>Shop</span>
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
                        <select id="category-select" name="category" class="filter-select" onchange="handleCategoryChange()" autocomplete="off">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="filter-group" id="size-group" style="display: none; min-width:120px;">
                        <label for="size-select">Size</label>
                        <select id="size-select" name="size" class="filter-select" autocomplete="off">
                            <option value="">All</option>
                        </select>
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
                    <div class="filter-group" style="min-width:260px; margin-bottom: 18px; display:flex; flex-direction:column; justify-content:flex-end;">
                        <label for="price-slider" style="margin-bottom:4px;">Price Range ($NZD)</label>
                        <div style="display:flex; flex-direction:column; justify-content:flex-end;">
                            <div id="price-slider"></div>
                            <div style="text-align:center; margin-top:8px;">
                                <span id="price-value">0 - 10,000</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-group" style="flex:1; min-width:220px; margin-bottom: 18px; display:flex; flex-direction:column; justify-content:flex-end;">
                        <label for="keywords-input">Keywords</label>
                        <input type="text" id="keywords-input" name="keywords" class="filter-input" placeholder="Search products..." autocomplete="off" style="width:100%; height:44px; font-size:1.1em; padding: 0 16px;">
                    </div>
                    <div class="filter-group" style="display:flex; flex-direction:column; justify-content:flex-end; min-width:160px; margin-bottom: 18px;">
                        <label for="search-btn">&nbsp;</label>
                        <button id="search-btn" name="search-btn" class="btn btn-primary" onclick="applyFilters()" style="width: 100%; height:44px; font-size:1.1em; align-self:flex-end;">SEARCH</button>
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
            
            <!-- Products Grid -->
            <div class="products-grid" id="products-grid">
                <!-- Products loaded via JavaScript -->
            </div>
            
            <div class="pagination" id="pagination"></div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <!-- Chỉ giữ 1 dòng CDN noUiSlider, đặt trước main.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
    <script src="assets/js/main.js"></script>
    <style>
    /* Ẩn tooltip số trên hai đầu slider price */
    #price-slider .noUi-tooltip {
        display: none !important;
    }
    </style>
    <script>
        let currentPage = 1;
        
        // Update breadcrumb based on selected category
        function updateBreadcrumb() {
            const categorySelect = document.getElementById('category-select');
            const breadcrumb = document.getElementById('breadcrumb');
            const pageTitle = document.getElementById('page-title');
            
            if (!categorySelect || categorySelect.value === '') {
                breadcrumb.innerHTML = '<a href="index.php">Home</a> / <span>Shop</span>';
                if (pageTitle) pageTitle.textContent = 'Shop All Products';
            } else {
                const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
                breadcrumb.innerHTML = '<a href="index.php">Home</a> / <a href="shop.php">Shop</a> / <span>' + categoryName + '</span>';
                if (pageTitle) pageTitle.textContent = categoryName;
            }
        }
        
        // Load categories
        async function loadCategories() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/index.php?request=categories');
                const data = await response.json();
                
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
            document.getElementById('size-group').style.display = 'none';
            document.getElementById('measurements-group').style.display = 'none';
            // Hide sliders
            if (window.widthSlider) widthSlider.target.parentElement.style.display = 'none';
            if (window.heightSlider) heightSlider.target.parentElement.style.display = 'none';
            // Show relevant filters based on category
            if (selectedText.includes('plywood') || selectedText.includes('timber') || selectedText.includes('wood')) {
                document.getElementById('treatment-group').style.display = 'block';
                document.getElementById('thickness-group').style.display = 'block';
            } else if (
                selectedText.includes('door') ||
                selectedText.includes('window') ||
                selectedText.includes('sliding door')
            ) {
                document.getElementById('size-group').style.display = 'block';
                document.getElementById('measurements-group').style.display = 'block';
                if (window.widthSlider) widthSlider.target.parentElement.style.display = 'block';
                if (window.heightSlider) heightSlider.target.parentElement.style.display = 'block';
            }
            updateBreadcrumb();
            applyFilters();
        }
        
        // Load provducts
        async function loadProducts() {
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
                
                const apiUrl = '/demolitiontraders/backend/api/index.php?request=products&' + params.toString();
                console.log('API URL:', apiUrl);
                
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                
                const data = await response.json();
                
                console.log('Products response:', data);
                
                // Display products
                const container = document.getElementById('products-grid');
                if (!data.data || data.data.length === 0) {
                    container.innerHTML = '<p class="no-results">No products found matching your criteria.</p>';
                } else {
                    container.innerHTML = data.data.map(product => {
                        const imageUrl = product.image || 'assets/images/no-image.jpg';
                        const newBadge = product.condition_type === 'new' ? '<span class="badge badge-new">NEW</span>' : '';
                        const outOfStockBadge = product.stock_quantity === 0 ? '<span class="badge badge-out-of-stock">Out of Stock</span>' : '';
                        const cartButton = product.stock_quantity > 0 ? 
                            '<button class="btn btn-cart" onclick="addToCart(' + product.id + ')"><i class="fas fa-shopping-cart"></i> Add to Cart</button>' : '';
                        
                        return '<div class="product-card">' +
                            '<a href="product-detail.php?id=' + product.id + '">' +
                                '<div class="product-image">' +
                                    '<img src="' + imageUrl + '" alt="' + product.name + '">' +
                                    newBadge + outOfStockBadge +
                                '</div>' +
                                '<div class="product-info">' +
                                    '<h3 class="product-name">' + product.name + '</h3>' +
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
            const container = document.getElementById('pagination');
            
            if (!pagination || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            
            if (pagination.current_page > 1) {
                html += '<button onclick="changePage(' + (pagination.current_page - 1) + ')">Previous</button>';
            }
            
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += '<button class="active">' + i + '</button>';
                } else if (i === 1 || i === pagination.total_pages || 
                          (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    html += '<button onclick="changePage(' + i + ')">' + i + '</button>';
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    html += '<span>...</span>';
                }
            }
            
            if (pagination.current_page < pagination.total_pages) {
                html += '<button onclick="changePage(' + (pagination.current_page + 1) + ')">Next</button>';
            }
            
            container.innerHTML = html;
        }
        
        // Change page
        function changePage(page) {
            currentPage = page;
            loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Apply filters
        function applyFilters() {
            currentPage = 1;
            loadProducts();
        }
        
        // Tìm kiếm khi nhấn Enter ở ô keywords
        document.addEventListener('DOMContentLoaded', async function() {
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
                            start: [300, 3000],
                            connect: true,
                            step: 10,
                            range: { min: 300, max: 3000 },
                            tooltips: [true, true],
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
                            start: [300, 3000],
                            connect: true,
                            step: 10,
                            range: { min: 300, max: 3000 },
                            tooltips: [true, true],
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
            
            // Load categories first, then products (so URL params work)
            await loadCategories();
            loadProducts();
        });
    </script>
</body>
</html>
