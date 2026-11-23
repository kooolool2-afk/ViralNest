# ViralNest launch checklist

The site is built and tested. Version 1.1.0 rewrites the public content from the **ViralNest Master Brief v2.0** (positioning, about/mission, three ways to work, full service catalog, brand voice, industries). Several other **inputs were not supplied**. Nothing was invented to fill these gaps: the site hides what is missing or shows an honest placeholder, and the admin shows a yellow "Before launch" box listing what is still open.

Owner = you (ViralNest). Where = where to enter it.

## A. Missing inputs (needed from ViralNest)

| # | Item | Why it matters | Where | Until then |
|---|---|---|---|---|
| A1 | ~~Brief v2.0~~ **Received and applied (v1.1.0).** Review the wording once on staging. Kept private on purpose: the internal parts of the brief (Parts 10 and 13 to 16). The vision line is not used publicly because it refers to an internal plan. | Final positioning, wording, service scope | Website Content (EN/AR), Services | Copy follows the brief |
| A2 | **Official color palette / brand guidelines** | Confirm tokens | Settings > Brand colors | Colors sampled from the logo (see DESIGN-GUIDE.md) |
| A3 | **Logo files** (SVG or high-res transparent PNG; light + dark versions) | Sharper logo, better favicon | Settings > Brand | Cut from the supplied PNG |
| A4 | **Logo tagline**: decided as "We make brands spread." The supplied logo image still contains "Digital growth accelerated" | Two taglines confuse | Supply a logo file without the second line | The site uses the mark + wordmark only, and "We make brands spread." as text |
| A5 | **WhatsApp business number** + approved prefilled messages | WhatsApp buttons, contact page, floating button | Settings > Contact | All WhatsApp buttons hidden |
| A6 | **Public email and phone** (if you want them public) | Contact page, footer, structured data | Settings > Contact | Hidden; contact page points to the quote form |
| A7 | **Social profile URLs** (Instagram, Facebook, LinkedIn, TikTok, X, YouTube, Behance) | Footer, contact page, structured data | Settings > Contact | Hidden |
| A8 | **Lead alert recipients** | Email when a quote arrives | Settings > Notifications | Leads saved in the admin, no email |
| A9 | **SMTP mailbox** (e.g. website@viralnesteg.com) + password in wp-config | Reliable alert delivery | Settings > Notifications + wp-config.php | — |
| A10 | **Portfolio**: three drafts exist (maloum, Rika Homemade, King Tut Stone) with only the work described in the brief. For each: confirm the work, get the client's **written permission** to be shown and named, add real images; add results only with evidence | Portfolio and homepage "Selected work" | Portfolio (Drafts) | Drafts are not public; "Case studies are being prepared" message; homepage section hides itself |
| A11 | **Service catalog check**: the brief's catalog is published (8 categories, 43 sub-services). 24 sub-services are ticked *Show in quote form*; the rest are listed on their category page only. Adjust the ticks if you want a different form | Quote form length vs. choice | Services > each service | As described |
| A12 | **Prices** (optional, per service, with currency) | Only if you want public prices | Each service > Pricing | "Proposals depend on scope" |
| A13 | **About page review**: story, mission (verbatim from the brief), brand voice and "what to expect" | Must match how you actually work | Website Content > About | Written from the brief, no facts or numbers |
| A14 | **Privacy policy legal review** (Egypt data protection law 151/2020; GDPR if you target EU visitors) | Legal | Website Content > Privacy ([REVIEW]) | Accurate description of what the site actually collects, marked as draft for legal review |
| A15 | **Arabic copy review** by a native Egyptian Arabic copywriter | Tone and brand voice | Website Content, Services (Arabic boxes) | Professional Egyptian Arabic draft |
| A16 | **GA4 measurement ID** (optional) | Google Analytics | Settings > Tracking | First-party analytics only |
| A17 | **Google Search Console** verification code | Indexing, sitemap | Settings > Tracking | — |
| A18 | **Office location** (only if you want it public) | Organization schema, contact page | Ask your developer to add the field | Not shown anywhere |
| A19 | **Team photos / real imagery** (optional) | About page | Media + Website Content | Abstract brand visuals |

## B. Go-live steps (in order)

1. [ ] Staging created, plugin + theme installed, permalinks "Post name" ([DEPLOYMENT-HOSTINGER.md](DEPLOYMENT-HOSTINGER.md) steps 2–6).
2. [ ] A5–A9 entered; **Send a test email** works.
3. [ ] A1, A13–A15 reviewed; Website Content published.
4. [ ] A11 decided; A10 at least considered (launching with "being prepared" is fine).
5. [ ] Smoke tests on staging (deployment guide step 15); delete the test lead.
6. [ ] Backup of the existing live site (if any) and screenshot of DNS records.
7. [ ] Push to live / install on the main domain. **(Needs your explicit go-ahead; overwrites production.)**
8. [ ] SSL active for viralnesteg.com and www; then Settings > Domain > **Force HTTPS canonical redirect** on.
9. [ ] DNS points to Hostinger. **(Only you change DNS, when ready.)**
10. [ ] Settings > Reading: search engines allowed.
11. [ ] Search Console: verify, submit `https://viralnesteg.com/sitemap.xml`.
12. [ ] Smoke tests on production; purge cache.
13. [ ] Create staff accounts with the right roles (Sales, Content Editor, Analyst); remove any shared logins.
14. [ ] Real cron job set up (deployment guide step 12).
15. [ ] Backup schedule confirmed in hPanel.

## C. After launch (first two weeks)

- Check **Leads** daily and set statuses, so the funnel and "Won" numbers mean something.
- Check **Analytics > How complete are these numbers?**: if few visitors allow analytics, consider *Notice* consent mode only after legal advice.
- Add the first approved case study as soon as you have one.
- Re-run the automated tests on staging after any update.
