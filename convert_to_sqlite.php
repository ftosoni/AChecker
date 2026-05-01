<?php
/**
 * Migration script to convert MySQL database to SQLite
 * Run this from your local command line: php convert_to_sqlite.php
 */

include_once('include/config.inc.php');

// 1. Connect to MySQL
$mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
if (!$mysql) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// 2. Create/Connect to SQLite
$sqlite_file = SQLITE_PATH;
if (file_exists($sqlite_file)) {
    unlink($sqlite_file); // Start fresh
}

try {
    $sqlite = new PDO("sqlite:" . $sqlite_file);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Failed to connect to SQLite: " . $e->getMessage());
}

echo "Migrating tables from MySQL (" . DB_NAME . ") to SQLite (" . basename($sqlite_file) . ")...\n";

// 3. Get all tables
$tables_result = mysqli_query($mysql, "SHOW TABLES");
while ($table_row = mysqli_fetch_row($tables_result)) {
    $table = $table_row[0];
    echo "Processing table: $table...\n";

    // Get table creation info to guess types
    $create_result = mysqli_query($mysql, "SHOW CREATE TABLE `$table`") or die(mysqli_error($mysql));
    $create_row = mysqli_fetch_row($create_result);
    $create_sql = $create_row[1];

    // Basic MySQL to SQLite conversion for CREATE TABLE
    $sqlite_create = $create_sql;

    // 1. Handle AUTO_INCREMENT -> INTEGER PRIMARY KEY AUTOINCREMENT
    $sqlite_create = preg_replace('/\b\w*int(\(\d+\))?(\s+unsigned)?\s+NOT\s+NULL\s+AUTO_INCREMENT\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlite_create);
    $sqlite_create = preg_replace('/\b\w*int(\(\d+\))?(\s+unsigned)?\s+AUTO_INCREMENT\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlite_create);

    // 2. Generic type replacements
    $sqlite_create = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint|integer)(\(\d+\))?(\s+unsigned)?\b/i', 'INTEGER', $sqlite_create);
    $sqlite_create = preg_replace('/varchar\(\d+\)/i', 'TEXT', $sqlite_create);
    $sqlite_create = preg_replace('/datetime/i', 'TEXT', $sqlite_create);
    $sqlite_create = preg_replace('/longtext/i', 'TEXT', $sqlite_create);
    $sqlite_create = preg_replace('/mediumtext/i', 'TEXT', $sqlite_create);
    $sqlite_create = preg_replace('/tinytext/i', 'TEXT', $sqlite_create);
    $sqlite_create = preg_replace('/ENUM\(.*?\)/i', 'TEXT', $sqlite_create);

    // 3. Remove MySQL specific column modifiers
    $sqlite_create = preg_replace('/COMMENT\s+\'.*?\'/i', '', $sqlite_create); // Strip column comments
    $sqlite_create = preg_replace('/\s+unsigned\b/i', '', $sqlite_create);     // Strip unsigned
    
    // 4. Remove MySQL specific table options and indexes
    $sqlite_create = preg_replace('/PRIMARY KEY\s+\(`.*?`\),?/i', '', $sqlite_create); 
    $sqlite_create = preg_replace('/KEY `.*?` \(.*?\),?/i', '', $sqlite_create);
    $sqlite_create = preg_replace('/UNIQUE KEY `.*?` \(.*?\),?/i', '', $sqlite_create);
    $sqlite_create = preg_replace('/CONSTRAINT `.*?` FOREIGN KEY \(.*?\).*?,?/is', '', $sqlite_create);
    $sqlite_create = preg_replace('/AUTO_INCREMENT=\d+/i', '', $sqlite_create); 
    $sqlite_create = preg_replace('/ENGINE=.*?($| )/i', '', $sqlite_create);
    $sqlite_create = preg_replace('/DEFAULT CHARSET=.*?($| )/i', '', $sqlite_create);
    $sqlite_create = preg_replace('/COLLATE=.*?($| )/i', '', $sqlite_create);
    $sqlite_create = preg_replace('/COMMENT=\'.*?\'/i', '', $sqlite_create);

    // 5. Cleanup
    $sqlite_create = preg_replace('/,\s*\)/', ')', $sqlite_create); 
    $sqlite_create = str_replace('`', '"', $sqlite_create); // SQLite prefers double quotes for identifiers
    
    // Execute CREATE
    try {
        $sqlite->exec($sqlite_create);
    } catch (Exception $e) {
        echo "Error creating table $table. SQL: \n$sqlite_create\n";
        throw $e;
    }

    // 4. Migrate Data
    $data_result = mysqli_query($mysql, "SELECT * FROM `$table`") or die(mysqli_error($mysql));
    $count = 0;
    while ($row = mysqli_fetch_assoc($data_result)) {
        $cols = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        
        $stmt = $sqlite->prepare("INSERT INTO `$table` ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($row));
        $count++;
    }
    echo "  - Migrated $count rows.\n";
}

echo "\nMigration complete! Your SQLite database is ready at: $sqlite_file\n";
echo "To switch AChecker to use this database, update include/config.inc.php:\n";
echo "define('DB_TYPE', 'sqlite');\n";
?>
