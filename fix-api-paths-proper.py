import os
import re

# Fix JavaScript in all frontend PHP files
# Don't touch PHP code (avoid define() statements)

frontend_dir = r'C:\xampp\htdocs\demolitiontraders\frontend'

replacements = 0
files_changed = []

for root, dirs, files in os.walk(frontend_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            
            # Pattern 1: fetch with single/double quotes
            pattern1 = r"fetch\(\s*['\"]\/demolitiontraders\/backend(\/[^'\"]*)['\"]"
            def replace1(match):
                return f"fetch(getApiUrl('{match.group(1)}')"
            content = re.sub(pattern1, replace1, content)
            
            # Pattern 2: const url = '...'
            pattern2 = r"(const|let|var)\s+url\s*=\s*['\"]\/demolitiontraders\/backend(\/[^'\"]*)['\"]"
            def replace2(match):
                return f"{match.group(1)} url = getApiUrl('{match.group(2)}')"
            content = re.sub(pattern2, replace2, content)
            
            # Pattern 3: console.log with path
            pattern3 = r"console\.log\([^,)]+,\s*['\"]\/demolitiontraders\/backend(\/[^'\"]*)['\"]"
            def replace3(match):
                return match.group(0).replace('/demolitiontraders/backend' + match.group(1), 
                                               "' + getApiUrl('" + match.group(1) + "') + '")
            content = re.sub(pattern3, replace3, content)
            
            # Count changes
            if content != original_content:
                file_replacements = len(re.findall(r'getApiUrl\(', content)) - len(re.findall(r'getApiUrl\(', original_content))
                if file_replacements > 0:
                    replacements += file_replacements
                    files_changed.append(os.path.basename(file))
                    
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(content)

print(f"Fixed {replacements} API paths in {len(files_changed)} files")
print(f"Files: {', '.join(files_changed)}")
