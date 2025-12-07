// Simple address lookup helper using OpenStreetMap Nominatim with manual fallback
// Usage: initAddressLookup({
//   lookupInput: '#billing-address-lookup',
//   trigger: '#billing-address-verify-btn',
//   suggestions: '#billing-address-suggestions',
//   status: '#billing-address-status',
//   fields: { address: 'input[name="billing_address"]', city: 'input[name="billing_city"]', postcode: 'input[name="billing_postcode"]' },
//   manualToggle: '#billing-manual-toggle'
// });
(function() {
    const cache = new Map();

    function debounce(fn, delay = 320) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    function getEl(ref) {
        if (!ref) return null;
        if (typeof ref === 'string') return document.querySelector(ref);
        return ref;
    }

    function pickCity(addr = {}) {
        return addr.city || addr.town || addr.village || addr.suburb || addr.hamlet || addr.state || '';
    }

    function pickSuburb(addr = {}) {
        return addr.suburb || addr.neighbourhood || addr.village || '';
    }

    function pickRegion(addr = {}) {
        return addr.state || addr.state_district || addr.region || addr.county || '';
    }

    function buildStreetLine(addr = {}, display = '') {
        const parts = [addr.house_number, addr.road, pickSuburb(addr)].filter(Boolean);
        if (parts.length) return parts.join(' ');
        return display || '';
    }

    async function fetchAddresses(query) {
        const key = query.toLowerCase();
        if (cache.has(key)) return cache.get(key);

        const buildUrl = () => {
            if (typeof getApiUrl === 'function') {
                return getApiUrl('/api/address/lookup.php?q=' + encodeURIComponent(query));
            }
            return '/api/address/lookup.php?q=' + encodeURIComponent(query);
        };

        const res = await fetch(buildUrl(), {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Lookup failed');
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Lookup failed');
        const data = json.results || [];
        cache.set(key, data);
        return data;
    }

    window.initAddressLookup = function initAddressLookup(opts = {}) {
        const lookupInput = getEl(opts.lookupInput || opts.lookupInputId);
        const trigger = getEl(opts.trigger || opts.triggerId);
        const suggestionsBox = getEl(opts.suggestions || opts.suggestionsId);
        const statusEl = getEl(opts.status || opts.statusId);
        const manualToggle = getEl(opts.manualToggle || opts.manualToggleSelector);
        const fields = {
            address: getEl(opts.fields?.address || opts.addressField),
            city: getEl(opts.fields?.city || opts.cityField),
            postcode: getEl(opts.fields?.postcode || opts.postcodeField),
            region: getEl(opts.fields?.region || opts.regionField)
        };

        if (!lookupInput || !suggestionsBox) return;

        let manualMode = false;

        function setStatus(msg, tone = 'muted') {
            if (!statusEl) return;
            statusEl.textContent = msg;
            statusEl.className = 'address-status ' + tone;
        }

        function clearSuggestions() {
            suggestionsBox.innerHTML = '';
        }

        function applyResult(item) {
            const addr = item.address || {};
            const streetLine = buildStreetLine(addr, item.display_name);
            if (fields.address) fields.address.value = streetLine || item.display_name || '';
            if (fields.city) fields.city.value = pickCity(addr) || fields.city.value;
            if (fields.postcode) fields.postcode.value = addr.postcode || fields.postcode.value;
            if (fields.region) fields.region.value = pickRegion(addr) || fields.region.value;
            setStatus('Address filled from verified result.', 'success');
            [fields.address, fields.city, fields.postcode, fields.region]
                .filter(Boolean)
                .forEach(el => {
                    const evt = new Event('input', { bubbles: true });
                    el.dispatchEvent(evt);
                });
            clearSuggestions();
        }

        function renderSuggestions(list) {
            clearSuggestions();
            if (!list || !list.length) {
                setStatus('No matches found. You can enter it manually.', 'warning');
                return;
            }
            const frag = document.createDocumentFragment();
            list.forEach(item => {
                const addr = item.address || {};
                const streetLine = buildStreetLine(addr, item.display_name);
                const city = pickCity(addr);
                const region = pickRegion(addr);
                const postcode = addr.postcode || '';
                const displayLine = [streetLine, city, region].filter(Boolean).join(', ');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'address-suggestion';
                btn.innerHTML = `
                    <span class="address-main">${displayLine || item.display_name}</span>
                    <span class="address-meta">${postcode || ''}</span>
                `;
                btn.addEventListener('click', () => applyResult(item));
                frag.appendChild(btn);
            });
            suggestionsBox.appendChild(frag);
            setStatus('Pick a suggestion or continue typing manually.', 'info');
        }

        async function runLookup() {
            if (manualMode) return;
            const q = lookupInput.value.trim();
            if (q.length < 4) {
                clearSuggestions();
                setStatus('Type at least 4 characters to search.', 'muted');
                return;
            }
            try {
                setStatus('Searching address...', 'info');
                const results = await fetchAddresses(q);
                renderSuggestions(results);
            } catch (err) {
                console.error('Address lookup error:', err);
                setStatus('Lookup failed. You can enter the address manually.', 'error');
            }
        }

        const debouncedLookup = debounce(runLookup, opts.debounce || 350);
        lookupInput.addEventListener('input', debouncedLookup);
        if (trigger) trigger.addEventListener('click', runLookup);

        if (manualToggle) {
            manualToggle.style.display = 'none';
        }
    };
})();
