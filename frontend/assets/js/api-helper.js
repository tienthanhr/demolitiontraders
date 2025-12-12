/**
 * API Helper with robust error handling and CSRF support
 */
(function() {
    'use strict';

    // Safe JSON parse helper
    function safeJsonParse(text) {
        try { return JSON.parse(text); } catch (e) { return null; }
    }

    // Preserve native fetch
    window.nativeFetch = window.nativeFetch || window.fetch;

    /**
     * Get API URL based on <base> tag or environment
     */
    window.getApiUrl = function(path) {
        const base = document.querySelector('base');
        if (base && /^https?:\/\//i.test(base.href)) {
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
     * Enhanced fetch with better error handling
     * @param {string} url - URL to fetch
     * @param {object} options - Fetch options
     * @param {number} retries - Number of retries (default: 1)
     */
    window.apiFetch = async function(url, options = {}, retries = 1) {
        // Ensure headers exist
        options.headers = options.headers || {};

        // Attach CSRF token for admin-protected endpoints if available
        if (!options.headers['X-CSRF-Token']) {
            const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                options.headers['X-CSRF-Token'] = csrfToken;
            }
        }
        
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
                    // If a 401 occurs while on admin pages, redirect to admin login
                    if (response.status === 401 && window.location.pathname && window.location.pathname.startsWith('/admin')) {
                        console.warn('[API] 401 Unauthorized on admin page - redirecting to admin-login');
                        window.location.href = '/admin-login';
                        return; // Give browser a moment to redirect
                    }
                    const errorText = await response.text();
                    const errorData = safeJsonParse(errorText) || { message: errorText || `HTTP ${response.status}` };
                    
                    throw {
                        status: response.status,
                        message: errorData.message || `HTTP Error ${response.status}`,
                        data: errorData
                    };
                }
                
                // Parse JSON response (tolerant)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const text = await response.text();
                    const data = safeJsonParse(text);
                    if (data !== null) {
                        console.log(`[API] Success:`, data);
                        return data;
                    }
                    console.warn('[API] Response declared JSON but could not parse. Returning raw text.');
                    return { success: false, message: 'Invalid JSON response', raw: text, status: response.status };
                }
                
                const text = await response.text();
                console.log(`[API] Success (text):`, text.substring(0, 100));
                return text;
                
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
        
        throw lastError || new Error('Unknown API error');
    };

    // Convenience GET wrapper
    window.apiGet = function(path, retries = 1) {
        return window.apiFetch(getApiUrl(path), { method: 'GET' }, retries);
    };

})();
