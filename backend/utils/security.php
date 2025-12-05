<?php
/**
 * Security Utility Functions
 */

/**
 * Recursively sanitizes data for outputting to HTML.
 * It applies htmlspecialchars to all string values within an array or object.
 *
 * @param mixed $data The input data (string, array, or object) to be escaped.
 * @return mixed The sanitized data, safe for HTML output.
 */
function escape_output($data) {
    // If it's a string, escape it.
    if (is_string($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // If it's an array, recursively call this function for each element.
    if (is_array($data)) {
        $escaped = [];
        foreach ($data as $key => $value) {
            $escaped[$key] = escape_output($value);
        }
        return $escaped;
    }

    // If it's an object, recursively call this function for each property.
    if (is_object($data)) {
        // Clone the object to avoid modifying the original
        $escaped = clone $data;
        foreach (get_object_vars($data) as $key => $value) {
            $escaped->{$key} = escape_output($value);
        }
        return $escaped;
    }

    // Return the data as is if it's not a string, array, or object (e.g., int, bool).
    return $data;
}

/**
 * Sends a consistent, secure JSON response.
 *
 * This function handles setting the correct content type, escaping all output data
 * to prevent XSS, encoding the data to JSON, and terminating the script.
 *
 * @param mixed $data The data to be sent in the response.
 * @param int $statusCode The HTTP status code to send (default: 200).
 */
function send_json_response($data, $statusCode = 200) {
    // Do not send further headers if they have already been sent.
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
    }

    // Sanitize all output data before encoding
    $sanitizedData = escape_output($data);

    echo json_encode($sanitizedData);
    exit;
}
