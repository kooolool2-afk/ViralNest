# ViralNest design guide (v2)

## Brand

- **Logo:** the supplied ViralNest logo as delivered, the nest mark stacked above the "ViralNest" wordmark, without the "Digital growth accelerated" line. Files: `public/assets/img/logo-stack-*.webp`; the original is in `design/source/`.
- **Idea:** "We make brands spread." The visuals show ideas spreading out from the nest: a slow network of connected points in the hero, orbiting rings around the mark, and gradients that move from cyan to violet to orchid.

## Color tokens (from the logo)

| Token | Hex | Use |
|---|---|---|
| `--vn-bg` | #12161C | Page background (nest charcoal) |
| `--vn-surface` | #1E242A | Cards, form fields' containers |
| `--vn-text` | #F3F5F9 | Primary text |
| `--vn-muted` | #A7B1BF | Secondary text |
| `--vn-cyan` | #3DC5E9 | Primary actions, links, focus rings ("Nest" in the wordmark) |
| `--vn-violet` | #7B4FD0 | Gradients, highlights ("Viral" in the wordmark) |
| `--vn-blue` | #1C67AE | Inner nest blue |
| `--vn-orchid` | #B96ADD | Gradient ends, chart accent |
| `--vn-mint` | #7ADFCE | Success, check marks |
| `--vn-on-accent` | #0B0E13 | Text on cyan/mint buttons |

Colors can be changed in Studio > Settings > Brand; combinations that fail WCAG AA contrast aren't saved. The signature gradient is `cyan → violet → orchid` at 120°.

## Type

- English: **Sora** (variable 100–800). Headings 600–680, tight tracking (−0.025 to −0.045em).
- Arabic: **Readex Pro** (variable), no negative tracking, taller line height (1.3 headings, 1.75 body).
- Fluid scale with `clamp()`: hero up to 100px, H1 80px, H2 52px, body 17px.
- Both fonts are self-hosted (no Google Fonts requests).

## Layout

- Max width 1240px, side gutter 16–40px. Sections breathe: 72–140px vertical padding.
- Cards: 24px radius, 1px hairline borders (`--line`), surface background; featured items get a gradient border.
- RTL: all spacing uses logical properties (`inset-inline`, `margin-inline-start`), so Arabic mirrors automatically. Directional icons (arrows) flip in RTL; phone numbers, emails and URLs stay left-to-right.

## Pages

| Page | Structure |
|---|---|
| Home | Hero (headline word reveal, network canvas, orbiting nest with service chips, quick facts) → service marquee → freelancers vs agency vs ViralNest → why ViralNest (bento) → numbers → services grid → three ways to work → who we work with → work → 8-step process → Work with us band → closing CTA |
| Services | Sticky "on this page" index + one row per category with all sub-services, pricing note, custom-solution note |
| Service | Overview, sub-services as numbered cards (each can be requested), pricing box, related work, CTA |
| Work | Case-study grid with category and industry filters; empty state until case studies are published |
| Case study | Client/industry/year/services, cover, challenge, approach, deliverables, documented outcomes, live links, gallery, next project, "request something similar" |
| Work with us | Why freelancers work with us, specialties, how it works, application form with optional PDF CV |
| About, Contact, Request a Quote, Privacy, 404 | Page hero + focused content |

## Motion

- Scroll reveals (fade + rise, staggered), split-word headings, counters, 3D tilt and spotlight on cards, gradient underline on nav links, magnetic-feeling buttons, a "View" cursor on project and service cards (mouse only), rotating CTA glow, marquee, clip-path mobile menu.
- Everything respects **prefers-reduced-motion**: animations are turned off and content is shown immediately. Without JavaScript all content is visible and both forms still work.
- The hero canvas pauses when off-screen or when the tab is hidden.

## Dashboard (ViralNest Studio)

Its own look, not WordPress: charcoal canvas (#0F1318), sidebar with the nest mark and "Studio", cyan actions, violet accents, rounded cards, KPI tiles with sparklines, status badges with consistent colors (New = cyan, in-progress = violet/amber, Won/Published = green, Lost/Spam = red).

## Accessibility

- Contrast meets WCAG AA on all text tokens.
- Skip link, visible focus rings, keyboard-operable menus (Escape closes), `aria-expanded`/`aria-current`, labelled form fields with inline errors and an error summary that links to each field, `role="status"` for results.
- Touch targets ≥ 40px; no horizontal scrolling at 360px width.
