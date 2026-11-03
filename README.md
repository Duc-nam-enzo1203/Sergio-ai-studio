# Sergio AI Studio

> **Sergio AI Studio** is an advanced autonomous AI content engine and On-Page SEO optimization suite for WordPress. Powered by Google Gemini AI, it combines cutting-edge **Generative Engine Optimization (GEO/AEO)**, **Claude-SEO** methodologies, and seamless deep integration with **Rank Math SEO (Free & PRO)**.

---

## 🌟 Key Features

### 1. Generative Engine Optimization (GEO/AEO) & Claude-SEO
- **Answer-First Hook:** Injects a direct, 2–3 sentence answer containing the Primary Focus Keyword within the opening passage to maximize citation probability across Google AI Overviews, Perplexity, and ChatGPT Search.
- **Key Takeaways Box:** Prominently displays bulleted executive highlights right at the top of every generated article.
- **Semantic Hierarchy (H2, H3):** Enforces a logically layered heading structure enriched with LSI entities and search-intent variants.
- **Structured Semantic FAQ:** Automatically generates high-intent FAQ sections formatted for Schema.org and Google "People Also Ask" (PAA) rich snippets.
- **Automated Table of Contents (TOC):** Dynamically inserts an in-page navigation block with semantic anchor links.
- **Contextual Image ALT Tags:** Equips all attached media with keyword-rich, natural-language descriptive ALT text.

### 2. Deep Research Fact Pack Engine (Zero Hallucination)
- **Real-Time Web Grounding:** Harnesses Google Search Grounding to verify real-world facts, dates, events, and metrics before writing.
- **Strict Verification Gate:** Rejects speculation and hallucinated claims; requires cross-referenced multi-source verification.
- **Automated Source Attribution:** Appends an optional transparent source citation box at the bottom of articles with full publisher credits.

### 3. Autonomous WP-Cron Scheduler & Smart Queue
- **Flexible Scheduling:** Automates background content production on hourly, twice-daily, daily, or custom-hour intervals.
- **Hybrid Topic Strategy:** Seamlessly draws from your curated topic queue, automatically falling back to AI niche trend discovery when the queue is exhausted.
- **Publication Safety Invariants:** Allows configuring default post status (`draft`, `pending`, or `publish`), ensuring editorial review prior to public release.
- **Smart Rate-Limiting & Quotas:** Enforces daily output caps and per-run batch limits to prevent server timeouts and API budget overruns.

### 4. Royalty-Free Stock Photo Suite
- **Wikimedia Commons Integration:** Built-in by default with **zero API key required** and **100% free usage**, extracting high-resolution media licensed under Creative Commons (CC-BY) and Public Domain.
- **Optional High-Res Unsplash & Pexels APIs:** Seamlessly connects with Unsplash Access Key and Pexels API Key for artistic, studio-grade featured images.
- **Intelligent Fallback Pipeline:** Gracefully cascades from reference article extraction to stock media libraries, ensuring every post has a valid Featured Image.

### 5. Comprehensive On-Page SEO Audit & Workbench
- **Site-Wide Health Audit:** Analyzes both Posts and Pages against a strict 14-point Rank Math SEO On-Page checklist.
- **1-Click AI Metadata & Content Enhancements:** Generates high-CTR SEO titles, meta descriptions, focus keyword suggestions, and body content expansions.
- **SERP Google Simulator:** Real-time visual preview of Google Search results with character count validation.

### 6. Web Crawler & Content Synthesizer (Crawl & Rewrite)
- **Clean DOM Extraction:** Extracts pure content while discarding sidebars, banners, and navigation menus.
- **Strict SSRF Protection:** Blocks private IP ranges (127.0.0.1, 10.x, 192.168.x), loopback requests, and cloud metadata endpoints.
- **Autonomous Editorial Rewriting:** Re-synthesizes source articles from fresh analytical angles with transparent source credit.

### 7. Enterprise Security & Architecture
- **Authenticated Encryption:** All API keys are encrypted at rest using **AES-256-CBC + HMAC-SHA256 (Encrypt-then-MAC)** derived from WordPress core salt keys.
- **Config Constant Support:** Allows locking sensitive credentials via `wp-config.php` (`SERGIO_AI_GEMINI_API_KEY`).
- **Native WordPress Admin UI:** Zero heavyweight JavaScript frameworks; built strictly using WordPress Native Admin Design System (`.wrap`, `.postbox`, `.form-table`, `.wp-list-table`).
- **Modular View Templates:** Clean MVC template separation located in `templates/` for effortless maintenance and extensibility.

---

## 🚀 Installation

1. Upload the plugin folder to your WordPress plugins directory:
   ```
   wp-content/plugins/sergio-ai-studio
   ```
2. Activate the plugin via the **Plugins** screen in the WordPress Admin Dashboard.
3. Navigate to **Sergio AI Studio** > **Settings** and enter your **Google Gemini API Key**.
4. *(Optional)* Secure your API key directly in `wp-config.php`:
   ```php
   define( 'SERGIO_AI_GEMINI_API_KEY', 'AIzaSy...' );
   ```

---

## 🛠️ Codebase Structure

```
sergio-ai-studio/
├── assets/
│   ├── css/
│   │   └── admin-studio.css        # Clean, modern WordPress Native UI styles
│   └── js/
│       └── admin-studio.js         # Real-time AJAX workbench coordination
├── includes/
│   ├── class-admin.php             # Core router, settings registration & AJAX handlers
│   ├── class-article-writer.php    # Long-form article generation & GEO formatting
│   ├── class-content-rewriter.php  # Source synthesis & rewrite engine
│   ├── class-db.php                # Custom tables migration & lifecycle management
│   ├── class-fact-pack-engine.php  # Deep Research web grounding & fact verification
│   ├── class-gemini-client.php     # Resilient Gemini API client with model cascading
│   ├── class-image-service.php     # Royalty-free stock photo & reference image pipeline
│   ├── class-logger.php            # Native audit logging & stage mapping
│   ├── class-rankmath-bridge.php   # Bidirectional Rank Math Free & PRO bridge
│   ├── class-scheduler.php         # WP-Cron background executor & queue manager
│   ├── class-security.php          # AES-256-CBC encryption & SSRF sanitization
│   ├── class-seo-audit.php         # 14-point Rank Math On-Page audit scoring engine
│   ├── class-topic-analyzer.php    # Intent analysis & editorial outline planner
│   └── class-web-crawler.php       # Safe DOM content scraper with SSRF defense
├── templates/
│   ├── tab-crawl.php               # Crawl & Rewrite view template
│   ├── tab-logs.php                # Execution audit logs view template
│   ├── tab-overview.php            # Quick stats & overview dashboard template
│   ├── tab-scheduler.php           # Cron scheduling & queue workbench template
│   ├── tab-seo.php                 # Rank Math SEO audit & optimizer template
│   ├── tab-settings.php            # API keys & model configuration template
│   └── tab-writer.php              # Fact Pack Deep Research & writing template
├── .gitignore
├── README.md                       # Documentation
├── sergio-ai-studio.php            # Main plugin bootstrap
└── uninstall.php                   # Safe database cleanup uninstaller
```

---

## ⚙️ Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 to 8.3+ (Fully compatible with PHP 8.2 and 8.3)
- **Required Extensions:** `curl`, `openssl`, `mbstring`, `json`
- **Recommended Plugin:** [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) (Free or PRO)

---

## 📄 License
Developed with care by **Sergio Themes & AI Team**.  
Licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
