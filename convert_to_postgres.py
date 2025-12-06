#!/usr/bin/env python3
"""
Convert MySQL SQL dump to PostgreSQL compatible format
Usage: python convert_to_postgres.py demolitiontraders_fly.sql > demolitiontraders_pg.sql
"""

import sys
import re

def convert_mysql_to_postgres(sql_content):
    """Convert MySQL SQL syntax to PostgreSQL compatible syntax"""
    
    # Remove MySQL specific comments
    sql_content = re.sub(r'(?m)^-- MySQL dump.*?$', '', sql_content)
    sql_content = re.sub(r'(?m)^-- Host:.*?$', '', sql_content)
    sql_content = re.sub(r'(?m)^-- Server version.*?$', '', sql_content)
    sql_content = re.sub(r'(?m)^-- MySQL Client version.*?$', '', sql_content)
    
    # Remove SET statements that PostgreSQL doesn't understand
    sql_content = re.sub(r"SET\s+\w+\s*=.*?;", '', sql_content, flags=re.IGNORECASE)
    
    # Replace AUTO_INCREMENT with SERIAL or GENERATED
    sql_content = re.sub(
        r'AUTO_INCREMENT',
        'GENERATED ALWAYS AS IDENTITY',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Fix CREATE TABLE to remove AUTO_INCREMENT part
    sql_content = re.sub(
        r'GENERATED ALWAYS AS IDENTITY\s*,',
        'GENERATED ALWAYS AS IDENTITY,',
        sql_content
    )
    
    # Replace ENGINE=InnoDB with nothing (PostgreSQL doesn't need it)
    sql_content = re.sub(
        r'\s+ENGINE\s*=\s*\w+',
        '',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Replace DEFAULT CHARSET with nothing
    sql_content = re.sub(
        r'\s+DEFAULT\s+CHARSET\s*=\s*\w+',
        '',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Replace COLLATE with nothing
    sql_content = re.sub(
        r'\s+COLLATE\s*=\s*[\w_]+',
        '',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Replace INT with INTEGER
    sql_content = re.sub(r'\bINT\b', 'INTEGER', sql_content, flags=re.IGNORECASE)
    
    # Replace TINYINT with SMALLINT
    sql_content = re.sub(r'\bTINYINT\b', 'SMALLINT', sql_content, flags=re.IGNORECASE)
    
    # Replace BIGINT UNSIGNED with BIGINT (PostgreSQL handles differently)
    sql_content = re.sub(r'\bBIGINT\s+UNSIGNED\b', 'BIGINT', sql_content, flags=re.IGNORECASE)
    
    # Replace INT UNSIGNED with INTEGER
    sql_content = re.sub(r'\bINTEGER\s+UNSIGNED\b', 'INTEGER', sql_content, flags=re.IGNORECASE)
    
    # Replace DECIMAL UNSIGNED
    sql_content = re.sub(r'(\bDECIMAL\s*\([^)]+\))\s+UNSIGNED', r'\1', sql_content, flags=re.IGNORECASE)
    
    # Replace VARCHAR to just VARCHAR (remove MySQL length)
    # Keep the length as is since PostgreSQL also supports it
    
    # Replace DATETIME with TIMESTAMP
    sql_content = re.sub(r'\bDATETIME\b', 'TIMESTAMP', sql_content, flags=re.IGNORECASE)
    
    # Replace TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    sql_content = re.sub(
        r'TIMESTAMP\s+DEFAULT\s+CURRENT_TIMESTAMP',
        'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Replace TEXT with TEXT (PostgreSQL compatible)
    sql_content = re.sub(r'\bLONGTEXT\b', 'TEXT', sql_content, flags=re.IGNORECASE)
    sql_content = re.sub(r'\bMEDIUMTEXT\b', 'TEXT', sql_content, flags=re.IGNORECASE)
    
    # Replace BLOB with BYTEA
    sql_content = re.sub(r'\bBLOB\b', 'BYTEA', sql_content, flags=re.IGNORECASE)
    sql_content = re.sub(r'\bLONGBLOB\b', 'BYTEA', sql_content, flags=re.IGNORECASE)
    
    # Replace ON UPDATE CURRENT_TIMESTAMP (not supported in PostgreSQL the same way)
    sql_content = re.sub(
        r',\s*ON UPDATE CURRENT_TIMESTAMP',
        '',
        sql_content,
        flags=re.IGNORECASE
    )
    
    # Replace KEY to nothing (PostgreSQL doesn't use KEY keyword in column definitions)
    # But keep FOREIGN KEY, PRIMARY KEY, UNIQUE KEY
    sql_content = re.sub(
        r'KEY\s+`([^`]+)`\s*\(`([^)]+)`\)',
        'INDEX (\2)',
        sql_content
    )
    
    # Remove backticks from identifiers (PostgreSQL uses double quotes or none)
    sql_content = re.sub(r'`([^`]+)`', r'"\1"', sql_content)
    
    # Fix double quotes that might have been created
    sql_content = re.sub(r'""', '"', sql_content)
    
    # Remove trailing commas before closing parenthesis in CREATE TABLE
    sql_content = re.sub(r',\s*\)', ')', sql_content)
    
    # Add PostgreSQL specific settings at the end of CREATE TABLE
    # This is tricky because we need to be careful with ALTER TABLE statements
    
    # Remove LOCK TABLES and UNLOCK TABLES statements
    sql_content = re.sub(r'LOCK TABLES.*?;', '', sql_content, flags=re.IGNORECASE | re.DOTALL)
    sql_content = re.sub(r'UNLOCK TABLES.*?;', '', sql_content, flags=re.IGNORECASE | re.DOTALL)
    
    # Remove /*!40000 ALTER TABLE...DISABLE KEYS */ and similar MySQL directives
    sql_content = re.sub(r'/\*!.*?\*/;?', '', sql_content)
    
    # Clean up multiple blank lines
    sql_content = re.sub(r'\n\n+', '\n\n', sql_content)
    
    return sql_content

def main():
    if len(sys.argv) < 2:
        print("Usage: python convert_to_postgres.py <mysql_dump_file>")
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    try:
        # Try different encodings
        encodings = ['utf-8-sig', 'utf-16', 'latin-1', 'cp1252']
        sql_content = None
        for encoding in encodings:
            try:
                with open(input_file, 'r', encoding=encoding) as f:
                    sql_content = f.read()
                print(f"Successfully read file with {encoding} encoding", file=sys.stderr)
                break
            except (UnicodeDecodeError, LookupError):
                continue
        
        if sql_content is None:
            raise Exception("Could not decode file with any encoding")
    except Exception as e:
        print(f"Error reading file: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Convert
    converted = convert_mysql_to_postgres(sql_content)
    
    # Output
    print(converted)

if __name__ == '__main__':
    main()
