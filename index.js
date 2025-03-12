
const http = require('http');
const fs = require('fs');
const path = require('path');

const server = http.createServer((req, res) => {
  // Redirect to PHP entry point
  res.writeHead(302, {
    'Location': '/public/index.php'
  });
  res.end();
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Access your PHP application at: https://${process.env.REPL_SLUG}.${process.env.REPL_OWNER}.repl.co/public/`);
});
