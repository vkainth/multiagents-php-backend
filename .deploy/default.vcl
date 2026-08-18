vcl 4.0;

backend default {
    .host = "162.213.156.4";
    .port = "8080";
}

sub vcl_recv {
    set req.http.Host = regsub(req.http.Host, ":[0-9]+", "");

    if (req.url ~ "^/(admin|dashboard|login|logout|handle_auth|verify-email|resend-verification|register|api|agent|user|bcch-admin|livewire|verify|otp|phone|confirm|complete-profile|profile|account|saved|favourites|contact|mortgage|ab-log|wishlist|schedule|request|listing)") {
        return (pass);
    }

    # Bypass cache for logged-in users
    if (req.http.Cookie ~ "logged_in=1") {
        return (pass);
    }

    # Bypass cache for anyone with a Laravel session (prevents stale CSRF tokens)
    if (req.http.Cookie ~ "bccondosandhomes_session=") {
        return (pass);
    }

    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }


    unset req.http.Cookie;
    return (hash);
}

sub vcl_backend_response {
    if (bereq.url ~ "^/(admin|dashboard|login|logout|handle_auth|verify-email|resend-verification|register|api|agent|user|bcch-admin|livewire|verify|otp|phone|confirm|complete-profile|profile|account|saved|favourites|contact|mortgage|ab-log|wishlist|schedule|request|listing)") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    # Only strip cookies on cacheable URLs (not auth/login responses)
    if (bereq.url ~ "^/(building|neighbourhood|market-report|search-listings|sold|search)" && bereq.url !~ "handle_auth" && bereq.url !~ "/login") {
        unset beresp.http.Set-Cookie;
    }
    unset beresp.http.Cache-Control;
    unset beresp.http.Pragma;
    unset beresp.http.Expires;
    unset beresp.http.Vary;

    if (beresp.status >= 400) {
        set beresp.uncacheable = true;
        set beresp.ttl = 30s;
        return (deliver);
    }

    # Current and previous month market reports - 6 hours
    if (bereq.url ~ "^/market-report" && bereq.url ~ "may-2026|april-2026") {
        set beresp.ttl = 6h;
        set beresp.grace = 2h;
        set beresp.http.Cache-Control = "public, max-age=21600";
        return (deliver);
    }

    # Historic market reports - never changes - 30 days
    if (bereq.url ~ "^/market-report") {
        set beresp.ttl = 30d;
        set beresp.grace = 24h;
        set beresp.http.Cache-Control = "public, max-age=2592000";
        return (deliver);
    }

    # Neighbourhood pages - 3 days
    if (bereq.url ~ "^/neighbourhood") {
        set beresp.ttl = 3d;
        set beresp.grace = 24h;
        set beresp.http.Cache-Control = "public, max-age=259200";
        return (deliver);
    }

    # Building pages - 24 hours
    if (bereq.url ~ "^/building") {
        set beresp.ttl = 24h;
        set beresp.grace = 12h;
        set beresp.http.Cache-Control = "public, max-age=86400";
        return (deliver);
    }

    # Sold listings search - never changes - 30 days
    if (bereq.url ~ "listing_status=sold") {
        set beresp.ttl = 30d;
        set beresp.grace = 24h;
        set beresp.http.Cache-Control = "public, max-age=2592000";
        return (deliver);
    }

    # Search results - 1 hour
    if (bereq.url ~ "^/search-listings") {
        set beresp.ttl = 1h;
        set beresp.grace = 2h;
        set beresp.http.Cache-Control = "public, max-age=3600";
        return (deliver);
    }

    # Everything else - 2 hours
    set beresp.ttl = 2h;
    set beresp.grace = 4h;
    set beresp.http.Cache-Control = "public, max-age=7200";
    return (deliver);
}

sub vcl_deliver {
    if (req.url ~ "^/(admin|dashboard|login|logout|handle_auth|verify-email|resend-verification|register|api|agent|user|bcch-admin|livewire|verify|otp|phone|confirm|complete-profile|profile|account|saved|favourites|contact|mortgage|ab-log|wishlist|schedule|request|listing)") {
        # Do NOT strip Set-Cookie for auth/user/listing routes
    } else if (req.http.Cookie ~ "logged_in=1") {
        # Do not strip Set-Cookie for logged-in users
    } else {
        # Only strip cookies when serving from cache
        if (obj.hits > 0) {
            unset resp.http.Set-Cookie;
        }
    }
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
    set resp.http.X-Cache-Hits = obj.hits;
}
