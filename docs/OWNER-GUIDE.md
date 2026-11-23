# ViralNest Studio: owner guide

ViralNest Studio is the team dashboard at **https://viralnesteg.com/admin**. It's built for ViralNest only: no WordPress, no plugins to update.

## Signing in and the team

- Sign in with your email and password. After 5 wrong passwords an account locks for 15 minutes; too many attempts from one connection are also slowed down.
- **Team** (admins only): add people, pick their role, reset passwords. A new person gets a one-time password shown once on screen; send it to them privately and ask them to change it under **My account**.
- Changing a password signs that person out everywhere else. Sessions end after 8 hours without activity.

| Role | Can do |
|---|---|
| Administrator | Everything, including Settings, Team, exports and publishing |
| Sales | Quote requests and freelancer applications: view, update status, notes, delete |
| Editor | Website Content, Services, Portfolio and Media as drafts; an admin publishes |
| Analyst | Analytics only |

## Overview

Your landing screen: requests in the last 30 days, what's waiting for a reply, new freelancers, items waiting for approval, and a **Before launch** checklist.

## Quote requests (Leads)

Every form submission lands here with a reference like **VN-00012**.

- Tabs filter by status: New, Contacted, Qualified, Proposal sent, Won, Lost, Spam.
- Search by name, email, phone or reference; filter by service, source, language and dates.
- Open a request to see the services picked, the message, where the visitor came from (source, landing page, campaign tags), and to **Email**, **WhatsApp** or **Call** them in one click.
- Change the status and add notes; everything is kept in the request's history with who did it.
- **Export CSV** (admins) exports the current filter. Open it in Excel or Google Sheets.
- Suspected spam is kept with status Spam (so nothing real is lost) and deleted automatically after the retention period.
- **Delete request** permanently removes a person's data, for data-deletion requests.

## Freelancers (Work with us)

Applications from the **Work with us** page arrive with a reference like **FL-00004**.

- Statuses: New → Reviewing → Shortlisted → Contacted, or Rejected.
- Filter by specialty, status, availability and language; search by name or email.
- The detail page shows portfolio links, experience, languages, availability, rate expectations and the optional **CV (PDF)**, which you download from the dashboard only. CVs are stored privately, never on a public URL.
- Notes and status history like quote requests. **Export CSV** for admins.
- Switch the page off or on in Settings > Freelancers. Alert emails for new applications are optional.
- Applications and CVs are deleted automatically after the retention period you set (default 2 years).

## Portfolio (case studies)

The **Work** page is built to win clients: each case study shows the client and industry, **the challenge**, **our approach**, **what we delivered**, a gallery, links to the live work, and the services involved, with a "Request something similar" button that pre-fills the quote form.

To add one: **Portfolio > New case study**.
1. Fill English (and Arabic when ready): title, summary, challenge, approach, main text, deliverables.
2. Pick the services involved and the industry (used for the filters on the Work page).
3. Upload images in **Media** first, then choose the cover and gallery.
4. Add live links, one per line, as `Label|https://…` (for example `Instagram|https://instagram.com/brand`).
5. **Publishing needs the client's written permission**: tick *Client approved publication in writing*. Without it the case study stays a draft.
6. **Results**: only add numbers that are documented. Tick *Outcomes verified (show them)* and note where the evidence is; otherwise the results section doesn't show.

The three drafts from the brief (maloum, Rika Homemade, King Tut Stone) are waiting for permission and real details.

## Services

- Categories and sub-services, in English and Arabic, with icons. Sub-services show as sections on their category's page, and in the quote form.
- Reorder with the position numbers and **Save order**.
- **Pricing** is hidden by default. Switch on *Show a price on the website* per service only when the price is final.
- Changing a slug keeps the old address working (it redirects).

## Website Content

Every text on the site, side by side in English and Arabic: home sections, About, Contact, Request a Quote, Work, Work with us, privacy policy, SEO titles and descriptions.

- Changes are saved as a **draft**. Use **Preview EN / AR** to check them, then **Publish**. **Discard** throws the draft away.
- **Structure**: reorder or hide home-page sections, choose menu items, and choose which pages are ready in Arabic. An Arabic page that isn't switched on shows "not available in Arabic yet" and is left out of search results.

## Analytics

First-party analytics, counted only for visitors who allow it (logged-in staff are never counted). Pick a period to see visits, quote requests, conversion rates, top pages, landing pages, sources and campaigns, which services get viewed vs. requested, portfolio views, which buttons bring requests, WhatsApp and social clicks, languages, devices and freelancer applications by specialty. All times are Cairo time.

## Media

Upload JPG, PNG or WebP (up to 10 MB). Images are resized and converted to WebP automatically. Add alt text in English and Arabic so screen readers and search engines understand them.

## Settings (admins)

Brand (name, logo, colors with contrast checks), contact channels, email alerts and SMTP, Freelancers page switch and alerts, analytics and consent, Google Analytics 4 and Search Console, data retention, and the main domain/HTTPS redirect.

## Good habits

- Reply to New requests within one working day and move them along the statuses so the numbers stay useful.
- Add a case study only with written permission, and real numbers only with evidence.
- Keep contact details in Settings current: the site shows only what's there.
