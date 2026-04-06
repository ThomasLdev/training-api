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
    # API Platform envoie des requêtes BAN quand une ressource est modifiée.
    # Le header "ApiPlatform-Ban-Regex" contient une regex des IRIs à purger.
    # Ex: après PATCH /courses/abc, API Platform envoie:
    #   BAN / HTTP/1.1
    #   ApiPlatform-Ban-Regex: (^/courses/abc$)|(^/courses$)
    # Varnish supprime alors du cache toutes les réponses qui matchent.
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
    # Varnish parle HTTP à Caddy. Caddy écoute en HTTP sur "php:80".
    # On sauvegarde le vrai Host pour que Symfony le connaisse via X-Forwarded-Host,
    # puis on met Host = "php" pour matcher le listener HTTP de Caddy.
    set req.http.X-Forwarded-Host = req.http.Host;
    set req.http.X-Forwarded-Proto = "http";
    set req.http.Host = "php";

    # ── Règles de cache ─────────────────────────────────────────
    # On ne cache que les GET et HEAD
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    # Si la requête a un header Authorization, on ne cache pas (cache privé)
    if (req.http.Authorization) {
        return (pass);
    }

    # On supprime les cookies pour permettre le cache des requêtes GET publiques.
    # Les cookies du profiler Symfony (debug) empêcheraient sinon tout caching.
    unset req.http.Cookie;

    # On cherche dans le cache
    return (hash);
}

# Appelé quand Varnish reçoit la réponse du backend
sub vcl_backend_response {
    # Si le backend dit "ne pas cacher" (private, no-store), on obéit
    if (beresp.http.Cache-Control ~ "private" || beresp.http.Cache-Control ~ "no-store") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    # On supprime les Set-Cookie des réponses cacheables.
    # Le profiler Symfony envoie des cookies de debug qu'on ne veut pas en cache.
    unset beresp.http.Set-Cookie;

    # Varnish utilise automatiquement s-maxage du header Cache-Control
    # pour déterminer combien de temps garder la réponse en cache.
}

# Appelé juste avant d'envoyer la réponse au client
sub vcl_deliver {
    # Headers de debug : permet de voir si c'est un HIT ou un MISS
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }

    # Le header "Age" est ajouté automatiquement par Varnish.
    # Il indique depuis combien de secondes l'objet est en cache.

    # Cache-Tags est utilisé en interne par Varnish pour l'invalidation BAN.
    # Le client n'en a pas besoin — on le retire pour réduire la taille des réponses.
    unset resp.http.Cache-Tags;
}
