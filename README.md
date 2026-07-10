# Mathilde — Premium Editorial WordPress Theme

A magazine-style theme for beauty, fashion & lifestyle blogs, built to match the
**Mathilde Lacombe** mockups (homepage, single post, category archive) on every
device. Elegant serif/sans typography, blush-and-cream palette, dark mode,
AEO/SEO schema, and a fully Customizer-driven homepage.

---

## 1. Installation

1. Zip the `mathilde-blog` folder (or copy it) into `wp-content/themes/`.
2. In WordPress: **Appearance → Themes → Activate** "Mathilde Blog".
3. Recommended: install a caching/image plugin of your choice. No build step or
   external dependencies are required — all CSS/JS is vanilla and ships ready.

**Requirements:** WordPress 6.0+, PHP 7.4+.

---

## 2. First-time setup (5 minutes)

### Front page
**Settings → Reading → Your homepage displays → A static page.**
Create two pages (e.g. *Home* and *Journal*), set *Home* as the homepage and
*Journal* as the posts page. The theme automatically uses `front-page.php` for
the homepage layout.

### Menus (Appearance → Menus)
Create and assign menus to these locations:
- **Primary Menu** — top navigation (Fashion, Beauty, Lifestyle, Travel…)
- **Footer Menu** — footer "Information" column
- **Legal / Bottom Menu** — bottom bar links
- **Social Links** — optional (social icons also come from the Customizer)

### Categories
Create categories matching your sections. The default homepage sliders look for
these slugs: `fashion`, `beauty`, `jewelry`, `health` (all editable in the
Customizer → *Homepage — Post Sections*).

### Logo
**Appearance → Customize → Site Identity → Logo**, or leave blank to use the
serif "SITE NAME / subtitle" wordmark. Set the subtitle under
*Customize → Header & Announcement*.

---

## 3. Customizing everything (Appearance → Customize)

All content blocks are editable under the **"Mathilde Theme Options"** panel:

| Section | Controls |
|---|---|
| Colors | Accent (rose), ink, soft text, blush, cream, borders |
| Typography | Heading & body font family, base size, corner radius |
| Header & Announcement | Announcement bar text/link, logo subtitle, subscribe URL |
| Homepage — Hero | Source category, number of slides |
| Homepage — About | Eyebrow, title, text, button, image |
| Homepage — Category Cards | Pick categories (or auto top-5) |
| Homepage — Featured In | Logo list (names or image URLs) |
| Homepage — Post Sections | Per-section title, category slug, on/off |
| Homepage — AI Trust | Four trust pillars (title + text) |
| Homepage — FAQ | Title, JSON items, image |
| Newsletter | Title, text, footer band toggle |
| Instagram | Handle, profile URL, image list |
| Sidebar | About-card avatar, name, bio |
| Social Media | Facebook, Instagram, Pinterest, TikTok, email |
| Footer | About text, footer thumbnail |
| Dark Mode | Show/hide header toggle |

> **Fonts:** the heading/body fields take a CSS font-family name. To use a
> different Google Font, load it (e.g. via a plugin or by editing
> `inc/enqueue.php`) and enter its family name here.

---

## 4. Writing articles (Single Post features)

When editing a post, scroll to the **"Mathilde — Article Extras"** box:

- **Key Takeaways** — one per line → renders the pink takeaways box + helps AEO.
- **Hide Table of Contents** — the TOC auto-builds from your H2/H3 headings;
  tick to hide it on a given post.
- **Review (rating + verdict)** — fills the review box *and* outputs Review
  schema.

**Author box:** edit a user profile and fill *Biographical Info* and the
Instagram / Pinterest / Twitter URL fields (added by the theme).

The single post page automatically provides: breadcrumbs, reading time, sticky
share rail, related posts, and a curated sidebar (About / Trending / Categories
/ Newsletter / Instagram).

---

## 5. SEO / AEO / Schema

`inc/schema.php` outputs JSON-LD for **Organization, WebSite (+search),
Article, Author, BreadcrumbList, FAQPage, and Review**, plus Open Graph and
Twitter Card meta. If **Yoast** or **Rank Math** is active, the theme defers to
the plugin to avoid duplicate schema.

---

## 6. Newsletter

Submissions are stored in the `mathilde_newsletter_subscribers` option and fire
a `do_action( 'mathilde_newsletter_signup', $email )` hook. To connect Mailchimp
/ ConvertKit / etc., hook that action in a small plugin or your child theme.

---

## 7. File structure

```
mathilde-blog/
├── style.css, functions.php
├── header.php, footer.php, sidebar.php, front-page.php
├── single.php, archive.php, index.php, search.php, page.php, 404.php
├── comments.php, searchform.php, screenshot.png
├── assets/
│   ├── css/  (base, typography, layout, components, utilities, responsive, dark-mode, editor-style)
│   └── js/   (theme, navigation, dark-mode, carousel, newsletter, customizer-preview)
├── inc/      (setup, enqueue, helpers, template-tags, widgets, customizer, schema, accessibility, meta-boxes, ajax)
├── page-templates/ (full-width)
└── template-parts/
    ├── components/ (post-card, category-card, author-box, newsletter, faq, ai-trust, related-posts)
    ├── homepage/   (hero, about, category-cards, featured-in, posts-section, instagram)
    └── global/     (search-overlay, mobile-drawer)
```

---

## 8. Responsive breakpoints

Per the design spec: **1400 / 1200 / 992 / 768 / 480**. Desktop nav collapses to
a hamburger drawer at ≤992px; sliders become swipeable; the single-post share
rail hides and the sidebar drops below the article.

---

## 9. Demo content

A starter category set + sample posts make the homepage come alive instantly.
Quickest path: create the `fashion`, `beauty`, `jewelry`, `health` categories,
publish a few posts in each **with featured images**, then visit the homepage.
Empty sections hide themselves, and Instagram/Trending fall back to recent post
thumbnails until you set real images.

---

## 10. Paid contributor memberships (PayPal)

Visitors can pay a one-time yearly fee via **PayPal** and instantly receive an
**Author** or **Editor** account that auto-downgrades to Subscriber when the
term ends.

**Set up:**
1. **Users → Membership** in wp-admin.
2. Create a PayPal app at <https://developer.paypal.com> → *Apps & Credentials*
   and paste the **Client ID** and **Secret**. Pick **Sandbox** to test, **Live**
   to take real money (the mode must match the keys).
3. Set prices, currency, and access length (365 days = 1 year). Optionally tick
   **Require admin approval** to vet members before the role is granted.
4. Publish a page using the **"Membership / Become a Contributor"** template, or
   drop the `[mathilde_membership]` shortcode on any page. (The demo install
   already has `/become-a-contributor/`.)

**How it's secured:**
- Prices and roles are resolved **server-side**; the browser can't change them.
- Payment is **captured and verified** against PayPal (status `COMPLETED`,
  amount + currency match the plan) before any account is created.
- **Replay protection** — each PayPal transaction id is recorded and can't be
  reused.
- Role grants are **allow-listed** to `author`/`editor` only (no escalation to
  admin).
- A **daily cron** (`mathilde_membership_daily`) downgrades expired members and
  emails them a renewal link. Members can re-pay to extend.

> **Trust note:** the *Editor* role can edit/delete everyone's content. Prefer
> the *Author* plan for open signups and reserve *Editor* for vetted people —
> or enable "require admin approval".

Manage members (extend / expire / approve) from the table at the bottom of the
**Users → Membership** screen.

## 11. Selling ebooks / digital guides (PayPal)

Sell downloadable PDFs/guides (e.g. *Makeup Routine*, *Goodbye Dark Circles*,
*The Complete Skincare Guide*) with one-time PayPal checkout and secure,
tokenised downloads. Uses the **same PayPal credentials** as memberships
(Users → Membership).

**Add a product:** **Ebooks → Add Ebook**.
- Title, description, and **Featured Image** = the cover.
- In *Ebook Details* (sidebar): set the **price**, subtitle, page count, and
  upload the **PDF/EPUB/ZIP** file.

**Where it appears:**
- The shop archive at **`/ebooks/`** (already in the demo menu as “Shop”).
- Each product gets a single sales page with a sticky buy box.
- Drop `[mathilde_ebooks]` on any page for a grid, or `[mathilde_my_downloads]`
  to show a logged-in customer their purchases.

**How delivery is secured:**
- Files are stored in a **protected folder** (`uploads/mathilde-ebooks/`) with an
  `.htaccess` deny — direct URLs return **403**.
- After a verified PayPal payment, the buyer gets a **tokenised, time-limited
  download link** (HMAC-signed, expiry + download-count limited) on screen *and*
  by email. Tampered/expired links are rejected.
- Logged-in buyers can re-download anytime from their account
  (`[mathilde_my_downloads]`); guests use the emailed link.
- Each PayPal transaction is single-use (replay-protected) and the amount is
  re-verified against the product price server-side.

Orders are listed under **Ebooks → Orders** in wp-admin.

**Promotion built in:**
- A **"Featured Guides"** strip on the homepage (Customize → *Homepage —
  Featured Guides*).
- A **"Recommended Guide"** card after each blog post's content. It auto-matches
  the post's category, or pick a specific guide per-post in the *Article Extras*
  box. Toggle/label it under Customize → *Homepage — Featured Guides*.

## Roadmap (out of current scope)

The brief's later phases — membership/community, Gutenberg block library, object
caching/Redis, AI recommendation engine — are **plugin-territory** rather than
theme features. The theme exposes clean hooks (`mathilde_newsletter_signup`,
`mathilde_before_header`, `mathilde_after_header`, `mathilde_before_footer`) so
those can be layered on without modifying the theme.
```
