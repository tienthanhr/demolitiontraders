import re
import sys

def convert_mysql_to_postgresql(input_file, output_file):
    with open(input_file, 'r', encoding='utf-8') as f:
        sql = f.read()
    
    # Remove MySQL specific comments
    sql = re.sub(r'/\*!.*?\*/;', '', sql, flags=re.DOTALL)
    sql = re.sub(r'--.*?$', '', sql, flags=re.MULTILINE)
    
    # Remove LOCK/UNLOCK TABLES
    sql = re.sub(r'LOCK TABLES.*?;', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'UNLOCK TABLES;', '', sql, flags=re.IGNORECASE)
    
    # Convert ENGINE and CHARSET
    sql = re.sub(r'\) ENGINE=\w+ AUTO_INCREMENT=\d+ DEFAULT CHARSET=\w+ COLLATE=\w+;', ');', sql)
    sql = re.sub(r'\) ENGINE=\w+ DEFAULT CHARSET=\w+ COLLATE=\w+;', ');', sql)
    sql = re.sub(r'\) ENGINE=\w+;', ');', sql)
    
    # Convert data types
    sql = re.sub(r'\bint\(\d+\)', 'INTEGER', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\btinyint\(1\)', 'BOOLEAN', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\btinyint\(\d+\)', 'SMALLINT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bdatetime\b', 'TIMESTAMP', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\blongtext\b', 'TEXT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bmediumtext\b', 'TEXT', sql, flags=re.IGNORECASE)
    
    # Convert AUTO_INCREMENT to SERIAL
    sql = re.sub(r'`(\w+)` INTEGER NOT NULL AUTO_INCREMENT,', r'"\1" SERIAL PRIMARY KEY,', sql)
    sql = re.sub(r'PRIMARY KEY \(`\w+`\),', '', sql)
    
    # Convert backticks to double quotes
    sql = re.sub(r'`([^`]+)`', r'"\1"', sql)
    
    # Convert ENUM to VARCHAR with CHECK constraint (simplified)
    sql = re.sub(r'enum\([^)]+\)', 'VARCHAR(50)', sql, flags=re.IGNORECASE)
    
    # Convert current_timestamp() to CURRENT_TIMESTAMP
    sql = sql.replace('current_timestamp()', 'CURRENT_TIMESTAMP')
    
    # Convert ON UPDATE current_timestamp (PostgreSQL doesn't support this, need triggers)
    sql = re.sub(r' ON UPDATE CURRENT_TIMESTAMP', '', sql, flags=re.IGNORECASE)
    
    # Fix KEY to INDEX
    sql = re.sub(r'\bKEY\s+', 'INDEX ', sql, flags=re.IGNORECASE)
    
    # Remove inline INDEX definitions (create separately)
    sql = re.sub(r',\s*INDEX "[^"]+"\s*\([^)]+\)', '', sql)
    sql = re.sub(r',\s*CONSTRAINT "[^"]+"\s*FOREIGN KEY[^;]+', '', sql)
    
    # Clean up multiple commas and spaces
    sql = re.sub(r',\s*\)', ')', sql)
    sql = re.sub(r'\n\n+', '\n\n', sql)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(sql)
    
    print(f"Converted {input_file} to {output_file}")

if __name__ == "__main__":
    input_file = "demolitiontraders_mysql.sql"
    output_file = "demolitiontraders_postgresql.sql"
    convert_mysql_to_postgresql(input_file, output_file)
