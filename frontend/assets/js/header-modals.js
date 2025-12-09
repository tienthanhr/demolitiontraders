// Header Modals Handler
// Handles opening hours and location modals

// Fetch opening hours from Google Places API
let weeklyHours = [];

async function fetchOpeningHours() {
    try {
        const data = await apiFetch(getApiUrl('/api/opening-hours.php'));

        if (data.success && data.today_hours) {
            const displayElement = document.getElementById('opening-hours-display');

            // Store weekly hours for dropdown
            weeklyHours = data.weekday_text || [];

            // Extract just the hours part (remove day name)
            const hoursOnly = data.today_hours.replace(/^[A-Za-z]+:\s*/, '');

            // Add open/closed indicator
            const statusIcon = data.open_now
                ? '<span class="text-status-green mr-5">●</span>'
                : '<span class="text-status-red mr-5">●</span>';

            const statusText = data.open_now ? 'Open Now' : 'Closed';

            displayElement.innerHTML = statusIcon + statusText + ' - ' + hoursOnly;

            // Populate dropdown list
            populateHoursList(data.weekday_text, data.open_now);
        }
    } catch (error) {
        console.error('Error fetching opening hours:', error);
        // Keep default hours on error
    }
}

function populateHoursList(weekdayText, isOpenNow) {
    const listElement = document.getElementById('opening-hours-list');

    if (!weekdayText || weekdayText.length === 0) {
        listElement.innerHTML = '<div class="hours-item">Hours not available</div>';
        return;
    }

    const today = new Date().getDay(); // 0 (Sunday) to 6 (Saturday)
    const adjustedToday = (today + 6) % 7; // Convert to Monday-first (0=Monday, 6=Sunday)

    listElement.innerHTML = weekdayText.map((hours, index) => {
        const isToday = index === adjustedToday;
        const dayClass = isToday ? 'hours-item today' : 'hours-item';
        const parts = hours.split(': ');
        const day = parts[0];
        const time = parts[1] || 'Closed';

        return '<div class="' + dayClass + '">' +
            '<span class="day-name">' + day + '</span>' +
            '<span class="day-hours">' + time + '</span>' +
            '</div>';
    }).join('');
}

// Toggle opening hours dropdown
document.getElementById('opening-hours-box')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const modal = document.getElementById('opening-hours-modal');
    const icon = document.getElementById('hours-dropdown-icon');

    modal.classList.add('active');
    icon.style.transform = 'rotate(180deg)';
    document.body.style.overflow = 'hidden';
});

// Location button click
document.getElementById('location-btn')?.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const modal = document.getElementById('location-modal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
});

// Close location modal
function closeLocationModal() {
    const modal = document.getElementById('location-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('close-location-modal')?.addEventListener('click', closeLocationModal);
document.getElementById('modal-backdrop-location')?.addEventListener('click', closeLocationModal);

// Close modal functions
function closeHoursModal() {
    const modal = document.getElementById('opening-hours-modal');
    const icon = document.getElementById('hours-dropdown-icon');

    modal.classList.remove('active');
    icon.style.transform = 'rotate(0deg)';
    document.body.style.overflow = '';
}

// Close button
document.getElementById('close-hours-modal')?.addEventListener('click', closeHoursModal);

// Backdrop click
document.getElementById('modal-backdrop')?.addEventListener('click', closeHoursModal);

// ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHoursModal();
        closeLocationModal();
    }
});

// Initialize
fetchOpeningHours();