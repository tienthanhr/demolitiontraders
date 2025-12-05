#!/usr/bin/env python3
"""
Simple line-by-line MySQL to PostgreSQL converter
"""
import re

def convert(input_file, output_file):
    with open(input_file, 'r', encoding='latin-1', errors='ignore') as f:
        lines = f.readlines()
    
    output = []
    skip_until_semicolon = False
    current_table = None
    
    for line_num, line in enumerate(lines):
        original_line = line
        line = line.rstrip()
        
        # Skip lock statements
        if 'LOCK TABLES' in line or 'UNLOCK TABLES' in line:
            skip_until_semicolon = True
            continue
        
        if skip_until_semicolon:
            if ';' in line:
                skip_until_semicolon = False
            continue
        
        # Skip MySQL-specific comments
        if line.strip().startswith('/*!') or line.strip().startswith('SET ') or line.strip().startswith('USE '):
            continue
        
        # Skip empty lines and normal comments
        if not line.strip() or line.strip().startswith('--'):
            continue
        
        # Process CREATE TABLE
        if 'CREATE TABLE' in line:
            current_table = True
            # Replace backticks with quotes
            line = line.replace('`', '"')
            output.append(line)
            continue
        
        # Process table definition lines
        if current_table:
            line = line.replace('`', '"')
            
            # Convert data types
            line = convert_data_types(line)
            
            # Remove AUTO_INCREMENT from int type
            if 'AUTO_INCREMENT' in line:
                # Convert int(xx) NOT NULL PRIMARY KEY AUTO_INCREMENT to SERIAL PRIMARY KEY
                line = re.sub(
                    r'"(\w+)"\s+int\(\d+\)\s+NOT NULL\s+PRIMARY KEY\s+AUTO_INCREMENT',
                    r'"\1" SERIAL PRIMARY KEY',
                    line
                )
                # Convert int(xx) NOT NULL AUTO_INCREMENT to SERIAL NOT NULL
                line = re.sub(
                    r'"(\w+)"\s+int\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT',
                    r'"\1" SERIAL NOT NULL',
                    line
                )
                # Remove any remaining AUTO_INCREMENT
                line = re.sub(r',?\s*AUTO_INCREMENT[^,]*', '', line)
            
            # Remove MySQL-specific options from lines
            line = re.sub(r'\)\s+ENGINE=[^;]*', ')', line)
            line = re.sub(r'\)\s+DEFAULT CHARSET[^;]*', ')', line)
            line = re.sub(r'\)\s+COLLATE[^;]*', ')', line)
            
            # Handle enum - convert to VARCHAR with CHECK
            line = re.sub(r"enum\('([^']*)'\)", r"VARCHAR(100) CHECK (value IN ('\1'))", line)
            line = re.sub(r"enum\('([^']*(?:','[^']*)*?)'\)", 
                         lambda m: f"VARCHAR(100) CHECK (value IN ({m.group(1)}))", line)
            
            # Remove UNSIGNED
            line = line.replace(' UNSIGNED', '')
            
            # Fix timestamps
            line = re.sub(r'ON UPDATE CURRENT_TIMESTAMP', '', line)
            line = re.sub(r"DEFAULT '0000-00-00'", "DEFAULT NULL", line)
            line = re.sub(r"DEFAULT '0000-00-00 00:00:00'", "DEFAULT NULL", line)
            
            output.append(line)
            
            if ');' in line:
                current_table = False
            continue
        
        # Process INSERT statements
        if 'INSERT INTO' in line:
            line = line.replace('`', '"')
            # Remove ON DUPLICATE KEY UPDATE
            line = re.sub(r'\s+ON DUPLICATE.*?;', ';', line)
            output.append(line)
            continue
        
        output.append(line)
    
    # Join and clean
    content = '\n'.join(output)
    
    # Remove double commas
    content = re.sub(r',+', ',', content)
    content = re.sub(r',(\s*[);])', r'\1', content)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"✓ Converted to {output_file}")
    tables = len([l for l in content.split('\n') if 'CREATE TABLE' in l])
    print(f"  Tables: {tables}")

def convert_data_types(line):
    # int(xx) -> INTEGER
    line = re.sub(r'\bint\(\d+\)', 'INTEGER', line, flags=re.IGNORECASE)
    # bigint -> BIGINT
    line = re.sub(r'\bbigint\(\d+\)', 'BIGINT', line, flags=re.IGNORECASE)
    # tinyint -> SMALLINT
    line = re.sub(r'\btinyint\(\d+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    # smallint -> SMALLINT  
    line = re.sub(r'\bsmallint\(\d+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    # varchar -> VARCHAR
    line = re.sub(r'\bvarchar\((\d+)\)', r'VARCHAR(\1)', line)
    # longtext -> TEXT
    line = re.sub(r'\blongtext\b', 'TEXT', line, flags=re.IGNORECASE)
    line = re.sub(r'\bmediumtext\b', 'TEXT', line, flags=re.IGNORECASE)
    # decimal/float -> NUMERIC
    line = re.sub(r'\bdecimal\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    line = re.sub(r'\bfloat\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    # timestamp/datetime -> TIMESTAMP
    line = re.sub(r'\b(timestamp|datetime)\b', 'TIMESTAMP', line, flags=re.IGNORECASE)
    # CURRENT_TIMESTAMP fix
    line = re.sub(r'CURRENT_TIMESTAMP\(\)', 'CURRENT_TIMESTAMP', line)
    return line

if __name__ == '__main__':
    convert('demolitiontraders_dump.sql', 'demolitiontraders.sql')
