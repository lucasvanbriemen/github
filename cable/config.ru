# Standalone ActionCable server, run as its own process (see
# deploy/git-ltvb-cable.service). Apache+Passenger strips the hop-by-hop
# `Connection: Upgrade` header from the 101 WebSocket upgrade response, which
# browsers reject ("'Connection' header is missing"). Apache instead proxies
# /cable to this process via mod_proxy_wstunnel, which preserves the upgrade
# headers. See deploy/README-cable.md.
require_relative "../config/environment"

Rails.application.eager_load!
run ActionCable.server
