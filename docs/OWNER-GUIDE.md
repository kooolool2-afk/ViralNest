# ViralNest website: owner guide

Everything you edit lives under **ViralNest** in the WordPress admin menu:

| Menu | What it is for |
|---|---|
| **Website Content** | All page texts in English and Arabic, homepage sections, navigation, CTAs, page SEO titles. Draft → preview → publish. |
| **Services** | The service catalog (8 categories and their sub-services) with Arabic version, pricing and quote-form options. |
| **Portfolio** | Case studies. Only approved facts are shown. |
| **Leads** | Quote requests: search, filters, details, notes, statuses, CSV export. |
| **Analytics** | Visits, sources, service/portfolio views, WhatsApp/social clicks, quote funnel. |
| **Settings** | Logo, colors, contact channels, notifications/SMTP, tracking/consent, data retention, domain. |

The dashboard of each screen shows a yellow "Before launch" box when something is still missing (for example no WhatsApp number). These items are hidden on the site until you fill them in, so nothing broken or invented is shown to visitors.

## Golden rules

1. **Never publish invented facts.** No testimonials, results, client names, prices, phone numbers or office addresses unless they are real and approved. The system is built to hide what is not filled in.
2. **Arabic is separate.** Each page, service and project has its own Arabic text and its own Arabic publish switch. Until the Arabic version is published, the Arabic site does not show that item (and the language switch falls back to the nearest Arabic page).
3. **Preview before you publish.** Website Content and posts both have preview.

## Website Content (texts on pages)

1. ViralNest > **Website Content**. Pick a page tab (Global, Home, About, Services, Portfolio, Contact, Quote, Privacy, 404, SEO).
2. Every field has an **EN** column and an **AR** column. Fields marked **[REVIEW]** are drafts that need your decision (for example the About story or the privacy text).
3. **Save draft** keeps your changes private. **Preview EN / Preview AR** opens the site with the draft (only you see it).
4. **Publish** makes the draft live. **Discard draft** throws away unpublished changes.
5. Tab **Structure**:
   - *Homepage sections*: drag to reorder, untick to hide (moving band of services and industries, the problem we solve, why ViralNest, catalog numbers (counted automatically from your services), services, three ways to work, who we work with, featured work, how we work, closing CTA). The hero is always first.
   - *Main navigation*: rename, reorder or hide menu items.
   - *Arabic pages published*: switch each Arabic page on only when its Arabic text is complete.

Users with the **ViralNest Content Editor** role can save drafts but only Administrators can publish.

## Services

ViralNest > **Services** lists categories and their sub-services (sub-services have a parent).

**Add a new service or sub-service**

1. Services > *Add New* (or *Add service*). Write the English title, a one-line summary (Excerpt) and the description.
2. *Page attributes* > **Parent**: choose a category to make it a sub-service; leave empty for a new category. *Order* controls position.
3. Box **Arabic version**: Arabic title, summary, description, and Arabic status (*Draft* or *Published*).
4. Box **Service settings, pricing and quote form**: icon, *Show in quote form* (on by default), pricing (below).
5. **Publish**. The service appears automatically on the Services page, its category page, the quote form, the sitemap and the structured data. No code changes.

Content Editors can only *Submit for review*; an Administrator approves by publishing.

The starter catalog follows the Master Brief: 8 categories and 43 sub-services, all published in EN and AR. To keep the quote form short, 24 sub-services have *Show in quote form* ticked; the others appear on their category page only. Tick or untick it per service to change the form. Media buying carries the note that ad budgets are paid separately.

**Pricing (optional, per service)**

Off by default: the page says proposals depend on scope. To show a price: tick *Show a price on the website*, choose *Starting from* or *Fixed price*, amount and currency (EGP, USD, SAR, AED, KWD, QAR, BHD, OMR, EUR; edit the list in Settings), optional price notes in EN/AR. The **ad spend** checkbox adds the note that advertising budget is paid separately.

**Changing a URL (slug)**: edit the slug; the old address automatically redirects (301) to the new one.

## Portfolio

1. Portfolio > *Add New*. Title, summary, story, featured image (add **alt text** in EN and AR in the media library).
2. **Project details**: industry (EN/AR), scope, services delivered (used for the filters), gallery, *Featured on the homepage*.
3. **Client name** is shown only if *Client approved being named* is ticked.
4. **Outcomes/results** are shown only if *Outcomes verified* is ticked and evidence is noted (internal, never shown). Unverified numbers stay hidden.
5. Arabic version and status as for services. Publish.

Three **drafts** are prepared from the brief: maloum, Rika Homemade and King Tut Stone. They describe only the work, with no results, and *Client approved being named* is off. Before publishing one: confirm the work, get the client's written permission, add real images, tick the approval, publish the Arabic version too. The draft "Project template" shows the structure; keep it as a draft or delete it.

## Leads

ViralNest > **Leads** (the red badge counts new leads).

- **Search** by email, name, company, phone or reference (VN-00012). **Filter** by date, status, language, source, service.
- Open a lead to see services requested (saved as they were named at that moment, even if a service is renamed later), contact details, message, language, landing page, referrer, UTM, and the anonymous visit path before the request (only when the visitor allowed analytics).
- **Status**: New → Contacted → Qualified → Proposal Sent → Won / Lost. **Spam** is for junk (suspicious submissions are flagged automatically, never silently dropped; if a real lead was flagged, set it back to New).
- **Internal notes**: call summaries and next steps; each note records who and when.
- **Email alert** status: *Sent*, *Failed* (with the error and a *Retry* button) or *Alerts off*.
- **Export CSV**: exports what the current filters show. Excel-safe (UTF-8 with BOM, formula injection neutralized). Only Administrators have export by default. Treat the file as personal data.
- **Delete**: permanently removes the lead, its services and notes.

Privacy requests: Tools > *Export Personal Data* / *Erase Personal Data* also cover leads (matched by email).

## Analytics

ViralNest > **Analytics**. Choose a range (today, 7/30/90 days, 12 months, custom).

- **How complete are these numbers?** Visits are recorded only for visitors who click *Allow analytics* (opt-in). Quote requests are always counted (server side). Logged-in staff are never tracked.
- **KPIs**: quote requests, measured sessions, page views, quote-button clicks, WhatsApp clicks, social clicks.
- **Quote form funnel**: measured sessions → clicked a quote button → started the form → submitted. *Form conversion rate* = measured sessions that started the form and submitted ÷ measured sessions that started the form. Submissions from visitors who declined analytics are listed separately as "unmeasured".
- Tables: top pages, landing pages, service and portfolio views, services requested, sources, UTM campaigns, which quote buttons lead to requests, WhatsApp/social clicks, language and devices.
- **Event definitions** at the bottom explain every event.

No email, phone, name or message ever enters analytics. Events are deleted after 400 days (change under Settings > Tracking).

**Google Analytics 4 / Search Console** (optional): paste a valid `G-XXXXXXX` ID and/or the Search Console verification code in Settings > Tracking. GA4 loads only after a visitor allows analytics. Leave empty to keep it off.

## Settings

- **Brand**: logo (light version for dark background), favicon (512×512 PNG), social share image (1200×630). Colors: change a color and save; a contrast check warns if text would become hard to read. *Reset to brand defaults* restores the logo colors.
- **Contact**: WhatsApp number (international format, e.g. +20…), WhatsApp prefilled messages EN/AR, public email and phone, social profiles. Empty = hidden everywhere (header, footer, contact page, floating button, structured data).
- **Notifications**: recipients, from-address, SMTP. The SMTP password belongs in `wp-config.php` (`VN_SMTP_PASSWORD`), see the deployment guide. *Send a test email* proves it works.
- **Tracking**: first-party analytics on/off, consent mode (*Opt-in*, recommended; or *Notice*), GA4 ID, Search Console code, retention (events 400 days, spam 30 days, leads: keep until deleted, or set a number of days).
- **Domain**: canonical host and the HTTPS redirect (turn on only after SSL works).

## Roles (Users > Add New)

| Role | Can |
|---|---|
| Administrator | Everything, including publishing, settings and CSV export. |
| ViralNest Sales | Leads: view, change status, notes, delete. No export, analytics or settings. |
| ViralNest Content Editor | Edit Website Content drafts, services and projects; submit for review. Cannot publish, see leads or settings. |
| ViralNest Analyst | Analytics only. |

Give each person their own account; never share logins.

## Images

- Upload JPG, PNG, WebP or AVIF images (also PDF, MP4, ICO). SVG and other file types are blocked for security. WordPress creates responsive sizes automatically.
- Always fill **Alternative text** (EN) and **Arabic alt text** in the media library.
- Recommended: project covers 1600×1000, gallery up to 2000 px wide, under 500 KB each.

## When something looks wrong

1. Purge the cache (LiteSpeed Cache > Purge All).
2. Check the yellow "Before launch" box on ViralNest screens.
3. A form that does not send: check Leads (the lead is probably saved) and the email alert status.
4. Contact your developer with the page URL and a screenshot.
