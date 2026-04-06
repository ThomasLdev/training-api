vcl 4.1;

backend default {
    .host = "php";
    .port = "80";
}

acl invalidators {
    "php";
    "localhost";
    "172.16.0.0"/12;
}

sub vcl_recv {
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

    set req.http.X-Forwarded-Host = req.http.Host;
    set req.http.X-Forwarded-Proto = "http";
    set req.http.Host = "php";

    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    if (req.http.Authorization) {
        set req.http.X-Saved-Authorization = req.http.Authorization;
        unset req.http.Authorization;
    }

    unset req.http.Cookie;

    return (hash);
}

sub vcl_backend_fetch {
    if (bereq.http.X-Saved-Authorization) {
        set bereq.http.Authorization = bereq.http.X-Saved-Authorization;
        unset bereq.http.X-Saved-Authorization;
    }
}

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

sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }

    unset resp.http.Cache-Tags;
}
