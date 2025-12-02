import os
import re

# Fix template literals with backticks - more careful approach

files_to_fix = [
    r'C:\xampp\htdocs\demolitiontraders\frontend\admin\orders.php',
    r'C:\xampp\htdocs\demolitiontraders\frontend\admin\products.php',
    r'C:\xampp\htdocs\demolitiontraders\frontend\admin\categories.php',
    r'C:\xampp\htdocs\demolitiontraders\frontend\admin\customers.php',
    r'C:\xampp\htdocs\demolitiontraders\frontend\shop.php'
]

replacements = 0
files_changed = []

for filepath in files_to_fix:
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Pattern for template literals: `...`
    # Replace `/demolitiontraders/backend/api/...` with `${getApiUrl('/api/...')}`
    pattern = r'`([^`]*)/demolitiontraders/backend/api/([^`]*)`'
    
    def replace_template_literal(match):
        before = match.group(1)
        api_path = match.group(2)
        return f'`{before}${{getApiUrl(\'/api/{api_path}\')}}`'
    
    content = re.sub(pattern, replace_template_literal, content)
    
    if content != original_content:
        count = content.count('${getApiUrl') - original_content.count('${getApiUrl')
        if count > 0:
            replacements += count
            files_changed.append(os.path.basename(filepath))
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)

print(f"Fixed {replacements} template literal paths in {len(files_changed)} files")
print(f"Files: {', '.join(files_changed)}")
