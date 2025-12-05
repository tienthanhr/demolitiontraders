#!/usr/bin/env python3
"""
Clean MySQL to PostgreSQL converter
"""
import re

def convert_mysql_to_postgresql(input_file, output_file):
    with open(input_file, 'r', encoding='latin-1', errors='ignore') as f:
        raw_content = f.read()
    
    # Step 1: Extract CREATE TABLE and INSERT statements
    output_lines = []
    
    # Remove MySQL header comments and settings
    content = raw_content
    content = re.sub(r'/\*.*?\*/', '', content, flags=re.DOTALL)  # Remove comments
    content = re.sub(r'^SET .*?;', '', content, flags=re.MULTILINE)
    content = re.sub(r'^USE .*?;', '', content, flags=re.MULTILINE)
    
    # Find all CREATE TABLE statements
    create_pattern = r'CREATE TABLE[^(]*\([^)]*(?:\([^)]*\)[^)]*)*\)[^;]*;'
    creates = re.finditer(create_pattern, content, re.DOTALL)
    
    inserts = re.finditer(r'INSERT INTO.*?;', content, re.DOTALL)
    
    for match in creates:
        stmt = match.group(0).strip()
        if not stmt:
            continue
            
        # Process the CREATE TABLE statement
        stmt = convert_create_table(stmt)
        if stmt:
            output_lines.append(stmt + '\n')
    
    # Add all INSERT statements
    for match in inserts:
        stmt = match.group(0).strip()
        if stmt:
            stmt = convert_insert(stmt)
            if stmt:
                output_lines.append(stmt + '\n')
    
    # Write output
    with open(output_file, 'w', encoding='utf-8') as f:
        for line in output_lines:
            f.write(line)
    
    print(f"✓ Converted {input_file} to {output_file}")
    print(f"  Tables: {len([l for l in output_lines if 'CREATE TABLE' in l])}")
    print(f"  Inserts: {len([l for l in output_lines if 'INSERT INTO' in l])}")

def convert_create_table(stmt):
    """Convert a single CREATE TABLE statement"""
    stmt = stmt.strip()
    
    # Replace backticks
    stmt = stmt.replace('`', '"')
    
    # Extract table name and definition
    match = re.match(r'CREATE TABLE\s+"?(\w+)"?\s*\((.*)\)\s*([^;]*);?', stmt, re.DOTALL)
    if not match:
        return None
    
    table_name = match.group(1)
    table_def = match.group(2)
    table_opts = match.group(3)
    
    # Process table definition line by line
    lines = table_def.split('\n')
    col_lines = []
    
    for line in lines:
        line = line.strip()
        if not line:
            continue
        
        # Skip LOCK/UNLOCK
        if 'LOCK' in line or 'UNLOCK' in line:
            continue
        
        # Convert data types
        line = convert_data_types(line)
        
        # Handle PRIMARY KEY
        if 'PRIMARY KEY' in line and 'AUTO_INCREMENT' in line:
            line = re.sub(r'"(\w+)"\s+int.*?AUTO_INCREMENT', r'"\1" SERIAL', line)
            line = re.sub(r',?\s*AUTO_INCREMENT.*', '', line)
        else:
            line = re.sub(r',?\s+AUTO_INCREMENT.*', '', line)
        
        # Clean up table options in constraints
        line = re.sub(r'\)\s*ENGINE.*$', '', line)
        line = re.sub(r'\)\s*DEFAULT CHARSET.*$', '', line)
        line = re.sub(r'\)\s*COLLATE.*$', '', line)
        
        # Handle enum - PostgreSQL doesn't support enum syntax well without CREATE TYPE
        # Convert to VARCHAR with check constraint
        line = re.sub(r"enum\('([^']*(?:',[^']*)*?)'\)", r"VARCHAR(100)", line)
        
        col_lines.append(line)
    
    # Rejoin with proper formatting
    table_def = ',\n  '.join(col_lines)
    table_def = table_def.replace(',\n  PRIMARY KEY', ',\n  PRIMARY KEY')
    table_def = table_def.replace(',\n  KEY', ',\n  KEY')
    table_def = table_def.replace(',\n  CONSTRAINT', ',\n  CONSTRAINT')
    table_def = table_def.replace(',\n  INDEX', ',\n  INDEX')
    
    return f'CREATE TABLE IF NOT EXISTS "{table_name}" (\n  {table_def}\n);'

def convert_insert(stmt):
    """Convert INSERT statement"""
    stmt = stmt.strip()
    
    # Replace backticks
    stmt = stmt.replace('`', '"')
    
    # Clean up ON DUPLICATE KEY UPDATE
    stmt = re.sub(r'\s+ON DUPLICATE KEY UPDATE.*?;', ';', stmt)
    
    return stmt

def convert_data_types(line):
    """Convert MySQL data types to PostgreSQL"""
    # Handle various integer types
    line = re.sub(r'\bint\(\d+\)', 'INTEGER', line, flags=re.IGNORECASE)
    line = re.sub(r'\bbigint\(\d+\)', 'BIGINT', line, flags=re.IGNORECASE)
    line = re.sub(r'\btinyint\(\d+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    line = re.sub(r'\bsmallint\(\d+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    
    # Handle varchar
    line = re.sub(r'\bvarchar\((\d+)\)', r'VARCHAR(\1)', line)
    
    # Handle text types
    line = re.sub(r'\blongtext\b', 'TEXT', line, flags=re.IGNORECASE)
    line = re.sub(r'\bmediumtext\b', 'TEXT', line, flags=re.IGNORECASE)
    
    # Handle decimal/numeric
    line = re.sub(r'\bdecimal\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    line = re.sub(r'\bfloat\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    line = re.sub(r'\bdouble', 'NUMERIC', line, flags=re.IGNORECASE)
    
    # Handle timestamp
    line = re.sub(r'\btimestamp\b', 'TIMESTAMP', line)
    line = re.sub(r'\bCURRENT_TIMESTAMP\(\)', 'CURRENT_TIMESTAMP', line)
    line = re.sub(r'\bON UPDATE CURRENT_TIMESTAMP\b', '', line)
    
    # Handle datetime
    line = re.sub(r'\bdatetime\b', 'TIMESTAMP', line, flags=re.IGNORECASE)
    
    # Handle date
    line = re.sub(r'\bdate\b', 'DATE', line, flags=re.IGNORECASE)
    
    # Handle default values
    line = re.sub(r"DEFAULT '0000-00-00'", 'DEFAULT NULL', line)
    line = re.sub(r"DEFAULT '0000-00-00 00:00:00'", 'DEFAULT NULL', line)
    
    return line

if __name__ == '__main__':
    convert_mysql_to_postgresql('demolitiontraders_dump.sql', 'demolitiontraders_pg_v3.sql')
