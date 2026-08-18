# GSC Bulk URL Removal — Filtered Search Pages

## Background

Google crawled approximately **144,560 filtered search URLs** (e.g.
`/search-listings/vancouver?beds=2&pricefrom=500000`) before robots.txt
restrictions and `noindex` meta tags were in place. Even with those signals
now live, already-indexed URLs linger in Search Console's Coverage report until
Google re-processes each one individually — which can take months.

A **Temporary Removal** request in Google Search Console speeds up that
re-processing. However, GSC's removal tool does not support wildcard patterns
(like `/search-listings/*?*`). The two practical options are described below,
with the recommended approach highlighted.

---

## Robots.txt Status

The following broad rules are now in place in `laravel-app/public/robots.txt`
(updated April 2026) for both `User-agent: *` and `User-agent: Googlebot`:

```
Disallow: /search-listings?
Disallow: /search-listings/*?
```

These two lines cover every parameterised URL under `/search-listings/`
regardless of which query-string parameters are present. Clean city-pages
(e.g. `/search-listings/vancouver`) have no query string and are unaffected.

The `noindex,follow` meta tag in `default.blade.php` is also already live for
all `search-listings*` pages with query parameters.

---

## GSC Removal Options — Action Required by Property Owner

> **These steps must be completed manually by the property owner in Google Search Console.**
> They cannot be automated without OAuth access to the GSC property.

### Option A — Recommended: Rely on noindex + robots.txt signals (no removal request)

GSC's temporary removal tool does not support wildcard query-string patterns.
A prefix removal (`/search-listings/`) would **also temporarily remove clean
city-pages** (e.g. `/search-listings/vancouver`, `/search-listings/burnaby`)
from the index for up to 6 months — an unacceptable trade-off.

The safest path is to let Google process the existing `noindex` meta tags and
robots.txt disallow rules organically:

- Google typically re-processes disallowed/noindexed URLs within **4–12 weeks**
  after the signals are confirmed in GSC.
- Use **Inspect URL** in GSC for a handful of sample filtered URLs to confirm
  Google sees the `noindex` tag. If it does, no removal request is needed.

**Timeline:** Coverage report "Excluded: noindex" count should decline measurably
within 4–8 weeks. Check weekly.

---

### Option B — Targeted removal for high-priority filtered URLs only

If specific filtered URLs appear in Google search results and need immediate
removal, submit them individually:

1. Log in to [Google Search Console](https://search.google.com/search-console)
   and select the `bccondosandhomes.com` property.
2. Navigate to **Removals → New Request → Temporary removal**.
3. Choose **"Remove this URL only"**.
4. Paste the specific URL (e.g. `https://www.bccondosandhomes.com/search-listings/vancouver?beds=2`).
5. Submit. Repeat for any other high-priority URLs.

This keeps clean city-pages in the index while clearing individual filtered URLs.

---

## ⚠️ What NOT to do

Do **not** submit a prefix removal for `https://www.bccondosandhomes.com/search-listings/`
using "Remove all URLs with this prefix." This would temporarily deindex
**all** pages under that path — including the clean, unfiltered city-level
search pages — for up to 6 months.

---

## Monitoring

Check the **Coverage** report in Search Console weekly:

| Metric to watch | Expected trend |
|---|---|
| Excluded › Crawled — currently not indexed | Decreasing over 4–8 weeks |
| Excluded › Excluded by 'noindex' tag | Decreasing over 4–8 weeks |
| Valid pages (indexed) | Stable or slight increase |

If the count does not drop within 8 weeks, use the URL Inspection tool to
verify Google is seeing the `noindex` tag on filtered pages and confirm
robots.txt is being fetched correctly (check the "robots.txt" report in GSC).

---

## Related files

- `laravel-app/public/robots.txt` — Disallow rules for all bots
- `laravel-app/resources/views/frontend/layouts/default.blade.php` — `noindex,follow`
  meta tag injected for all `search-listings*` pages that have query parameters
