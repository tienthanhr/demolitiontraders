/**
 * API Helper với error handling và retry logic
 * Xử lý tất cả các API calls trong ứng dụng
 */

(function() {
    'use strict';

    // Preserve native fetch
    window.nativeFetch = window.nativeFetch || window.fetch;

    /**
     * Lấy base URL cho API
     */
    window.getApiUrl = function(path) {
        const base = document.querySelector('base');
        if (base) {
            const baseUrl = base.href.replace(/\/frontend\/?$/, '');
            return baseUrl + '/backend' + path;
        }
        
        // Fallback: detect if localhost or production
        const isLocalhost = window.location.hostname === 'localhost' || 
                          window.location.hostname === '127.0.0.1';
        
        if (isLocalhost) {
            return '/demolitiontraders/backend' + path;
        } else {
            // Production
            return '/backend' + path;
        }
    };

    /**
     * Enhanced fetch với better error handling
     * @param {string} url - URL to fetch
     * @param {object} options - Fetch options
     * @param {number} retries - Number of retries (default: 1)
     */
    window.apiFetch = async function(url, options = {}, retries = 1) {
        // Ensure headers exist
        options.headers = options.headers || {};
        
        // Add ngrok skip header
        options.headers['ngrok-skip-browser-warning'] = 'true';
        
        // Add credentials for CORS - use 'include' to always send cookies
        options.credentials = options.credentials || 'include';
        
        // Add Content-Type if not set and has body
        if (options.body && !options.headers['Content-Type']) {
            if (typeof options.body === 'string') {
                try {
                    JSON.parse(options.body);
                    options.headers['Content-Type'] = 'application/json';
                } catch (e) {
                    // Not JSON, might be FormData
                }
            }
        }

        let lastError;
        
        for (let i = 0; i <= retries; i++) {
            try {
                console.log(`[API] Fetching: ${url}${i > 0 ? ` (retry ${i})` : ''}`);
                
                const response = await window.nativeFetch(url, options);
                
                // Check if response is ok
                if (!response.ok) {
                    const errorText = await response.text();
                    let errorData;
                    
                    try {
                        errorData = JSON.parse(errorText);
                    } catch (e) {
                        errorData = { message: errorText || `HTTP ${response.status}` };
                    }
                    
                    throw {
                        status: response.status,
                        message: errorData.message || `HTTP Error ${response.status}`,
                        data: errorData
                    };
                }
                
                // Parse JSON response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    console.log(`[API] Success:`, data);
                    return data;
                } else {
                    const text = await response.text();
                    console.log(`[API] Success (text):`, text.substring(0, 100));
                    return text;
                }
                
            } catch (error) {
                lastError = error;
                console.error(`[API] Error (attempt ${i + 1}/${retries + 1}):`, error);
                console.error(`[API] Error details - Status:`, error.status, 'Message:', error.message, 'Data:', error.data);
                
                // Don't retry on client errors (4xx)
                if (error.status && error.status >= 400 && error.status < 500) {
                    break;
                }
                
                // Wait before retry
                if (i < retries) {
                    await new Promise(resolve => setTimeout(resolve, 500 * (i + 1)));
                }
            }
        }
        
        // All retries failed
        throw lastError || new Error('Failed to fetch');
    };

    /**
     * GET request helper
     */
    window.apiGet = function(endpoint, params = {}) {
        const url = new URL(window.getApiUrl(endpoint), window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        return window.apiFetch(url.toString());
    };

    /**
     * POST request helper
     */
    window.apiPost = function(endpoint, data = {}) {
        return window.apiFetch(window.getApiUrl(endpoint), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
    };

    /**
     * PUT request helper
     */
    window.apiPut = function(endpoint, data = {}) {
        return window.apiFetch(window.getApiUrl(endpoint), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
    };

    /**
     * DELETE request helper
     */
    window.apiDelete = function(endpoint) {
        return window.apiFetch(window.getApiUrl(endpoint), {
            method: 'DELETE'
        });
    };

    /**
     * Check if API is available
     */
    window.checkApiHealth = async function() {
        try {
            const response = await window.apiFetch(window.getApiUrl('/api/index.php?request=health'), {}, 0);
            console.log('[API] Health check passed');
            return true;
        } catch (error) {
            console.warn('[API] Health check failed:', error);
            return false;
        }
    };

    console.log('[API Helper] Loaded successfully');
})();
