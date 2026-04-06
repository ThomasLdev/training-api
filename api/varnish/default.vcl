vcl 4.1;

# Le backend, c'est Caddy/FrankenPHP (le service "php" dans Docker)
backend default {
    .host = "php";
    .port = "80";
}

# ACL : seul le réseau Docker interne peut envoyer des requêtes BAN (purge)
acl invalidators {
    "php";
    "localhost";
    # Réseau Docker par défaut
    "172.16.0.0"/12;
}

# Appelé à chaque requête entrante
sub vcl_recv {
    # ── Invalidation (BAN) ──────────────────────────────────────
    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        if (req.http.ApiPlatform-Ban-Regex) {
            ban("obj.http.Cache-Tags ~ " + req.http.ApiPlatform-Ban-Regex);
            return (synth(200, "Banned"));
        }

        return (synth(400, "ApiPlatform-Ban-Regex header missing"));
    }

    # ── Forwarding headers ──────────────────────────────────────
    set req.http.X-Forwarded-Host = req.http.Host;
    set req.http.X-Forwarded-Proto = "http";
    set req.http.Host = "php";

    # ── Règles de cache ─────────────────────────────────────────
    # On ne cache que les GET et HEAD
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    # On sauvegarde Authorization pour le backend, puis on le retire du hash.
    # Comme ça les resources publiques (s-maxage) sont partagées entre tous les users.
    # Les resources privées ne seront pas cachées (vcl_backend_response respecte "private").
    if (req.http.Authorization) {
        set req.http.X-Saved-Authorization = req.http.Authorization;
        unset req.http.Authorization;
    }

    unset req.http.Cookie;

    return (hash);
}

# Appelé avant d'envoyer la requête au backend (MISS)
sub vcl_backend_fetch {
    if (bereq.http.X-Saved-Authorization) {
        set bereq.http.Authorization = bereq.http.X-Saved-Authorization;
        unset bereq.http.X-Saved-Authorization;
    }
}

# Appelé quand Varnish reçoit la réponse du backend
sub vcl_backend_response {
    if (beresp.http.Cache-Control ~ "no-store") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    if (beresp.http.Cache-Control ~ "private") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    unset beresp.http.Set-Cookie;
}

# Appelé juste avant d'envoyer la réponse au client
sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }

    unset resp.http.Cache-Tags;
}
