#!/usr/bin/env python3
import re
import sys

def convert_mysql_to_postgresql(input_file, output_file):
    """Convert MySQL dump to PostgreSQL format"""
    
    with open(input_file, 'r', encoding='latin-1') as f:
        content = f.read()
    
    # Remove MySQL-specific comments and statements
    lines = content.split('\n')
    output_lines = []
    skip_next = False
    
    for i, line in enumerate(lines):
        # Skip MySQL-specific statements
        if any(line.startswith(x) for x in [
            '/*!40',  # MySQL version-specific
            'SET ',   # MySQL SET statements
            '--',     # Comments
            '/*',     # Block comments
        ]):
            if '/*' in line and '*/' not in line:
                skip_next = True
            if '*/' in line:
                skip_next = False
            continue
        
        if skip_next:
            if '*/' in line:
                skip_next = False
            continue
        
        if not line.strip():
            continue
        
        # Convert AUTO_INCREMENT to SERIAL
        line = re.sub(r'\bAUTO_INCREMENT\b', '', line)
        line = re.sub(r'\bint\(.*?\)\s+NOT NULL', 'SERIAL NOT NULL', line)
        line = re.sub(r'\bint\(.*?\)\s+DEFAULT 0', 'INTEGER DEFAULT 0', line)
        line = re.sub(r'\bint\(.*?\)', 'INTEGER', line)
        
        # Convert backticks to double quotes
        line = line.replace('`', '"')
        
        # Convert DEFAULT CURRENT_TIMESTAMP
        line = re.sub(r"DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 
                     "DEFAULT CURRENT_TIMESTAMP", line)
        line = re.sub(r"DEFAULT '0000-00-00'", "DEFAULT NULL", line)
        line = re.sub(r"'0000-00-00'", "NULL", line)
        
        # Convert TINYINT to SMALLINT
        line = line.replace('tinyint', 'smallint')
        line = line.replace('TINYINT', 'SMALLINT')
        
        # Convert LONGTEXT
        line = line.replace('LONGTEXT', 'TEXT')
        line = line.replace('longtext', 'text')
        
        # Remove ENGINE= and DEFAULT CHARSET
        line = re.sub(r'\s+ENGINE=\w+', '', line)
        line = re.sub(r'\s+DEFAULT CHARSET=\w+', '', line)
        line = re.sub(r'\s+DEFAULT COLLATE=\S+', '', line)
        
        output_lines.append(line)
    
    # Join and clean up
    output_content = '\n'.join(output_lines)
    
    # Remove extra blank lines
    output_content = re.sub(r'\n\n+', '\n\n', output_content)
    
    # Write output
    with open(output_file, 'w', encoding='latin-1') as f:
        f.write(output_content)
    
    print(f"✓ Converted {input_file} to {output_file}")

if __name__ == '__main__':
    convert_mysql_to_postgresql('demolitiontraders_dump.sql', 'demolitiontraders_pg.sql')
