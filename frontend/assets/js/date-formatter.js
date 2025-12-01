/**
 * Format date to dd/mm/yy or dd MMM yyyy
 */
window.formatDate = function(dateStr, format = 'short') {
    if (!dateStr) return '';
    
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const shortYear = String(year).slice(-2);
    
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthName = monthNames[date.getMonth()];
    
    if (format === 'long') {
        return `${day} ${monthName} ${year}`;
    }
    
    return `${day}/${month}/${shortYear}`;
};

window.formatDateTime = function(dateStr, format = 'short') {
    if (!dateStr) return '';
    
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    
    const dateFormatted = formatDate(dateStr, format);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${dateFormatted} ${hours}:${minutes}`;
};
