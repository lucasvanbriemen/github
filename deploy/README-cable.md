# Real-time (ActionCable / Turbo Streams) on the Plesk + Apache + Passenger server

Real-time updates (live comments, notifications, CI, sidebar) use ActionCable
over SolidCable. Getting WebSockets working through this server needs three
things that are NOT default Rails behavior — all are required, and each one
independently breaks the connection if missing.

## 1. Allowed origins (in the repo — `config/environments/production.rb`)

```ruby
config.action_cable.allowed_request_origins = [ %r{\Ahttps?://[a-z0-9.-]+\.ltvb\.nl\z} ]
```

Without this, production refuses every handshake with a 404 (the browser reports
it as `'Connection' header is missing`).

## 2. Force HTTP/1.1 on the vhost + proxy /cable via mod_proxy_wstunnel

HTTP/2 forbids the `Connection: Upgrade` header, and Passenger strips it from the
101 response anyway. So we serve this vhost over HTTP/1.1 and hand /cable to a
standalone cable process through `mod_proxy_wstunnel` (which preserves the
upgrade headers). These are Plesk custom-directive includes (survive Plesk
reconfigures):

`/var/www/vhosts/system/git.ltvb.nl/conf/vhost.conf` **and** `.../vhost_ssl.conf`:

```apache
Protocols http/1.1
ProxyPass /cable ws://127.0.0.1:28082/cable
ProxyPassReverse /cable ws://127.0.0.1:28082/cable
```

Requires `proxy_module` + `proxy_wstunnel_module` (already enabled). Apply with:

```
plesk sbin httpdmng --reconfigure-domain git.ltvb.nl
apache2ctl configtest && systemctl reload apache2
```

## 3. Two long-running processes (Passenger runs neither)

Passenger runs only the web process. Install both systemd units:

```
install -o root -g root -m 0644 deploy/git-ltvb-cable.service /etc/systemd/system/git-ltvb-cable.service
install -o root -g root -m 0644 deploy/git-ltvb-jobs.service  /etc/systemd/system/git-ltvb-jobs.service
systemctl daemon-reload && systemctl enable --now git-ltvb-cable git-ltvb-jobs
```

- **git-ltvb-cable** — standalone ActionCable Puma on `127.0.0.1:28082`
  (`cable/config.ru`). This is what Apache proxies /cable to.
- **git-ltvb-jobs** — Solid Queue worker that runs the webhook jobs; without it,
  webhook-triggered broadcasts (pushes, external comments, CI, notifications)
  never fire.

## Verifying

```
# 101 response MUST include "Connection: Upgrade" (Passenger drops it; wstunnel keeps it)
curl -si -H "Origin: https://git.ltvb.nl" -H "Connection: Upgrade" -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  https://git.ltvb.nl/cable | grep -iE "HTTP/|connection|upgrade"
```

A real browser is the only reliable check — curl accepts a 101 that is missing
`Connection`, but Chromium rejects it. The Files tab also polls GitHub's head
sha as a cable-independent fallback for diff refresh.
