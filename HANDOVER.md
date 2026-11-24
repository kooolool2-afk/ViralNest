# ViralNest 2.2 handover

ViralNest is now a custom PHP + MySQL website with its own dashboard, **ViralNest Studio**. WordPress is not used. The old WordPress version is kept on the `wordpress` branch for reference only.

## What's in the box
- **Public site (EN + AR, right-to-left):**
  - Pages: Home, Services (9 categories, including PR & Influencer Marketing, each with its own page), Work (portfolio case studies), About, Work with us, Work with us > Apply (freelancer application form), Contact, Request a Quote, Privacy, and 404.
  - Features: animations that respect reduced motion, a consent banner, first-party analytics, SEO (sitemap, hreflang, schema) and the real ViralNest logo.
- **ViralNest Studio (`/admin`):**
  - Overview
  - Quote requests (statuses, notes, CSV)
  - Freelancers (New, Reviewing, Shortlisted, Contacted, Rejected; rate, social accounts, portfolio file and CV; filters by rate, currency, max price and more; CSV; email alerts)
  - Analytics
  - Services
  - Portfolio (case studies with client-permission and verified-results switches)
  - Website content (every text in EN/AR, with draft and publish)
  - Media
  - Settings
  - Team (roles: admin, sales, editor, analyst)

## Where things are
| Need | File |
|---|---|
| Upload to Hostinger, step by step | `docs/DEPLOYMENT-HOSTINGER.md` |
| Daily use of Studio | `docs/OWNER-GUIDE.md` |
| What to tick before launch | `docs/LAUNCH-CHECKLIST.md` |
| What was tested and what's pending | `docs/VERIFICATION-REPORT.md` |
| Colours, type, components | `docs/DESIGN-GUIDE.md` |
| Tables and data retention | `docs/DATABASE-SCHEMA.md` |
| Screenshots | `design/screenshots/` |
| Upload package | `dist/viralnest-hostinger-2.2.0.zip` (build with `tools/package.sh`) |

## Rules this build follows
- No invented testimonials, results, client logos, prices, contact details or office locations. Prices are hidden until you switch them on.
- Portfolio drafts (maloum, Rika Homemade, King Tut Stone) stay **unpublished**. Studio will not publish a case study until "Client approved publication in writing" is on. Results show only when "Outcomes verified" is on.
- Personal data (names, emails, phones, messages, CVs) stays in your database and is never sent to analytics.
- Credentials live only in `app/config.php` on the server, never in Git or in the package.

## Next steps
1. Send the portfolio links and brands so the case studies can be written up from what you actually delivered.
2. Follow `docs/DEPLOYMENT-HOSTINGER.md`, then work through `docs/LAUNCH-CHECKLIST.md`.
