=== Sole Translation ===
Contributors: solecomputer
Tags: translation, ai translation, multilingual, translate, localization
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.2
License: AGPL-3.0-only
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Translate eligible WordPress page text with context-aware AI through SOLE Engine.

== Description ==

Sole Translation translates text in the HTML that WordPress sends to a
visitor. It keeps translations locally and uses SOLE Engine to obtain new
ones. A background worker handles automatic service requests, so a page load
does not wait for a new AI translation to finish.

The plugin provides:

* Translation memory shared across pages
* Neighboring source text and existing translations as context when available
* Source and target language settings, including custom language labels
* A language menu as a block, shortcode, classic widget or automatic floating selector
* Local reuse of translations returned by the service

You need the [SOLE Engine](https://wordpress.org/plugins/sole-engine/) plugin,
a SOLE Engine service, a SOLE Account and a user key. Save your account's key
in SOLE Engine and verify it with Refresh Account Status in its Diagnostics.

= External services =

Sole Translation makes no outbound request itself. It passes translation work to the
required SOLE Engine plugin, which contacts `engine.sole.computer`. Sole
Computer (https://sole.computer) operates that service.

**Data sent for translation.** The service receives eligible page text or
Sole Translation interface strings. Contiguous text separated by supported inline tags
or links can form one phrase. Such a phrase includes canonical inline tag
names and their nesting, but not element attributes, URLs, comments or the
site's original HTML nodes. Sole Translation substitutes nonnumeric placeholders for
numeric text nodes within a phrase and restores the numbers locally. It does
not submit units made entirely of numbers.

Page batches include the target language code and display name. Custom
targets also include their administrator-supplied names and any notes;
notes configured for built-in targets are included too. If source detection
is disabled, a batch includes the source code and built-in display name.
When a translated neighbor is available, its source text and translation
provide context. Single String Test sends the administrator's entered text
without page-neighbor context.

Returned page translations are stored in Sole Translation's translation memory.
Translations of Sole Translation's interface strings are stored in WordPress options;
a change to the site locale or source strings causes them to be requested
again.

On non-English sites, changes to Sole Translation's interface text can start a full interface-dictionary renewal. English is used until renewal finishes. The worker retains accepted jobs while retrying interrupted work; unavailable or expired results keep their English fallback.

**When requests happen.** Installation and activation send no translation
content. A visitor request can register eligible untranslated text as pending
in the local database. The background worker later submits pending page text
in batches. On sites using a language other than English, it can also submit
bounded batches of Sole Translation interface strings.

Turning automatic translation off stops scheduled page work, Process now,
and automatic interface-string submission and polling. Process now requires
translation to be enabled. Single String Test and the Diagnostics control
named Submit and poll translation flow remain explicit administrator actions.

**Which text is eligible.** Selection operates on the final server-rendered
HTML. Sole Translation does not compute browser or CSS visibility, and it cannot select
text added later by JavaScript. Supported inline formatting can join adjacent
text into a phrase. Attribute values are not translation inputs.

The document `<title>` has its own single-string context. Applying
`translate="no"`, `class="notranslate"` or `data-no-translate` to that element
excludes it. Other text in the head is skipped. Text below `script`, `style`,
`svg` and `math` is excluded, as are whitespace-only units and pure numbers.
The `[sole_translation_notranslate]` shortcode (legacy alias `[notranslate]`),
the `notranslate` class, `translate="no"` and
`data-no-translate` provide further exclusions. Text inside `pre` or
`textarea` is excluded unless you enable its corresponding setting.
New custom codes use letters and digits separated by hyphens; underscores
normalize to hyphens. Existing saved custom codes remain available.
Human-locked rows can supply displayed translations, but automatic work does
not resubmit or overwrite them. Debug decoration, including an eligible
document title, is limited to logged-in administrators.

New translation-memory rows are not registered on logged-in requests, search
results, 404 pages, or requests with query parameters other than `l`.
Those requests can still display locally cached translations. The WordPress
admin bar, including its account name, is excluded through `#wpadminbar`.
For other anonymous pages, registration is limited to 100 new text rows per
request and 1,000 per UTC day. Hitting a limit stops registration and records
a diagnostic. Eligible public text outside these exclusions can reach the
service through SOLE Engine.

**Credentials and recovery.** A missing key prevents new automatic submission
and polling. Local pending registration and display of cached translations
can continue. Diagnostics reports `SOLE Engine credential: missing` and
offers `Enter a user key in SOLE Engine settings, then verify Account Status.`

A saved key that has not been verified against matching API evidence produces
`SOLE Engine credential: present but not verified`. Automatic Engine work
waits for verification. The action is `Open SOLE Engine Diagnostics and
refresh Account Status.` Rejection by the API produces
`SOLE Engine credential: invalid`. Replace the saved user key from your SOLE
account, then verify Account Status.

If the Engine readiness interface cannot be used, Diagnostics reports
`SOLE Engine: not available` and offers `Update or activate SOLE Engine, then
review its Diagnostics.` Visitors receive original or cached content; the
key and its credential state are not displayed to them.

This is Sole Translation's complete current disclosure about service use and
privacy-relevant data handling. The operator's
[Sole Engine terms](https://sole.computer/wordpress/engine/terms) and
[privacy policy](https://sole.computer/wordpress/engine/privacy) apply to the
service. SOLE Engine's own disclosure, including its scheduled background
jobs, also applies; see its plugin page for that account of its behavior.

== Installation ==

1. Install and activate [SOLE Engine](https://wordpress.org/plugins/sole-engine/).
2. Install and activate Sole Translation.
3. Save your SOLE account's user key in SOLE Engine, then verify Account Status
   in SOLE Engine Diagnostics.
4. Open Settings > Sole Translation, enable translation, and choose the
   source and target languages.
5. Visit your public site. With targets configured, the floating language
   dropdown appears automatically. It opens upward or downward according to
   available viewport space. For manual placement, turn the floating selector
   off and add a block, classic widget or `[dolet_language_menu]` shortcode.

== Frequently Asked Questions ==

= How do I switch from Dolet AI Translation? =

Allow accepted page-translation jobs to finish before switching. Deactivation
clears their local job records; interrupted page work may be submitted again.
Deactivate Dolet AI Translation before activating Sole Translation; do not run
both copies together. You may leave the old copy installed and inactive.
To remove it after checking the new copy, delete only the old plugin folder
with SFTP or your host's file manager. Do not use the Plugins-screen Delete
link or an uninstall command on the old copy: both copies share saved state.
Uninstall can remove saved settings and, if its deletion option is enabled,
translation memory. The new name uses the existing settings and memory.

= What does the first visit show? =

A page with no cached translation shows its source content. Eligible missing
strings can be registered locally for later background translation. Once
translations are available, subsequent requests can use that local cache.
Content outside the supported selection remains in its source language.

= What if Engine or the key is unavailable? =

WordPress requires SOLE Engine before it allows Sole Translation to activate. A later
dependency or credential problem produces a status notice to administrators
when translation is enabled. Original or cached content remains available;
new translations require the indicated recovery action.

= How can I remove the automatic selector? =

Clear "Show floating language selector" in Sole Translation's settings and save.
This hides the automatic bottom selector. Your manually placed blocks,
widgets and shortcodes remain available.

= Will it work with my theme or builder? =

Compatibility depends on the HTML produced by the theme and other plugins.
Only supported server-rendered text is eligible. Some SVG, legacy or very
large documents, and content emitted after the closing body, can cause a page
to retain its source text. Client-side templates and shortcodes inside
textarea elements also have compatibility limits. Browser-generated text
and CSS visibility are outside this translation pass.

= What is removed when I delete Sole Translation? =

The default is to keep the translation-memory table and saved settings for
reuse after reinstallation. To delete them, enable "Delete Sole Translation data when
the plugin is deleted" in Settings and save before deleting the plugin.
That removal cannot be undone. If the database refuses deletion, uninstall
fails visibly and leaves the table and settings in place so an administrator
can correct access and retry.

= Can I lock a translation against automatic replacement? =

Sole Translation has no interface for setting human locks. It respects locks already
set by another integration and does not overwrite those rows automatically.

= How do I exclude a passage? =

Use `[sole_translation_notranslate]...[/sole_translation_notranslate]` (the old `[notranslate]` form remains an alias), or mark its HTML element with the
`notranslate` class or `translate="no"`. The selection rules above describe
the other exclusions and the optional pre/textarea settings.
The shortcode filters nested shortcode output through WordPress's post HTML
allowlist. To exclude forms or other markup outside that allowlist, apply the
class or attribute to the existing element instead.

== Screenshots ==

1. Sole Translation's WordPress 7.1 language selector, with Spanish and original content.

== Licence ==

The license is `AGPL-3.0-only`. `LICENSE` contains the canonical GNU AGPLv3
text; `SOLE-ADDITIONAL-TERMS.txt` contains the SOLE Section-7 notice.
Copyright (C) 2026 Sole Computer.

== Source code ==

Source repository: https://github.com/solecomputer/dolet-translation
The JavaScript and CSS are directly maintained readable source;
no bundler or minifier is used. A deterministic allowlisted packaging step
normalizes line endings and removes non-distributed comments to produce
the release package.

== Changelog ==

= 1.0.2 =
* Both exclusion shortcode names now filter nested output through WordPress's post-HTML allowlist. Disallowed tags are removed and their body text may remain; use element exclusion markers for arbitrary original markup.
* Checkbox settings now read stored 'on', 'yes', and text 'true' values as off. Re-save affected checkboxes on the Settings page to enable them.
* Preserve WordPress Image-block duotone SVG when translating supported pages.
* Provide clearer Diagnostics messages for rejected markup and generated responses.
* Pages whose original HTML fails the initial safety check no longer add text to the translation queue.
* Keep accepted interface-translation jobs when background work is delayed or interrupted.

= 1.0.1 =
* Prepare the WordPress.org release with revised service disclosure and setup guidance.

= 1.0.0 =
* First public GitHub release.
