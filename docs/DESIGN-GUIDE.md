# ViralNest design guide

The visual system is derived from the only brand asset supplied: the ViralNest logo (a spiral "nest" of signals launching outward, a violet-to-cyan wordmark, on dark charcoal). The idea that runs through the whole site is **a brand spreading**: signals leave a center, travel outward and connect.

Screenshots of every page (desktop 1440 px and mobile 390 px, EN and AR) and of the admin screens are in `design/screenshots/`.

> Brief v2.0 shapes the content and structure (v1.1.0); it does not define a palette. When an official palette arrives, update the values in **ViralNest > Settings > Brand colors** (no code change) or in `src/css/tokens.css` (defaults), then compare with this guide.

## 1. Color

Sampled from the logo, then tuned for WCAG AA contrast on the dark background.

| Token | Hex | Sampled from | Use |
|---|---|---|---|
| `--vn-bg` | `#12161C` | logo background `#171B22` (deepened) | page background |
| `--vn-surface` | `#1E242A` | logo background, lighter area | cards, panels, inputs |
| `--vn-text` | `#F3F5F9` | | body and headings (15.9:1 on bg) |
| `--vn-muted` | `#A7B1BF` | | secondary text (8.4:1 on bg) |
| `--vn-cyan` | `#3DC5E9` | wordmark end `#40D1ED` | primary buttons, links, focus ring (9.4:1 on bg) |
| `--vn-violet` | `#7B4FD0` | wordmark start `#7848C8` | gradient start, accents |
| `--vn-blue` | `#1C67AE` | outer ring | gradient middle, hero rings |
| `--vn-orchid` | `#B96ADD` | ring highlights | hero particles, card glow |
| `--vn-mint` | `#7ADFCE` | launch arrow | eyebrows, counters, success |
| `--vn-on-accent` | `#0B0E13` | | text on cyan buttons (10.2:1) |

Gradients: `--vn-gradient` (violet → blue → cyan, 100°) for bars and outlines; `--vn-gradient-text` (light violet → cyan → mint) for the highlighted headline word.

Rules: cyan is the single action color; gradients decorate, never carry text that must be read in small sizes; errors use `#FF8A8A` on dark surfaces. The plugin checks contrast whenever colors are changed in Settings and lists any failing pair.

## 2. Typography

| Script | Family | Why |
|---|---|---|
| Latin | **Sora** (variable 100–800) | geometric and wide like the logo wordmark, strong at display sizes |
| Arabic | **Readex Pro** (variable 160–700) | modern Arabic designed to pair with geometric Latin; clear at small sizes |

Both are self-hosted woff2 (`assets/fonts/`, ~72 KB total), preloaded, `font-display: swap`. Arabic pages switch the whole stack to Readex Pro (Latin falls back to Sora).

Scale (fluid with `clamp`): display 44→100 px, h1 40→76, h2 32→52, h3 22→30, h4 18→22, lead 18→21, body 17, small 15, xs 13. Line heights: 1.05 display, 1.15 headings, 1.65 body (Arabic body 1.8, Arabic headings 1.35). Headings weight 700 with −0.02em tracking in Latin; no negative tracking in Arabic.

## 3. Space, layout, shape

- Spacing scale (4 px base): 4, 8, 12, 16, 24, 32, 48, 64, 96, 128. Section padding `clamp(64px … 128px)`.
- Container 1240 px with fluid gutter 16→40 px.
- Breakpoints: **sm 480**, **md 720**, **lg 1024** (desktop navigation appears), **xl 1280**.
- Radii: 10 (controls), 16 (cards), 24 (panels, media), 36 (CTA band), pill (buttons, chips, language switch).
- Depth: one long soft shadow `0 24px 60px -24px rgb(0 0 0 / .6)`, plus glows from the brand colors instead of more shadows.

## 4. RTL

- `<html lang="ar" dir="rtl">` on every `/ar/` page. Layout uses logical properties (`margin-inline`, `inset-inline-start`, `padding-inline`), so it mirrors automatically.
- Direction-dependent icons (arrows) flip in RTL; brand mark, check marks and media do not.
- Phone numbers, emails and URLs are always `dir="ltr"` inside Arabic text.
- Numbers use Western digits in both languages (common in Egyptian business use; consistent with phone numbers).

## 5. Components

| Component | Notes |
|---|---|
| Logo | The supplied ViralNest logo as delivered: nest mark above the wordmark, cut out of its dark background (the second tagline line of the file is left out). Header 72 px tall (60 px on tablet/mobile), footer 160 px. An uploaded logo in Settings > Brand replaces it. |
| Header | Sticky; gains a blurred background and hairline after scrolling. Desktop: nav, "Request a Quote" button, EN/عربي switch. Mobile (<1024): switch stays visible, nav opens as a full-height panel (Escape closes, focus moves into it). |
| Language switch | Pill with the current language highlighted; links to the equivalent page; keeps the quote-form selection. |
| Buttons | `primary` (cyan, dark text), `ghost` (outline), sizes `sm`/`lg`. Arrow nudges 3 px on hover; focus ring 2 px cyan with offset. Min touch target 44 px. |
| Service card | Icon, title, summary, sub-service chips. Hover: a radial glow follows the pointer; lifts 4 px. |
| Project card | Masked cover image (reveals top-to-bottom on scroll), industry + services chips, subtle 3D tilt on fine pointers. |
| Comparison ("the problem") | Three columns: freelancers and traditional agencies (muted, close icons) vs. ViralNest (highlighted border, brand mark, check icons). Stacks on mobile. |
| Value list | Five items with a thin gradient line on top; 1 → 2 → 3 columns by width. |
| Three ways to work | Model cards plus a note that a full package is never required. |
| Audience | Who we work with, markets line with globe icon, industries as large chips. Shared by Home and About. |
| Story + voice (About) | Two-column story, model/approach/mission panels, brand-voice list with check marks. |
| Process steps | Eight client-journey steps, numbered, connector line draws in when visible. |
| CTA band | Gradient panel with slow-moving light. |
| Quote form | Grouped service checkboxes (category + sub-services), counter, email, phone with country selector (EG, US and Gulf first), optional fields, privacy note, error summary with links to fields, inline errors, success panel with reference number. |
| Contact cards | Only for configured channels; otherwise a clear fallback to the quote form. |
| Consent banner | Bottom card, equal-weight Decline/Allow buttons, link to privacy. Reopen from the footer. |
| Floating WhatsApp | Only when a number is set. |
| Empty states | Honest text (e.g. "Case studies are being prepared…") with a quote CTA. |

## 6. Motion

No animation library is used: CSS transitions and keyframes, IntersectionObserver, one shared scroll loop (`src/js/motion.js`) and one `<canvas>`. JS on a normal page: 15 KB (5 KB gzipped), +4 KB hero, +7 KB form, each loaded only where needed.

| Motion | Spec |
|---|---|
| **Signature hero, "brand spreading"** (`src/js/hero.js`) | A glowing nest node emits a wave of signals every 1.4 s (9 desktop / 6 mobile, max 90). Signals travel outward on curved paths with fading trails and link to neighbors closer than 16% of the canvas, forming a living network; rings pulse every 2.6 s. Canvas DPR capped at 2. Starts after first paint during idle time; the headline, text and CTAs are plain HTML and fully visible on first render. Pauses when the hero leaves the viewport or the tab is hidden. |
| Headline | Words rise 0.35 em over 900 ms with a 90 ms stagger (transform only, so text is readable from the first frame); the gradient word slowly shifts its gradient (6 s, alternating). |
| Reveal on scroll | Elements with `data-reveal`: fade + 28 px rise, 800 ms `cubic-bezier(.16,1,.3,1)`, stagger 80 ms via `--i`. Runs once. |
| Image masks | Project covers reveal with `clip-path: inset()` over 1 s. |
| Hover/focus/tap | 160 ms color, 320 ms transform with a slight spring (`cubic-bezier(.34,1.56,.64,1)`); checkboxes pop; buttons press to 0.97. |
| Page transitions | Cross-document View Transitions (Chrome, Edge, Safari 18+): the old page fades up and blurs out (260 ms), the new one rises in (520 ms). Other browsers navigate normally. |
| Reading progress | 3 px gradient bar at the top of the window that fills as you scroll. |
| Smart header | Slides away while you read down, returns as soon as you scroll up, focus enters it, or the menu is open. |
| Split headings | Section titles rise word by word from behind a mask (900 ms, 55 ms per word, slight rotation that mirrors in Arabic). The text stays real text for search engines and screen readers. |
| Hero on scroll | Visual drifts down and scales up 12%, text lifts and fades as you leave the hero; scroll cue on large screens. |
| Moving band | Two rows after the hero: service categories (large, gradient on alternate items) and industries (small, opposite direction). 48 s / 64 s loops, pause on hover. Hidden from screen readers (it repeats content listed below). |
| Counters | The four stats (service categories, services, ways to work, journey steps) are counted from the live catalog and content, and count up once when visible. No invented numbers. |
| Process line | As you scroll, each step's line fills and its dot lights up; steps not yet reached are dimmed. |
| Pointer effects (mouse only) | Magnetic buttons (pull up to 8 px), spotlight that follows the pointer on cards, and a custom cursor: a dot plus a trailing ring that grows over links and turns into a "View" label over service and project cards. The system cursor stays visible. |
| Footer statement | Large "Let's make your brand spread." link to the quote page; the gradient sweeps through the text and the arrow turns on hover. |
| Back to top | Round button with a ring that fills with reading progress; appears after 700 px. |
| Reduced motion | `prefers-reduced-motion: reduce` → hero script is never downloaded, canvas hidden and replaced by the static gradient/rings; all reveals visible immediately; transitions ~0 ms; smooth scrolling off; no page transitions, split headings, parallax, magnetic or cursor effects; counters show the final number; the moving band is hidden. |

All timings are tokens in `src/css/tokens.css` (`--dur-*`, `--ease-*`, `--stagger`) and `CONFIG` at the top of `hero.js`.

## 7. Imagery and icons

- Icons: a single inline SVG set (`vn_icon()`), 1.75 px stroke, round caps, `currentColor`.
- Brand images (`data/brand/`) are cut from the supplied logo: full logo (used in header and footer), mark (favicon, small accents), 512 px icon, 1200×630 share image.
- Photos/case-study images: not supplied. Use real project images only; 16:10 covers; WordPress generates `srcset`; all images lazy-load except the first visible one.

## 8. Accessibility checklist built in

Skip link, landmarks, one `h1` per page, visible focus ring, 44 px targets, labels tied to inputs, `aria-invalid` + `aria-describedby` errors, error summary focused on submit, live region for status, form usable without JavaScript, contrast AA checked, alt text fields in EN and AR, language attributes on mixed-language snippets.
