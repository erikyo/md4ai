=== AI Bot Markdown Server ===
Contributors: yourname
Tags: ai, markdown, bot, seo, gpt, google, crawlers
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serves a clean, structured Markdown version of your posts directly to AI and search bots, optimizing content retrieval.

== Description ==

The **AI Bot Markdown Server** plugin automatically detects requests from a comprehensive list of known AI agents (like GPTBot, ClaudeBot, Google-Extended, etc.) and serves them a lightweight, structured Markdown version of your content instead of the standard HTML theme.

This is a powerful tool for modern SEO and AI optimization:

* **Clarity and Structure:** Provides AI models with content that is easy to parse, clean of theme-specific HTML, JavaScript, and CSS.
* **Faster Processing:** Reduces the load on AI crawlers by sending only the essential textual data in Markdown format.
* **Optimized Indexing:** Ensures that AI bots and LLMs ingest the most coherent and structured version of your single posts and pages.
* **Zero Configuration:** Install and activate. The plugin works automatically in the background by checking the HTTP User-Agent.

The Markdown output includes the post title, URL, publication date, author, content (converted from HTML), categories, and tags.

== Installation ==

1.  Upload the `ai-bot-markdown-server` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  There are no settings. The plugin works immediately upon activation.

== Frequently Asked Questions ==

= How does it detect an AI bot? =

The plugin checks the incoming HTTP `User-Agent` string against an internal, pre-defined list of known AI and large language model (LLM) crawlers (e.g., gptbot, claudebot, google-extended, perplexitybot, etc.).

= Does this affect regular search engine optimization (SEO)? =

No. Regular users and traditional search engine crawlers (like standard Googlebot) will continue to receive your standard HTML page. This functionality is specifically targeted at AI agents that are known to utilize data for LLMs and generative AI.

= What content is included in the Markdown file? =

The plugin outputs:
* Post Title (as an H1)
* Meta (URL, Date, Author)
* The main Post Content (converted from HTML to Markdown)
* A list of Categories and Tags (as H2 headers)

= Are there any performance concerns? =

No. The plugin runs early in the request process and immediately stops execution (`exit;`) after serving the Markdown content, preventing the loading of the full WordPress theme and template files for the detected bot.

== Changelog ==

= 1.0.0 =
* Initial release of the AI Bot Markdown Server plugin.
* Includes a list of 14 known AI and search bots.
* Implements basic HTML to Markdown conversion for optimal AI consumption.
