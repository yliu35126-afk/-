const http = require('http');

const port = parseInt(process.env.PORT || '3012', 10);
const host = process.env.WS_HOST || '127.0.0.1';

const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ ok: true, url: req.url, ts: new Date().toISOString() }));
});

server.listen(port, host, () => {
  console.log(`Quick HTTP server listening on http://${host}:${port}/`);
});