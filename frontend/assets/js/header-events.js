/**
 * Header Events & Dynamic Navigation
 * Handles category loading and header interactions
 */

// Detect base path (without /frontend/) for page links
function getBasePath() {
    // First check if getBaseUrl is defined (from api-helper.js)
    if (typeof window.getBaseUrl === 'function') {
        return window.getBaseUrl();
    }
    
    // Get from <base> tag and remove /frontend/ suffix
    const base = document.querySelector('base');
    if (base && base.href) {
        // base.href is like "/demolitiontraders/frontend/" or just "/frontend/"
        // We need "/demolitiontraders/" or "/" for page URLs
        let href = base.getAttribute('href') || base.href;
        // Remove protocol and host if present
        if (href.startsWith('http')) {
            try {
                const url = new URL(href);
                href = url.pathname;
            } catch (e) {}
        }
        // Remove /frontend/ or frontend/ from the end
        href = href.replace(/frontend\/?$/, '');
        // Ensure trailing slash
        if (!href.endsWith('/')) href += '/';
        return href;
    }
    
    // Fallback
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    return isLocalhost ? '/demolitiontraders/' : '/';
}

// Load categories as soon as DOM is ready so header reflects admin changes immediately
document.addEventListener('DOMContentLoaded', function() {
    loadHeaderCategories();
});

/**
 * Load categories from API and build navigation menu
 */
async function loadHeaderCategories() {
    const navList = document.getElementById('nav-menu-list');
    const mobileNavContent = document.getElementById('mobile-nav-content');
    
    if (!navList) return;

    try {
        const categories = await fetchCategories();

        if (Array.isArray(categories)) {
            const categoryTree = buildCategoryTree(categories);
            renderDesktopMenu(categoryTree, navList);
            if (mobileNavContent) {
                renderMobileMenu(categoryTree, mobileNavContent);
            }
        } else {
            console.error('Invalid category data format:', categories);
            navList.innerHTML = '<li class="text-white">Error loading menu</li>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        navList.innerHTML = '<li class="text-white">Menu unavailable</li>';
    }
}

// Try multiple endpoints to stay resilient to routing differences
async function fetchCategories() {
    const fetchFunc = window.apiFetch || window.fetch;
    const base = window.getApiUrl ? window.getApiUrl('') : '/demolitiontraders/backend';

    const endpoints = [
        base.replace(/\/$/, '') + '/api/index.php?request=categories',
        base.replace(/\/$/, '') + '/api/categories'
    ];

    let lastError;
    for (const url of endpoints) {
        try {
            let data = await fetchFunc(url);
            if (data && typeof data.json === 'function') {
                data = await data.json();
            }
            const categories = Array.isArray(data?.data) ? data.data : data;
            if (Array.isArray(categories)) {
                const filtered = categories.filter(cat => String(cat.is_active ?? '1') !== '0');
                console.log('[HEADER] Loaded categories', { count: filtered.length, url, sample: filtered.slice(0, 5) });
                return filtered;
            }
        } catch (err) {
            lastError = err;
        }
    }
    throw lastError || new Error('Unable to load categories');
}

const allowedMainNames = [
    "Plywood", "Doors", "Windows", "Sliding Doors", "Timber", 
    "Cladding", "Landscaping", "Roofing", "Kitchens", 
    "Bathroom & Laundry", "General"
];

/**
 * Build hierarchical tree from flat category list
 */
function buildCategoryTree(categories) {
    // Filter only allowed main categories
    let mainCategories = categories.filter(cat => {
        const isRoot = !cat.parent_id || cat.parent_id == 0 || cat.parent_id === null;
        const isActive = cat.is_active === undefined || cat.is_active === null || String(cat.is_active) === '1';
        const isAllowed = allowedMainNames.some(name => name.toLowerCase() === (cat.name || '').toLowerCase());
        return isRoot && isActive && isAllowed;
    });
    // Sort by display_order
    mainCategories.sort((a, b) => {
        const orderA = Number(a.display_order) || 0;
        const orderB = Number(b.display_order) || 0;
        if (orderA !== orderB) return orderA - orderB;
        return (a.name || '').localeCompare(b.name || '');
    });
    // Build tree structure (no children for header)
    return mainCategories.map(cat => ({ ...cat, children: [] }));
}

/**
 * Render Desktop Navigation Menu
 */
function renderDesktopMenu(tree, container) {
    // Get base path for links from <base> tag
    const basePath = getBasePath();
    
    const buildList = (items) => {
        return items.map(item => {
            const url = `${basePath}shop.php?category=${item.id}`;
            const hasChildren = item.children && item.children.length > 0;
            if (hasChildren) {
                return `
                    <li class="has-dropdown">
                        <a href="${url}" class="nav-parent nav-clickable">${escapeHtml(item.name)}</a>
                        <ul class="dropdown">${buildList(item.children)}</ul>
                    </li>
                `;
            }
            return `<li><a href="${url}" class="nav-clickable">${escapeHtml(item.name)}</a></li>`;
        }).join('');
    };

    container.innerHTML = buildList(tree);
}

/**
 * Render Mobile Navigation Menu
 */
function renderMobileMenu(tree, container) {
    // Get base path for links from <base> tag
    const basePath = getBasePath();
    
    const buildList = (items) => {
        return items.map(item => {
            const url = `${basePath}shop.php?category=${item.id}`;
            const hasChildren = item.children && item.children.length > 0;
            if (hasChildren) {
                return `
                    <li class="mobile-nav-item has-dropdown">
                        <a href="${url}" class="mobile-nav-link">${escapeHtml(item.name)}</a>
                        <ul class="mobile-nav-submenu">${buildList(item.children)}</ul>
                    </li>
                `;
            }
            return `
                <li class="mobile-nav-item">
                    <a href="${url}" class="mobile-nav-link">${escapeHtml(item.name)}</a>
                </li>
            `;
        }).join('');
    };

    container.innerHTML = `<ul class="mobile-nav-list">${buildList(tree)}</ul>`;
}

/**
 * Helper to escape HTML special characters
 */
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
