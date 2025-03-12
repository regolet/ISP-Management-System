const sqlite3 = require('sqlite3').verbose();

// Connect to the database
const db = new sqlite3.Database('database/isp-management.sqlite');

// Function to check schema for a table
function checkTableSchema(tableName) {
    db.all("PRAGMA table_info(" + tableName + ")", [], (err, rows) => {
        if (err) {
            console.error("Error fetching schema for " + tableName + ":", err.message);
            return;
        }

        console.log("Schema for " + tableName + ":");
        rows.forEach((column) => {
            console.log("Column: " + column.name + ", Type: " + column.type + ", Not Null: " + column.notnull + ", Default Value: " + column.dflt_value);
        });
        console.log(''); // Add a newline for separation
    });
}

// Fetch all table names from the database
db.all("SELECT name FROM sqlite_master WHERE type='table'", [], (err, tables) => {
    if (err) {
        console.error("Error fetching table names:", err.message);
        db.close();
        return;
    }

    // Iterate over the tables and check their schemas
    tables.forEach(table => {
        checkTableSchema(table.name);
    });

    // Close the database
    db.close();
});
