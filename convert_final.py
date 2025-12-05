#!/usr/bin/env python3
"""
Final MySQL to PostgreSQL converter - clean and simple approach
"""
import re

def convert_mysql_to_postgresql(input_file, output_file):
    with open(input_file, 'r', encoding='latin-1', errors='ignore') as f:
        content = f.read()
    
    output_lines = []
    
    # Extract CREATE TABLE statements and process them
    # Match CREATE TABLE ... (...) ... ;
    tables = re.finditer(
        r'CREATE TABLE `([^`]+)`\s*\((.*?)\)\s*(?:ENGINE|DEFAULT|;)',
        content,
        re.DOTALL | re.IGNORECASE
    )
    
    for table_match in tables:
        table_name = table_match.group(1)
        table_body = table_match.group(2)
        
        # Clean the table body
        cleaned_body = clean_table_definition(table_body)
        
        output_lines.append(f'CREATE TABLE "{table_name}" ({cleaned_body});')
        output_lines.append('')
    
    # Extract INSERT statements
    inserts = re.finditer(
        r'INSERT INTO `([^`]+)`.*?VALUES(.*?);',
        content,
        re.DOTALL | re.IGNORECASE
    )
    
    for insert_match in inserts:
        table_name = insert_match.group(1)
        values = insert_match.group(2)
        
        # Replace backticks with quotes
        values = values.replace('`', '"')
        
        output_lines.append(f'INSERT INTO "{table_name}" VALUES{values};')
        output_lines.append('')
    
    # Write output
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(output_lines))
    
    print(f"✓ Converted {input_file} to {output_file}")
    print(f"  Tables: {len(list(tables))}")

def clean_table_definition(body):
    """Clean table definition from MySQL to PostgreSQL"""
    
    # Split by lines and process
    lines = body.split('\n')
    clean_lines = []
    
    for line in lines:
        line = line.strip()
        if not line or line.startswith('--'):
            continue
        
        # Remove backticks
        line = line.replace('`', '"')
        
        # Convert data types
        line = convert_types(line)
        
        # Handle AUTO_INCREMENT
        if 'AUTO_INCREMENT' in line:
            # Find the column definition and convert to SERIAL
            col_match = re.match(r'"(\w+)"\s+int', line, re.IGNORECASE)
            if col_match:
                col_name = col_match.group(1)
                # Replace with SERIAL
                line = re.sub(r'"(\w+)"\s+int\([0-9]+\)\s+NOT NULL\s+AUTO_INCREMENT', 
                             f'"{col_name}" SERIAL NOT NULL', line, flags=re.IGNORECASE)
                line = re.sub(r',\s*AUTO_INCREMENT.*', '', line)
        
        # Remove MySQL-specific options that appear in columns
        line = re.sub(r'\s+UNSIGNED', '', line)
        line = re.sub(r',\s*$', '', line)  # Remove trailing comma
        
        if line:
            clean_lines.append(line)
    
    # Rejoin with commas
    result = ',\n  '.join(clean_lines)
    
    # Clean up any double commas
    result = re.sub(r',+', ',', result)
    result = re.sub(r',\s*\)', ')', result)
    
    return result

def convert_types(line):
    """Convert MySQL types to PostgreSQL"""
    
    # Integer types
    line = re.sub(r'\bint\([0-9]+\)', 'INTEGER', line, flags=re.IGNORECASE)
    line = re.sub(r'\bbigint\([0-9]+\)', 'BIGINT', line, flags=re.IGNORECASE)
    line = re.sub(r'\btinyint\([0-9]+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    
    # Varchar
    line = re.sub(r'\bvarchar\(([0-9]+)\)', r'VARCHAR(\1)', line)
    
    # Text types
    line = re.sub(r'\blongtext\b', 'TEXT', line, flags=re.IGNORECASE)
    line = re.sub(r'\bmediumtext\b', 'TEXT', line, flags=re.IGNORECASE)
    line = re.sub(r'\btext\b', 'TEXT', line)
    
    # Enum - convert to VARCHAR
    line = re.sub(r"\benum\([^)]+\)", "VARCHAR(100)", line, flags=re.IGNORECASE)
    
    # Timestamp
    line = re.sub(r'\btimestamp\b', 'TIMESTAMP', line)
    line = re.sub(r'\bON UPDATE CURRENT_TIMESTAMP', '', line)
    
    # Datetime
    line = re.sub(r'\bdatetime\b', 'TIMESTAMP', line, flags=re.IGNORECASE)
    
    # Decimal
    line = re.sub(r'\bdecimal\(([0-9]+),([0-9]+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    line = re.sub(r'\bfloat\(([0-9]+),([0-9]+)\)', r'NUMERIC(\1,\2)', line, flags=re.IGNORECASE)
    
    # Default NULL for 0000-00-00
    line = re.sub(r"DEFAULT '0000-00-00'", 'DEFAULT NULL', line)
    line = re.sub(r"DEFAULT '0000-00-00 00:00:00'", 'DEFAULT NULL', line)
    
    # Remove trailing commas in constraints
    line = re.sub(r',(\s*(?:PRIMARY|KEY|CONSTRAINT|FOREIGN|UNIQUE))', r'\1', line)
    
    return line

if __name__ == '__main__':
    convert_mysql_to_postgresql('demolitiontraders_dump.sql', 'demolitiontraders.sql')
