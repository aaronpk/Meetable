


## Requirements

Install Laravel with a MySQL database backend

Set up a queuing mechanism such as Redis

Make sure the `storage` folder is writable by the web server 


### imageproxy

Install https://github.com/willnorris/imageproxy

Run with:

`imageproxy -cache memory -allowHosts events.example.org -baseURL https://events.example.org/ -signatureKey 1234 -addr 127.0.0.1:8090`

Configure nginx to proxy `/img/` to the imageproxy:

```
  location /img/ {
    proxy_pass http://localhost:8090/;
  }
```

### Public folder

```
  location /public {
    alias /web/sites/Almanac/storage/app/public;
  }
```

