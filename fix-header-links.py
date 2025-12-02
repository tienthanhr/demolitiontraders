import re

# Read header
with open('frontend/components/header.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace all shop.php links to include userUrl
# Pattern: href="shop.php?category=xxx"
content = re.sub(
    r'href="shop\.php\?category=([^"]+)"',
    r'href="<?php echo userUrl(\'shop.php?category=\1\'); ?>"',
    content
)

# Write back
with open('frontend/components/header.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ Updated all shop.php links in header.php")
