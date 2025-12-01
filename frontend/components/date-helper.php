<?php
/**
 * Format date to dd/mm/yy format
 */
function formatDate($dateStr, $format = 'short') {
    if (empty($dateStr)) return '';
    
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) return $dateStr;
    
    if ($format === 'long') {
        // dd MMM yyyy format (e.g., 28 Nov 2025)
        return date('d M Y', $timestamp);
    }
    
    // dd/mm/yy format (e.g., 28/11/25)
    return date('d/m/y', $timestamp);
}

/**
 * Format date with time
 */
function formatDateTime($dateStr, $format = 'short') {
    if (empty($dateStr)) return '';
    
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) return $dateStr;
    
    $dateFormatted = formatDate($dateStr, $format);
    return $dateFormatted . ' ' . date('H:i', $timestamp);
}
?>
