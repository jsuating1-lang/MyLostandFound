from http.server import HTTPServer, SimpleHTTPRequestHandler
import os

# Serve files from this script's directory
os.chdir(os.path.dirname(__file__))

class Handler(SimpleHTTPRequestHandler):
    # copy and extend the default MIME map to serve .php as text/html
    extensions_map = SimpleHTTPRequestHandler.extensions_map.copy()
    extensions_map.update({
        '.php': 'text/html',
    })

if __name__ == '__main__':
    addr = ('127.0.0.1', 8001)
    httpd = HTTPServer(addr, Handler)
    print(f"Serving on http://{addr[0]}:{addr[1]}")
    httpd.serve_forever()
