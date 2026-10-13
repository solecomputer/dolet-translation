=== Dolet AI Translation ===
Contributors: solecomputer
Tags: translation, ai translation, multilingual, translate, localization
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0
License: AGPL-3.0-only
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Context-aware AI translation for WordPress via SOLE Engine.

== Description ==

Dolet AI Translation brings context-aware multilingual translation in the final server-rendered WordPress HTML.
Powered by SOLE Engine, it uses neighboring text when available so translations stay coherent and natural.

**Features:**

* Rendered-page translation of eligible text nodes
* Context-aware triads: each string is translated considering its neighbors
* Translation memory with automatic reuse across pages
* Background worker: automatic Engine requests stay out of visitor page loads
* Language menu available as Gutenberg block, shortcode, or classic widget
* Translation-memory rows flagged as human-locked are never overwritten
* Configurable source and target languages with custom labels

**Requirements:**

* [SOLE Engine](https://wordpress.org/plugins/sole-engine/) must be installed and active
* A paid SOLE Engine service, SOLE Account, and user key are required
* Obtain the key from your SOLE account, save it in SOLE Engine, and use Refresh
  Account Status in SOLE Engine Diagnostics to verify it

= External services =

Dolet makes no outbound request itself. Through the required SOLE Engine plugin,
content reaches the paid SOLE Engine service at `engine.sole.computer`, operated
by Sole Computer (https://sole.computer).

**What reaches the service because of this plugin.** Eligible page text and
Dolet interface text being translated. When visible text is split only by
eligible inline formatting or links, Dolet may send it as one phrase containing
canonical inline tag names and nesting; element attributes, URLs, comments and
the site's original HTML nodes are not sent as part of that phrase. Standalone
numeric text is replaced by a nonnumeric placeholder and restored locally.
Automatic and administrator-triggered
page batches send the target language code and display name; a configured
custom target also sends its administrator-authored name and note when present.
When the source is not autodetected, batches also send its code and built-in
display name. A previously translated neighboring source string and its
translation are included as context only when one is available. Page
translations are cached in Dolet's translation memory. Interface translations
are cached in WordPress options and are requested again when the site locale or
Dolet's source strings change.

**When.** On a visitor request, eligible untranslated page text is recorded
locally as pending; the background worker submits it later in batches. On a
non-English site, Dolet can submit its own interface strings in bounded batches
across WordPress admin-side requests, including background admin-AJAX requests
from a logged-in user. Installing or activating Dolet sends nothing.
Disabling automatic translation stops scheduled page work and automatic
interface-string submission and polling, but it does not disable the explicit
administrator controls Run Worker Now, Single String Test, or Diagnostics'
Submit and poll translation flow. Single String Test sends the text entered by
the administrator without page-neighbor context.

**Selection and exclusions.** Dolet selects visible text units in the final HTML
sent by WordPress; reader-contiguous text separated only by conservative inline
markup can be selected as one phrase. Dolet does not calculate CSS/browser
visibility and does not cover text added later by client-side JavaScript. The
HTML document `<title>` is selected
as its own one-string context, without changing body context; `translate="no"`,
`class="notranslate"`, or `data-no-translate` on `<title>` protects it. Every
other head descendant is skipped. Dolet also skips text under `script`, `style`,
`svg`, and `math`; whitespace-only and pure-number text; and content
excluded by `[notranslate]`, the `notranslate` class, `translate="no"`,
`data-no-translate`. Text under `pre` and `textarea` is skipped unless its
setting is enabled. Attribute values are not selected. Human-locked translation
rows may still be displayed, but are never resubmitted or overwritten.
Debug mode marks an eligible document title like every other selected core.

**Credential states and recovery.** When the user key is missing, new automatic
Engine submission and polling are inactive. Eligible text may still be recorded
locally as pending and existing cached translations may still display.
Diagnostics shows `SOLE Engine credential: missing`. The recovery action is
`Enter a user key in SOLE Engine settings, then verify Account Status.` A saved
key without matching API evidence shows
`SOLE Engine credential: present but not verified`; automatic Engine work remains inert until Refresh
Account Status verifies that exact key. The recovery action is
`Open SOLE Engine Diagnostics and refresh Account Status.` If the API rejects it, Diagnostics shows
`SOLE Engine credential: invalid`; dependent work fails safely. Replace the
saved user key from your SOLE account, then verify Account Status. If the Engine
readiness contract is unavailable, Diagnostics shows
`SOLE Engine: not available`. The recovery action is
`Update or activate SOLE Engine, then review its Diagnostics.` Visitor
output retains original or cached content and never exposes the key or
credential state.

This section is Dolet's complete current disclosure about service use and
privacy-relevant data handling. Dolet claims no separate service-terms or
privacy-policy URL. It does not claim that this disclosure is, or replaces, the
operator's legal terms or privacy policy.

Because all traffic goes through SOLE Engine, its own disclosure — including
the background jobs it schedules — applies here too. See the SOLE Engine plugin
page for the full description.

== Installation ==

1. Install and activate the [SOLE Engine](https://wordpress.org/plugins/sole-engine/) plugin.
2. Install and activate Dolet AI Translation.
3. Obtain a user key from your SOLE account, save it in SOLE Engine, and verify
   Account Status in SOLE Engine Diagnostics.
4. Go to Settings > Dolet AI Translation.
5. Configure your source language and target languages.
6. Enable the translation worker.
7. Visit the public site: a floating, plainly labelled language dropdown appears
   automatically once target languages are configured. Its menu chooses upward
   or downward opening from the available viewport space. To use only a manual
   menu, disable the floating selector in Settings, then add the block, shortcode
   `[dolet_language_menu]`, or widget where you want it.

== Frequently Asked Questions ==

= What happens if SOLE Engine is not installed? =

Once translation is enabled, the plugin displays an admin notice asking you to
install SOLE Engine. Your site continues to work normally but no new
translations are generated.

= How does translation work? =

On the first visit, the page renders in the original language while untranslated strings are registered as pending. A background worker submits them to SOLE Engine in batches. On subsequent visits, cached translations are applied instantly.

= How do I hide the floating language selector? =

Clear "Show floating language selector" under Settings > Dolet AI Translation
and save. This affects only the automatic bottom selector; manually placed
blocks, shortcodes, and widgets remain available.

= Which themes and page builders does this work with? =

Dolet works on eligible text nodes present in the final server-rendered HTML.
Compatibility therefore depends on the theme or builder producing text that
passes the selector and exclusions described above. CSS visibility and content
added later by client-side JavaScript are outside this pass.

= What happens to human-locked translations? =

Translation-memory rows already flagged as human-locked are never resubmitted or
overwritten by automatic updates. Dolet does not currently provide a control
for setting that flag.

= What does the `[notranslate]` shortcode do? =

Wrap content in `[notranslate]...[/notranslate]` to prevent it from being translated. You can also use `class="notranslate"` or `translate="no"` attributes on HTML elements.

== Screenshots ==

1. The language selector on WordPress 7.1 switching between Spanish and original content.

== Licence ==

Dolet AI Translation is licensed `AGPL-3.0-only`. See `LICENSE` for the
canonical GNU AGPLv3 text and `SOLE-ADDITIONAL-TERMS.txt` for the SOLE Section-7
notice. Copyright (C) 2026 Sole Computer.

== Source code ==

Human-readable release source: https://github.com/solecomputer/dolet-translation.
The distributed JavaScript and CSS files are directly maintained readable
source; no bundler or minifier is used. Release packages are generated by a
deterministic allowlisted packaging step that normalizes line endings and
removes non-distributed comments.

== Changelog ==

= 1.0.0 =
* Initial public release
