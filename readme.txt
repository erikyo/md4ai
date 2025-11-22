=== md4AI ===
Contributors: codekraft
Tags: GEO, AI, SEO, markdown, llms.txt
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimise content for generative engines (GEO) by serving custom Markdown and a site-wide llms.txt.

== Description ==

The **md4AI** plugin provides a powerful, multi-layered approach to Generative Engine Optimization (GEO).

### 1. Automatic AI Bot Detection (Zero-Config)

md4AI automatically detects requests from a comprehensive list of known AI agents (like GPTBot, ClaudeBot, Google-Extended, etc.) and serves them a lightweight, on-the-fly Markdown version of your content instead of the standard HTML theme. This ensures a clean, parsable data feed for LLMs right out of the box.

### 2. Per-Post Custom Markdown (Metabox)

The plugin adds a new metabox to your post and page editor, giving you full control over the AI-optimized version of your content.
Serving an md version of the website is particular useful if your website has editors like Elementor / Beaver Builder / Divi Builder / other div bloated editors because bots usually skips complex and too much nested content.

* **Generate Custom Markdown:** Convert your post's content into Markdown with one click.
* **Manually Edit:** Modify and enhance the Markdown to be exactly as you want AIs to see it.
* **(Optional) Enhance with AI:** If you also use the **[AI Services](https://wordpress.org/plugins/ai-services/)** plugin, you can use the "Generate with AI" button to automatically add FAQs, discussion questions, or key takeaways extracted from your post.
* **Serve Enhanced Content:** When an AI bot visits this *specific post*, it will be served your new, custom-tailored Markdown version, giving it far richer data.
* **Automatic Fallback:** If you don't create a custom version, the plugin falls back to the automatic on-the-fly conversion.

### 3. Site-Wide AI Instructions (llms.txt)

The plugin adds a new main page under 'Utilities' with dedicated tabs for configuration and insights. From here, you can create and manage a `llms.txt` file for your entire site.

* **What is `llms.txt`?** This is a file, similar to `robots.txt`, that provides general instructions, context, and useful links to AI crawlers. It's a new standard to help guide LLMs in understanding your site's content and purpose.
* **Manage Content:** Use the settings page to write your `llms.txt` content, which will then be served automatically to bots that look for it.
* **Learn More:** You can find more details on this new standard at [https://llmstxt.org/](https://llmstxt.org/).

This combination gives you full control, from automatic optimization to granular, AI-enhanced, per-post content and site-wide AI directives.

### 4. GEO/AIO Insights & Validation

The dedicated GEO/AIO Insights tab provides a unique validation mechanism for your site's AI presence, offering measurable scores (0-100) on how well your brand is understood by Generative Engines.

* **The Validation Process**: We ask a Large Language Model (LLM) di completare una "scheda di conoscenza" sul tuo sito (ad esempio, il nome del brand, gli argomenti principali e le categorie di prodotto).
* **Ground Truth Check**: La risposta dell'AI viene immediatamente confrontata con i dati reali estratti dal tuo database WordPress (ad esempio, il titolo del sito, i nomi degli autori, le categorie di WooCommerce).
* **Final Score**: In base a questa controverifica, l'insight fornisce un punteggio per l'identità del brand e la rilevanza tecnica, evidenziando esattamente dove l'AI ha fornito dati errati o insufficienti (allucinazioni o dati mancanti).

== Installation ==

1.  Upload the `md4ai` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  **(Optional but Recommended):** Install and configure the **[AI Services](https://wordpress.org/plugins/ai-services/)** plugin to enable the "Generate with AI" features in the post metabox.
4.  The automatic bot detection is now live.
5.  Go to the new **"md4AI" options page** in your dashboard (usually under 'Settings') to configure your site-wide `llms.txt` file.
6.  Go to any post or page edit screen to find the **"md4AI Custom Markdown"** metabox to customize content for that specific page.

== Frequently Asked Questions ==

= What is md4AI? =

md4AI is a plugin that optimizes your website for Generative Engines (GEO) by serving custom Markdown and a site-wide llms.txt file.

= What is GEO? =

GEO is a new standard for Generative Engine Optimization (GEO) that helps guide LLMs in understanding your site's content and purpose.

= How does it detect an AI bot? =

The plugin checks the incoming HTTP `User-Agent` string against an internal, pre-defined list of known AI and large language model (LLM) crawlers (e.g., gptbot, claudebot, google-extended, perplexitybot, etc.).

= How does the AI Services integration work? =

If you have the `ai-services` plugin installed, md4AI adds a "Generate with AI" button to its **post metabox**. This takes the generated Markdown for that post and sends it, along with a prompt, to your chosen AI provider. The AI-enhanced text (e.g., your post *plus* a new FAQ section) then replaces the content in the metabox, ready to be served to bots.

= What's the difference between the Custom Markdown and the llms.txt file? =

They serve two different, important purposes:

* **Custom Markdown (Per Post/Page):** This is controlled via a **metabox** on each post/page. It allows you to create a specific, enhanced Markdown *version of that page's content*. This is what an AI bot will receive *instead* of the HTML when it crawls that specific URL.
* **llms.txt (Site-Wide):** This is controlled from the plugin's main **options page**. It's a *single file* for your entire site, similar to `robots.txt`. It doesn't contain post content, but rather gives *general instructions* and context to AI crawlers (e.g., site purpose, useful links, etc.). For more details, see [https://llmstxt.org/](https://llmstxt.org/).

= What's the difference between 'Automatic' and 'Manual' Markdown? =

This applies to your posts and pages:

* **Automatic:** If you do nothing, the plugin automatically converts your post's HTML to Markdown on-the-fly when a bot visits.
* **Manual:** If you use the metabox to "Generate" or "Generate with AI," you create a *static* version that is saved to your database. This *saved* version will be served to bots instead. This allows you to add enhanced content (like FAQs) that only the AI sees.

= Does this affect regular search engine optimization (SEO)? =

No. Regular users and traditional search engine crawlers (like standard Googlebot) will continue to receive your standard HTML page. This functionality is specifically targeted at AI agents.

= Are there any performance concerns? =

No. For automatic detection, the plugin runs early and immediately stops execution (`exit;`) after serving the Markdown content. For manual generation, the content is pre-generated and saved, so serving it is extremely fast.

== Changelog ==

= 1.1.0 =
- Enhanced admin UI
- GEO insights page
- Added crawler stats
- Added integration with the AI Services plugin.
- Added "Generate with AI" functionality to enhance content using prompts (e.g., add FAQs, summaries).
- Implemented REST API endpoints for generating base .md and .txt content.

= 1.0.0 =
- Added metaboxes to the post editor to manually generate and store custom Markdown (.md) file.
- The plugin now serves the custom, manually-generated content to bots if it exists, falling back to the automatic conversion if not.
- Initial release of the md4AI plugin.
- Includes a list of 14 known AI and search bots.
- Implements basic HTML to Markdown conversion for optimal AI consumption.
