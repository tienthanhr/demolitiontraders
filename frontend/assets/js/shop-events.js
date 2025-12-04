/**
 * Shop Filter Events Handler
 * CSP Compliant - Removes all inline onchange and onclick handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Category select change handler
    const categorySelect = document.getElementById('category-select');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            handleCategoryChange();
        });
    }

    // Treatment select change handler
    const treatmentSelect = document.getElementById('treatment-select');
    if (treatmentSelect) {
        treatmentSelect.addEventListener('change', function() {
            applyFilters();
        });
    }

    // Thickness select change handler
    const thicknessSelect = document.getElementById('thickness-select');
    if (thicknessSelect) {
        thicknessSelect.addEventListener('change', function() {
            applyFilters();
        });
    }

    // Keywords input enter key handler
    const keywordsInput = document.getElementById('keywords-input');
    if (keywordsInput) {
        keywordsInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }

    // Search button handler
    const searchBtn = document.getElementById('search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }

    // Pagination handlers
    initializePaginationHandlers();
});

/**
 * Initialize pagination event listeners
 */
function initializePaginationHandlers() {
    const topPagination = document.getElementById('pagination-top');
    const bottomPagination = document.getElementById('pagination-bottom');

    [topPagination, bottomPagination].forEach(container => {
        if (container) {
            setupPaginationListeners(container);
        }
    });
}

/**
 * Setup pagination click listeners
 */
function setupPaginationListeners(container) {
    if (!container) return;

    const links = container.querySelectorAll('a, span');
    links.forEach(link => {
        if (link.getAttribute('data-scroll') !== null) {
            link.style.cursor = 'pointer';
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                const scroll = link.getAttribute('data-scroll') === 'true';
                if (page) {
                    changePage(parseInt(page), scroll);
                }
            });
        }
    });
}

/**
 * Sorttable headers click handler
 */
function initSortTableHandlers() {
    const sortableHeaders = document.querySelectorAll('th[data-sortable="true"]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const sortKey = header.getAttribute('data-sort-key');
            if (sortKey) {
                sortTable(sortKey);
            }
        });
    });
}
