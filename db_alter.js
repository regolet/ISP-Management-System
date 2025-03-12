const sqlite3 = require('sqlite3').verbose();

const db = new sqlite3.Database('database/isp-management.sqlite', (err) => {
  if (err) {
    console.error(err.message);
  }
  console.log('Connected to the database.');
});

db.run(`ALTER TABLE billing ADD COLUMN client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE;`, (err) => {
  if (err) {
    console.error(err.message);
  } else {
    console.log('Column client_id added to billing table.');
  }
  db.close((err) => {
    if (err) {
      console.error(err.message);
    }
    console.log('Closed the database connection.');
  });
});