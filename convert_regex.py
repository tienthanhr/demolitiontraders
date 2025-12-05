#!/usr/bin/env python3
import re

# Read the dump
with open('demolitiontraders_dump.sql', 'r', encoding='latin-1', errors='ignore') as f:
    content = f.read()

# Extract just the CREATE TABLE and INSERT statements
creates = re.findall(r'CREATE TABLE.*?\);', content, re.DOTALL)
inserts = re.findall(r'INSERT INTO.*?;', content, re.DOTALL)

output = []

# Convert CREATE TABLE statements
for create in creates:
    # Replace backticks
    create = create.replace('`', '"')
    
    # Remove MySQL-specific options
    create = re.sub(r'\)\s+ENGINE=.*?;', ');', create, flags=re.DOTALL)
    create = re.sub(r'\)\s+DEFAULT CHARSET.*?;', ');', create, flags=re.DOTALL)  
    create = re.sub(r'\)\s+COLLATE.*?;', ');', create, flags=re.DOTALL)
    
    # Fix enum - PostgreSQL doesn't have enum in the traditional sense
    # Just use VARCHAR for now
    create = re.sub(r"enum\([^)]*\)", "VARCHAR(50)", create, flags=re.IGNORECASE)
    
    # Fix int types
    create = re.sub(r'\bint\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT', 'SERIAL NOT NULL', create)
    create = re.sub(r'\bint\(\d+\)', 'INTEGER', create)
    create = re.sub(r'\bbigint\(\d+\)', 'BIGINT', create)
    create = re.sub(r'\btinyint\(\d+\)', 'SMALLINT', create)
    
    # Fix varchar
    create = re.sub(r'\bvarchar\((\d+)\)', r'VARCHAR(\1)', create)
    
    # Fix text types
    create = re.sub(r'\blongtext\b', 'TEXT', create, flags=re.IGNORECASE)
    create = re.sub(r'\bmediumtext\b', 'TEXT', create, flags=re.IGNORECASE)
    
    # Fix decimal
    create = re.sub(r'\bdecimal\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', create, flags=re.IGNORECASE)
    create = re.sub(r'\bfloat\((\d+),(\d+)\)', r'NUMERIC(\1,\2)', create, flags=re.IGNORECASE)
    
    # Fix timestamp
    create = re.sub(r'\btimestamp\b', 'TIMESTAMP', create)
    create = re.sub(r'\bdatetime\b', 'TIMESTAMP', create, flags=re.IGNORECASE)
    create = re.sub(r'CURRENT_TIMESTAMP\(\)', 'CURRENT_TIMESTAMP', create)
    create = re.sub(r'\bON UPDATE CURRENT_TIMESTAMP\b', '', create)
    
    # Fix default values
    create = re.sub(r"DEFAULT '0000-00-00'", "DEFAULT NULL", create)
    create = re.sub(r"DEFAULT '0000-00-00 00:00:00'", "DEFAULT NULL", create)
    
    # Remove AUTO_INCREMENT
    create = re.sub(r',?\s*AUTO_INCREMENT[^,)]*', '', create)
    
    # Remove UNSIGNED
    create = create.replace(' UNSIGNED', '')
    
    output.append(create + '\n')

# Add INSERT statements
for insert in inserts:
    insert = insert.replace('`', '"')
    insert = re.sub(r'\s+ON DUPLICATE KEY UPDATE.*?;', ';', insert)
    output.append(insert + '\n')

# Write output
with open('demolitiontraders_pg_clean.sql', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output))

print(f"✓ Created demolitiontraders_pg_clean.sql")
print(f"  Tables: {len(creates)}")
print(f"  Inserts: {len(inserts)}")
