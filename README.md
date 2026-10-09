<p align="center"><img src="https://www.myinstants.com/media/apple-touch-icon-114x114.png" alt="MyInstants"></p>
<h1 align="center">MyInstants REST API</h1>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Vercel-Deployed-000000?logo=vercel&logoColor=white" alt="Deployed on Vercel">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License MIT">
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Welcome">
</p>

<p align="center">A RESTful API for scraping and retrieving sound data from the <a href="https://www.myinstants.com" target="_blank">MyInstants</a> website. This API provides endpoints for retrieving information about sounds, including titles, URLs, descriptions, tags, favorites, views, and uploader details.</p>

## ✨ Features

- ⚡ **Ultra Fast**: Powered by Vercel Edge Caching (`s-maxage=3600`) for ~0ms response times on cached requests.
- 🚀 **Serverless Ready**: Native deployment to Vercel without tweaking. Uses separate serverless functions for maximum efficiency.
- 🌐 **CORS Enabled**: Ready to be consumed directly from frontend web applications (React, Vue, etc) without cross-origin issues.
- 🎯 **Reliable Error Handling**: Returns proper HTTP status codes (e.g., 404, 400) instead of just 200 OK.

## Table of Contents

- [Features](#-features)
- [Getting Started](#-getting-started)
  - [Requirements](#requirements)
  - [Installation](#installation)
- [Reference](#%EF%B8%8F-reference)
  - [Endpoints](#endpoints)
  - [Request Parameters](#request-parameters)
  - [Response Example](#response-example)
- [Error Handling](#-error-handling)
- [Examples](#-examples)
- [Contributing](#-contributing)
- [Support](#-support)
- [Credits](#-credits)
- [License & Disclaimer](#%EF%B8%8F-license)

## 🚀 Getting Started

### Requirements

- PHP 7.4 or higher
- [simple_html_dom.php](https://simplehtmldom.sourceforge.io/) library for HTML parsing
- `curl` extension enabled in `php.ini`

### Installation

1. Clone the repository to your server:

    ```bash
    git clone https://github.com/wissam333/myinstants.git
    cd myinstants
    ```

2. Download and place `simple_html_dom.php` in the `lib/` directory.

3. **Local Development (No Apache/Nginx required)**:
   You can run the API locally using PHP's built-in web server. This project includes a `router.php` file that perfectly simulates Vercel's serverless routing environment, allowing you to access endpoints without the `.php` extension.

   ```bash
   php -S localhost:8000 router.php
   ```

   Now you can access the API locally (e.g., `http://localhost:8000/best?q=id`).

4. **Deploy to Vercel**:
   Deploying is simple. Click the button below to deploy this repository directly to your Vercel account.<br>
    [![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fwissam333%2Fmyinstants%2F&redirect-url=https%3A%2F%2Fgithub.com%2Fwissam333%2Fmyinstants%2F)

## ❇️ Reference

### Endpoints

| Request          | Response                 | Parameter  |
| :--------------- | :----------------------- | :--------: |
| `GET /trending`  | Trending based region    | `q`, `page` |
| `GET /search`    | Search a sound           | `q`, `page` |
| `GET /detail`    | The sound details        |    `id`    |
| `GET /recent`    | Recently uploaded sounds |   `page`   |
| `GET /best`      | Best of all time sounds  | `q`, `page` |
| `GET /uploaded`  | User's uploaded sounds   | `username`, `page` |
| `GET /favorites` | User's favorite sounds   | `username`, `page` |
| `GET /category`  | Sounds by category       | `category`, `q`, `page` |
| `GET /memesoundboard` | Sounds from MemeSoundboard.io (search or browse) | `q`, `sort`, `page`, `page_size` |
| `GET /101soundboards` | Sounds from 101Soundboards.com | `q`, `page` |
| `GET /101categories` | 101Soundboards category (tag) list | _none_ |
| `GET /101category` | Boards inside a 101Soundboards category | `tag`, `page` |
| `GET /101board` | Sounds inside a 101Soundboards board | `board`, `page` |
| `GET /search_all` | Merged search across all sources | `q`, `page` |

_All list endpoints (`/trending`, `/search`, `/recent`, `/best`, `/uploaded`, `/favorites`, `/category`, `/memesoundboard`, `/101soundboards`, `/101category`, `/101board`, `/search_all`) support pagination via `?page=N` (default `1`) and duration probing via `?with_duration=1&min_duration=2&max_duration=6`._

_Each item carries a `source` field (`myinstants`, `memesoundboard`, `101soundboards`). `/search_all` interleaves all sources (round-robin) and reports per-source status in `sources`. 101Soundboards items include `thumbnail` and a free `duration` (no probing needed)._

_101Soundboards is two levels deep: a category (tag) page lists **boards**, and each board holds the actual sounds. Use `/101categories` to list the available tags, `/101category?tag=games` to page through boards, then `/101board?board=36000-halo-ringtones` to get the sounds of one board._

_`/memesoundboard` has two modes: pass `?q=` to search by name, or omit `q` to browse the catalog without a query — `sort` selects the feed (`new` = newest, default, `trending`, `all` = full catalog). `page_size` (1–100, default 35) tunes the page size. Example: `/memesoundboard?sort=trending&page=2&page_size=50`._

### Request Parameters

|   Parameter    | Description                                              |
| :------------: | :------------------------------------------------------- |
|      `q`       | Search query or region                                   |
|   `username`   | User's username                                          |
|      `id`      | Sound's Unique ID                                        |
|     `page`     | Page number (>= 1, default `1`). Example: `?page=4`      |
|   `category`   | Category name (required for `/category`, case-insensitive). One of: `anime & manga`, `games`, `memes`, `movies`, `music`, `politics`, `pranks`, `reactions`, `sound effects`, `sports`, `television`, `tiktok trends`, `viral`, `whatsapp audios` |
|     `tag`      | 101Soundboards category tag (required for `/101category`, slug or name). One of: `anime-comics-cartoons`, `celebrities`, `comedy`, `games`, `memes-funny`, `movies`, `music-musicians`, `nature`, `other`, `politics`, `sound-fx`, `sports`, `streamers-twitch-podcasts`, `tv`, `united-kingdom`, `united-states` |
|    `board`     | 101Soundboards board slug, `boards/{id-slug}`, or full URL (required for `/101board`). Example: `?board=36000-halo-ringtones` |
| `with_duration`| `1` to include MP3 `duration` (seconds) per sound        |
| `min_duration` | Only keep sounds >= N seconds (implies `with_duration`)  |
| `max_duration` | Only keep sounds <= N seconds (implies `with_duration`)  |

_Note: `myinstants.com` exposes no durations in its HTML, so `with_duration` / `min_duration` / `max_duration` probe each MP3 and parse the MPEG frame headers. Lists stay fast by default; responses are slower (parallel fetch, best-effort, `duration: null` when undetectable) only when duration params are used._

### Response Example

A typical successful response (HTTP 200) will return a JSON object like this:

```json
{
  "status": 200,
  "author": "wissam333",
  "page": 4,
  "count": 30,
  "total_pages": 50,
  "has_next": true,
  "data": [
    {
      "id": "vine-boom-sound-70972",
      "title": "VINE BOOM SOUND",
      "url": "https://www.myinstants.com/en/instant/vine-boom-sound-70972/",
      "mp3": "https://www.myinstants.com/media/sounds/vine-boom.mp3",
      "duration": 1.42
    }
  ]
}
```

_`page` / `count` / `total_pages` / `has_next` are returned by all list endpoints (`total_pages` is parsed from the upstream `Page X of Y` title, `null` when undetectable). `duration` (seconds) only appears when `with_duration=1` or `min_duration` / `max_duration` is used. `/category` additionally echoes `category` and `region` (`null` when global). `/101category` echoes `tag`, `name`, and `kind: "boards"` (its items are boards, not sounds); `/101board` echoes `board`._

_`source` is `"live"` normally, `"proxy"` when served via your scraper proxy, or `"archive"` when `myinstants.com` blocked the request and the data was served from the latest Wayback Machine snapshot instead (data may be older; `mp3` links still point at the live site, and archive responses are edge-cached for 24h). If no source works, the API returns HTTP `502` — retry later._

> **Anti-bot note:** `myinstants.com` runs Cloudflare bot protection that sometimes blocks datacenter IPs (Vercel) with HTTP 403. Browser headers alone can't pass it, and free forward-proxies (corsproxy.io, allorigins, codetabs…) don't either — they get the same challenge page. The reliable, serverless-friendly fix is a free-tier **scraper API** (a cloud browser that solves the challenge for you). No VPS or extra infrastructure needed:
>
> 1. Sign up for free credits — recommended: [ZenRows](https://www.zenrows.com/) (~2,000 free credits, no card) or [ScrapingBee](https://www.scrapingbee.com/) (~1,000 free credits).
> 2. Copy your API key from their dashboard.
> 3. Set the `UPSTREAM_PROXY_TEMPLATES` env var in Vercel (Dashboard → Settings → Environment Variables) to the template **with the anti-bot flags included** — without them, the plain fetch gets the same Cloudflare block:
>
> ```
> # ZenRows (recommended) — antibot+js_render flags are what defeat Cloudflare:
> UPSTREAM_PROXY_TEMPLATES=https://api.zenrows.com/v1/?apikey=KEY&url={url}&js_render=true&antibot=true&premium_proxy=true
>
> # ScrapingBee — stealth_proxy is their Cloudflare-bypass mode:
> UPSTREAM_PROXY_TEMPLATES=https://app.scrapingbee.com/api/v1/?api_key=KEY&url={url}&render_js=true&stealth_proxy=1
>
> # You can chain several (comma-separated, tried in order) to mix free tiers:
> UPSTREAM_PROXY_TEMPLATES=https://api.zenrows.com/v1/?apikey=KEY1&url={url}&js_render=true&antibot=true,https://app.scrapingbee.com/api/v1/?api_key=KEY2&url={url}&render_js=true&stealth_proxy=1
> ```
>
> (The old singular `UPSTREAM_PROXY_TEMPLATE` still works for a single entry.)
>
> **Credits last longer than they look:** the proxy is only consulted *after* a direct fetch fails with 403/429 (live is always tried first), and every proxied page is edge-cached (see month-long caching below), so one credit can serve many requests. Rough ZenRows math: an antibot request costs ~25 credits → ~80 protected fetches per free signup, stretched much further by caching. Rotate/re-sign-up if you burn through them.
>
> Without any proxy configured, the API automatically falls back to Wayback Machine snapshots when blocked (`source: "archive"`, data may be slightly stale), and the `/memesoundboard` and `/101soundboards` sources are unaffected by myinstants' Cloudflare entirely.
>
> **Month-long caching:** edge-cache TTLs are env-configurable (seconds; `2592000` ≈ 30 days). Proxy/edge hits don't burn proxy credits, so one proxied fetch can serve a URL for a month:
>
> ```
> CACHE_SMAXAGE_LIVE=2592000
> CACHE_SMAXAGE_PROXY=2592000
> CACHE_SMAXAGE_ARCHIVE=2592000
> ```
>
> (Defaults: live/proxy `3600`, archive `86400`. Note: Vercel's edge cache is best-effort — entries can be evicted under pressure and every redeploy purges it, causing a fresh round of upstream fetches. Cache keys include the full query string, so each `page`/filter combo is cached separately.)

_Note: For the `/detail` endpoint, the `data` object will contain extra fields like `description`, `tags`, `favorites`, `views`, and `uploader` (plus `duration` when `?with_duration=1` is passed)._

## 💥 Error Handling

All errors return JSON objects with an appropriate HTTP status code (e.g., 404, 400) and a `message` explaining the issue.

- **404 Error**:
  - When the page is not found or an invalid endpoint is accessed.
  ```json
  {
    "status": 404,
    "author": "wissam333",
    "message": "Endpoint not found"
  }
  ```

- **502 Error**:
  - When `myinstants.com` refuses the request (HTTP 403/429 anti-bot protection). Retry later — cached responses still work.
  ```json
  {
    "status": 502,
    "author": "wissam333",
    "message": "Upstream myinstants.com refused this request (HTTP 403, anti-bot protection). Please retry later."
  }
  ```

## 🌐 Examples

### Example 1: Get Trending Sounds by Region

```http
GET https://myinstants-api.vercel.app/trending?q=id
```

### Example 2: Search Sounds by Query

```http
GET https://myinstants-api.vercel.app/search?q=laugh
```

### Example 3: Get Sound Details by ID

```http
GET https://myinstants-api.vercel.app/detail?id=akh-26815
```

### Example 4: Get Recently Uploaded Sounds

```http
GET https://myinstants-api.vercel.app/recent
```

### Example 5: Get Best of All Time Sounds

Retrieve a list of the most popular sounds of all time based on a specified region:

```http
GET https://myinstants-api.vercel.app/best?q=id
```

### Example 6: Get User's Uploaded Sounds

```http
GET https://myinstants-api.vercel.app/uploaded?username=hellmouz
```

### Example 7: Get User's Favorite Sounds

```http
GET https://myinstants-api.vercel.app/favorites?username=hellmouz
```

### Example 8: Paginate Any List (e.g. Trending Syria, Page 4)

Mirrors https://www.myinstants.com/en/index/sy/?page=4 — page 1 is the default and returns the same shape as before:

```http
GET https://myinstants-api.vercel.app/trending?q=sy&page=4
GET https://myinstants-api.vercel.app/search?q=laugh&page=2
GET https://myinstants-api.vercel.app/recent?page=2
```

### Example 9: Only 2–6 Second Sounds

```http
GET https://myinstants-api.vercel.app/search?q=laugh&min_duration=2&max_duration=6
GET https://myinstants-api.vercel.app/trending?q=sy&page=4&min_duration=2&max_duration=6
GET https://myinstants-api.vercel.app/search?q=laugh&with_duration=1
```

### Example 10: Get Sounds by Category (with Optional Region + Pagination)

Mirrors https://www.myinstants.com/en/categories/anime%20&%20manga/sy/?page=9 — `q` (region) is optional; omit it for the global listing:

```http
GET https://myinstants-api.vercel.app/category?category=music
GET https://myinstants-api.vercel.app/category?category=anime%20%26%20manga&q=sy&page=9
GET https://myinstants-api.vercel.app/category?category=memes&q=sy&page=2&min_duration=2&max_duration=6
```

### Example 11: Search Other Sound Sites (Merged or Per-Source)

```http
GET https://myinstants-api.vercel.app/search_all?q=bruh&page=1
GET https://myinstants-api.vercel.app/memesoundboard?q=bruh&page=2
GET https://myinstants-api.vercel.app/101soundboards?q=bruh&page=2&min_duration=2&max_duration=6
```

### Example 12: Browse MemeSoundboard Without a Query (newest / trending / all)

```http
GET https://myinstants-api.vercel.app/memesoundboard
GET https://myinstants-api.vercel.app/memesoundboard?sort=trending&page=2
GET https://myinstants-api.vercel.app/memesoundboard?sort=all&page_size=50
```

### Example 13: Browse 101Soundboards by Category (tags -> boards -> sounds)

```http
GET https://myinstants-api.vercel.app/101categories
GET https://myinstants-api.vercel.app/101category?tag=games&page=1
GET https://myinstants-api.vercel.app/101board?board=36000-halo-ringtones
```

## 🌱 Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature-name`.
3. Commit your changes: `git commit -m 'Add feature'`.
4. Push to the branch: `git push origin feature-name`.
5. Submit a pull request.

## ✨ Support

If you like this project, please star on this repository, thank you ⭐

### Star History

<a href="https://www.star-history.com/?repos=wissam333%2Fmyinstants&type=date&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/chart?repos=wissam333/myinstants&type=date&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/chart?repos=wissam333/myinstants&type=date&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/chart?repos=wissam333/myinstants&type=date&legend=top-left" />
 </picture>
</a>

## 🙏 Credits

- Original project by [abdipr](https://github.com/abdipr/myinstants-api).
- Updated and maintained by [wissam333](https://github.com/wissam333/myinstants).

## ⚖️ License

This project is licensed under the `MIT License`. See the [LICENSE](https://github.com/wissam333/myinstants/blob/main/LICENSE) file for more information.

## ⚠️ Disclaimer

The sounds contained in this API are obtained from the original [MyInstants](https://www.myinstants.com) website by web scraping. Developers using this API must follow the applicable regulations by mentioning this project or the official owner in their projects and are prohibited from abusing this API for personal benefits.

[⬆️ Back to Top](#myinstants-rest-api)
