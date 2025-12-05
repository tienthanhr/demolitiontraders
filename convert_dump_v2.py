#!/usr/bin/env python3
"""
Advanced MySQL to PostgreSQL converter
"""
import re
import sys

def convert_mysql_to_postgresql(input_file, output_file):
    """Convert MySQL dump to PostgreSQL format"""
    
    with open(input_file, 'r', encoding='latin-1', errors='ignore') as f:
        content = f.read()
    
    # Remove MySQL header comments and version-specific code
    content = re.sub(r'\/\*![\d]+.*?\*\/', '', content, flags=re.DOTALL)
    content = re.sub(r'\/\*.*?LOCK TABLES.*?\*\/', '', content, flags=re.DOTALL)
    content = re.sub(r'^SET .*?;$', '', content, flags=re.MULTILINE)
    content = re.sub(r'^USE .*?;$', '', content, flags=re.MULTILINE)
    
    # Remove LOCK/UNLOCK TABLES
    content = re.sub(r'LOCK TABLES.*?;', '', content, flags=re.DOTALL)
    content = re.sub(r'UNLOCK TABLES;?', '', content, flags=re.DOTALL)
    
    # Process line by line for better control
    lines = content.split('\n')
    output_lines = []
    in_create_table = False
    table_def_lines = []
    
    for line in lines:
        # Skip comments and empty lines
        if not line.strip() or line.strip().startswith('--') or line.strip().startswith('#'):
            continue
        
        # Handle CREATE TABLE
        if 'CREATE TABLE' in line:
            in_create_table = True
            table_def_lines = [line]
            continue
        
        if in_create_table:
            table_def_lines.append(line)
            
            if ') =' in line or ') DEFAULT' in line or ') COLLATE' in line or ') ENGINE' in line or ');' in line:
                # Process the complete table definition
                table_def = '\n'.join(table_def_lines)
                
                # Convert backticks to double quotes
                table_def = table_def.replace('`', '"')
                
                # Remove everything after ) until ;
                table_def = re.sub(r'\)\s*=\d+.*?;', ');', table_def, flags=re.DOTALL)
                table_def = re.sub(r'\)\s+DEFAULT CHARSET.*?;', ');', table_def, flags=re.DOTALL)
                table_def = re.sub(r'\)\s+ENGINE=\w+.*?;', ');', table_def, flags=re.DOTALL)
                table_def = re.sub(r'\)\s+COLLATE.*?;', ');', table_def, flags=re.DOTALL)
                table_def = re.sub(r'\)\s*;', ');', table_def)
                
                # Fix data types
                table_def = re.sub(r'\bint\((\d+)\)\s+NOT NULL\s+PRIMARY KEY\s+AUTO_INCREMENT', r'SERIAL PRIMARY KEY', table_def)
                table_def = re.sub(r'\bint\((\d+)\)\s+NOT NULL\s+AUTO_INCREMENT', r'SERIAL NOT NULL', table_def)
                table_def = re.sub(r'\bint\((\d+)\)', r'INTEGER', table_def)
                table_def = re.sub(r'\bint\s+', r'INTEGER ', table_def)
                table_def = re.sub(r'\btinyint\((\d+)\)', r'SMALLINT', table_def, flags=re.IGNORECASE)
                table_def = re.sub(r'\bbigint\((\d+)\)', r'BIGINT', table_def, flags=re.IGNORECASE)
                table_def = re.sub(r'\bvarchar\((\d+)\)', r'VARCHAR(\1)', table_def)
                table_def = re.sub(r'\bdouble', r'NUMERIC', table_def, flags=re.IGNORECASE)
                table_def = re.sub(r'\bfloat\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', table_def)
                table_def = re.sub(r'\benum\((.*?)\)', r"VARCHAR(50) CHECK (value IN (\1))", table_def)
                table_def = re.sub(r'\bLONGTEXT', r'TEXT', table_def)
                table_def = re.sub(r'\blongtext', r'text', table_def)
                
                # Fix timestamps
                table_def = re.sub(r'timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 
                                 r'timestamp DEFAULT CURRENT_TIMESTAMP', table_def)
                table_def = re.sub(r'timestamp NULL DEFAULT current_timestamp\(\) ON UPDATE current_timestamp\(\)', 
                                 r'timestamp DEFAULT CURRENT_TIMESTAMP', table_def)
                table_def = re.sub(r"DEFAULT '0000-00-00'", "DEFAULT NULL", table_def)
                table_def = re.sub(r"DEFAULT '0000-00-00 00:00:00'", "DEFAULT NULL", table_def)
                
                # Remove CONSTRAINT foreign key names that start with numbers
                table_def = re.sub(r',?\s*CONSTRAINT "(\d+)"', '', table_def)
                
                # Clean up KEY definitions to PostgreSQL style
                table_def = re.sub(r'^\s*KEY "idx_', '  INDEX idx_', table_def, flags=re.MULTILINE)
                
                output_lines.append(table_def)
                in_create_table = False
                table_def_lines = []
                continue
        
        # Handle INSERT statements
        if 'INSERT INTO' in line:
            # Convert backticks to double quotes
            line = line.replace('`', '"')
            
            # Fix empty string for enum fields
            line = re.sub(r"'',", "'',", line)
            
            output_lines.append(line)
            continue
        
        if output_lines or (line.strip() and not in_create_table):
            # Convert backticks in other statements
            line = line.replace('`', '"')
            if line.strip():
                output_lines.append(line)
    
    # Join and clean up
    output_content = '\n'.join(output_lines)
    
    # Final cleanup of blank lines
    output_content = re.sub(r'\n\n+', '\n\n', output_content)
    
    # Write output
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(output_content)
    
    print(f"✓ Converted {input_file} to {output_file}")
    
    # Count tables
    tables = len(re.findall(r'CREATE TABLE', output_content))
    print(f"  Found {tables} tables")

if __name__ == '__main__':
    convert_mysql_to_postgresql('demolitiontraders_dump.sql', 'demolitiontraders_pg_v2.sql')
