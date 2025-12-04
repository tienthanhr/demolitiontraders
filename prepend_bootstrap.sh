#!/bin/bash

# This script standardizes the header of all admin API files.

ADMIN_API_DIR="backend/api/admin"
TEMPLATE_FILE="$ADMIN_API_DIR/promote-to-admin.php"

# Get the template header content, up to the line before 'try {'
TEMPLATE_HEADER=$(sed '/try {/Q' "$TEMPLATE_FILE")

# Find all PHP files in the admin directory, excluding the middleware and the template itself.
find "$ADMIN_API_DIR" -type f -name "*.php" ! -name "csrf_middleware.php" ! -name "promote-to-admin.php" | while read FILE; do
    echo "Processing $FILE..."

    # Get the file's specific description block.
    DESCRIPTION=$(awk '/\/\*\*/,/\*\//' "$FILE")

    # Get the content of the file from the 'try {' block onwards.
    CONTENT_AFTER_HEADER=$(awk 'f;/try {/{f=1}' "$FILE")

    # Replace the template's description with the file's specific description.
    NEW_HEADER=$(echo "$TEMPLATE_HEADER" | sed "s#\(\/\*\*\).*\(Promote User to Admin API\).*\( \*\/\)#$DESCRIPTION#")

    # Write the new standardized content back to the file.
    echo "$NEW_HEADER" > "$FILE"
    echo "$CONTENT_AFTER_HEADER" >> "$FILE"
done

echo "Admin API header standardization script finished."
