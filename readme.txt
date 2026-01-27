=== Gov Hybrid Translator ===
Contributors: govtechteam
Tags: translation, hybrid, glossary, government, multilingual, gutenberg, elementor, avada
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 2.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A hybrid translation system (Manual + AI) with Glossary support, Gutenberg blocks, and Elementor page builder translation.

== Description ==

Gov Hybrid Translator is designed to streamline the translation workflow for government websites. It uses a Meta-based architecture for storing translations alongside the original content, without creating duplicate posts.

**Key Features:**

*   **Glossary System**: Manage official terms (Person, Position, Unit) via a Custom Post Type.
*   **AI-Powered Translation**: Automatic translation using AI with glossary term replacement.
*   **Gutenberg Support**: Parse and translate Gutenberg block content while preserving structure.
*   **Elementor Support**: Parse and translate Elementor widget data.
*   **Auto-Translate on Publish**: Automatically translate when publishing new content.
*   **Comparison View**: Side-by-side Thai vs English comparison for all translations.
*   **View Translation Modal**: Quick preview of translated content with copy functionality.
*   **Routing**: Supports `example.go.th/en/` URL structure.
*   **Language Switcher**: Configurable language switcher with multiple display options.
*   **Multi-Language Support**: English, Chinese, Japanese, Korean, German, French.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/gov-hybrid-translator` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to "Gov Glossary" to start adding terms.
4.  Configure settings in "Gov Translator" menu.
5.  (Optional) Enable Auto-Translate in Settings → Content & SEO.

== Changelog ==

= 2.3.0 =
*   **NEW**: Delete Translation button in Review Content modal.
*   **NEW**: Custom HTML Block (`core/html`) translation support.
*   **FIXED**: Complex HTML structure (timeline, nested divs) losing elements during translation.
*   **IMPROVED**: Smart HTML translation using DOM-based text extraction for complex content.
*   **IMPROVED**: Added translateHtmlDom() method for better HTML structure preservation.
*   **SECURITY**: Removed all debug log statements (console.log, error_log).

= 2.2.0 =
*   **NEW**: View Original/Translated tabs in Review Content modal.
*   **NEW**: Avada Theme Builder header/footer rendering for translated pages.
*   **FIXED**: Header/footer missing on translated internal pages.
*   **FIXED**: 404 errors on translated page URLs.

= 2.1.0 =
*   **FIXED**: View Logs and Clear Logs buttons not responding in Advanced Settings.
*   **FIXED**: Dashboard statistics showing mock data instead of real data.
*   **FIXED**: Language switcher TH button not working on English pages.
*   **IMPROVED**: Dashboard now displays real statistics from database (Total Translations, Glossary Terms, AI Credits Used, TM Hit Rate, Language Distribution, Monthly Trends, Top Categories, Recent Translations).
*   **IMPROVED**: View Logs modal with dark theme terminal-like display.
*   **IMPROVED**: Clear Logs functionality with confirmation dialog.

= 2.0.0 =
*   **NEW**: Gutenberg block translation parser - preserves block structure.
*   **NEW**: Elementor widget translation parser - supports complex widgets.
*   **NEW**: Auto-Translate on Publish feature with configurable settings.
*   **NEW**: TH ↔ EN Comparison Tab - view all translations side-by-side.
*   **NEW**: View Translation Modal - quick preview with copy functionality.
*   **NEW**: Target language selector in Review Content modal.
*   **NEW**: Multi-language support (EN, ZH, JA, KO, DE, FR).
*   **IMPROVED**: Post Contents and Page Contents tabs show English Excerpt.
*   **IMPROVED**: Content Reviewer with better glossary term detection.
*   **IMPROVED**: Translation feedback with notifications instead of alerts.
*   **ARCHITECTURE**: Switched to Meta-based translation storage (no duplicate posts).
*   Updated plugin description and version.

= 1.2.0 =
*   Fixed language switcher button visibility with fixed/sticky theme headers.
*   Increased z-index to 999999 for floating button to ensure it appears above fixed headers.
*   Added Top Offset setting to adjust button position for themes with fixed headers.
*   Improved CSS transitions for smoother hover effects.

= 1.1.1 =
*   Added configurable Language Switcher with settings page.
*   Added support for Floating, Menu, and Shortcode display modes.
*   Added Dual Buttons layout (TH | EN side-by-side).
*   Added Button Content options (Flag Only, Text Only, Both).
*   Added customizable floating positions (Top-Right, Center-Right, Bottom-Right).
*   Fixed PHP warning in Router.php for page_link filter.
*   Improved flag icon styling with smaller size (30px).

= 1.1.0 =
*   Initial Release for testing.
*   Added Glossary Custom Post Type.
*   Added Hybrid Translation Workflow.
*   Added Frontend Routing for /en/ URLs.

== Frequently Asked Questions ==

= Does this plugin create duplicate posts for translations? =

No. Version 2.0.0 uses a Meta-based architecture that stores translations as post_meta on the original post.

= Does it work with Gutenberg? =

Yes! Version 2.0.0 includes a Gutenberg parser that translates block content while preserving block structure.

= Does it work with Elementor? =

Yes! Version 2.0.0 includes an Elementor parser that handles widget data and nested structures.

= What AI service does it use? =

The plugin uses configurable AI services. Configure your API key in Settings → API Settings.

== Screenshots ==

1. Dashboard overview
2. Translation comparison view
3. Review Content modal
4. Settings page
5. Glossary management

== Upgrade Notice ==

= 2.1.0 =
Bug fixes for View Logs functionality and Dashboard statistics now show real data from database.

= 2.0.0 =
Major update with Gutenberg/Elementor support, Auto-Translate feature, and improved UI. Recommended for all users.
