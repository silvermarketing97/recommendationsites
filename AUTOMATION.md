# Daily article automation – writer's guide

This file is read by the automated writer (Claude, via a scheduled task) every day.
Follow it exactly. The site owner can edit this file to change the rules.

## Daily job

1. `git pull` the repository `silvermarketing97/recommendationsites` (branch `main`).
2. Count how many posts in `src/posts/` already have today's date (America/Bogota). If there are already **ARTICLES_PER_DAY** or more, stop.
3. Open `content-plan.md` and take the first unchecked topic (`- [ ]`). If none are left, add 20 new topics following the mix below, then take the first.
4. Check that no existing post covers the same search intent (compare titles and slugs in `src/posts/`). If it does, pick the next topic.
5. Research the topic on the web (at least 3–5 searches). Verify every fact, number, bonus and availability claim against current sources. Never invent statistics, earnings or quotes.
6. Write the article (rules below), save it as `src/posts/<slug>.md`, tick the topic in `content-plan.md` (`- [x]`) and add the slug after it.
7. Run `npm ci && npm run build`. If the build fails, fix the article.
8. Commit with the message `New article: <title>` and push to `main`. The GitHub Action publishes it to HostGator automatically.

**ARTICLES_PER_DAY = 1** (increase slowly, never above 3).

## Topic mix (repeat this cycle)

- 40% Swagbucks (how-tos, tips, reviews of features, country guides, comparisons "Swagbucks vs X")
- 20% Paid surveys in general (best practices, country guides for US/UK/CA/AU/IE, scams)
- 20% Make money online (freelancing, selling, apps, digital products)
- 15% Offline side hustles (local, physical work)
- 5% Money tips (budgeting, cashback stacking, scam avoidance)

## Writing rules

- **Language:** English for a US, UK, Canadian, Australian and Irish audience. Use US spelling.
- **Audience:** beginners who want honest, practical advice.
- **Length:** 1,200–2,000 words. Depth over padding.
- **Originality:** write in your own words. Never copy or closely paraphrase another site.
- **Structure:** 2–3 sentence intro answering the search intent fast → a `<div class="key">` short-answer box → H2/H3 sections → at least one comparison table → a conclusion.
- **SEO:** main keyword in the title, first 100 words, one H2 and the slug. Title 50–65 characters. Meta description 140–160 characters. Use 2–4 internal links to existing posts (use their real URLs, e.g. `/swagbucks-review/`).
- **E-E-A-T:** be specific (steps, examples, numbers with sources), state limitations and downsides honestly.
- **No hype:** never promise guaranteed or specific income. Give realistic ranges and say results vary.
- **Swagbucks call-to-action:** every article must mention that new members who join through our link get a **$5 welcome bonus**. Insert `{% swagbucks %}` (or `{% swagbucks "Custom headline" %}`) once in the body, ideally after the first third. The end-of-article CTA and disclosure are added automatically by the template. Never write the referral URL by hand; it lives in `src/_data/site.json`.
- **FAQ:** add 3–5 FAQs in the front matter (`faq:`) answering real questions people search.

## Ads and blocks

- Do not insert ad code in articles; ads are placed automatically from `src/_data/ads.json`.
- Write the article in the markdown body. Leave the `blocks:` field empty; it is for the owner's manual drag-and-drop edits.

## Media rules (images + video in every article)

- **Cover image (required):** a free Unsplash photo. Find one with WebFetch on
  `https://unsplash.com/napi/search/photos?query=<words>&per_page=10`, use only results where `premium` is false, and set
  `image` (the `images.unsplash.com/photo-...` URL without query string), `imageAlt`, `imageCredit` (photographer name) and `imageCreditUrl` (`https://unsplash.com/@username`). Never reuse a cover already used by another post.
- **One more photo in the body:** `{% photo "URL", "alt text", "Photographer", "https://unsplash.com/@username" %}`.
- **Original graphic (when useful):** existing infographics live in `src/assets/img/graphics/`. You may create a new simple SVG infographic in the same style (dark green #0b1f1a / #0f3a2e, brand green #0f9d6b, gold #ffc23d, Arial) and insert it with `{% graphic "file.svg", "alt text" %}`.
- **Video (required when a good one exists):** find a relevant YouTube video, then verify it exists and matches the topic with WebFetch on
  `https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=<ID>&format=json`.
  Add it in the front matter under `videos:` (`id`, `title`, `channel`) or inline with `{% youtube "ID", "Title" %}`. Only use videos from established channels; never use videos that promote scams or other referral links aggressively.
- **Never** download or copy images from Google Images or other websites.

## Front matter template

```yaml
---
title: "..."
description: "..."
date: 2026-11-24T13:00:00Z
category: swagbucks   # paid-surveys | swagbucks | make-money-online | side-hustles | money-tips
tags: [swagbucks, paid surveys]
featured: false
image: https://images.unsplash.com/photo-XXXXXXXX
imageAlt: "..."
imageCredit: "..."
imageCreditUrl: https://unsplash.com/@...
videos:
  - id: XXXXXXXXXXX
    title: "..."
    channel: "..."
faq:
  - q: "..."
    a: "..."
---
```
