import markdownIt from "markdown-it";
import markdownItAnchor from "markdown-it-anchor";
import fs from "node:fs";

const readSite = () => JSON.parse(fs.readFileSync("src/_data/site.json", "utf8"));

const slugify = (s) =>
  String(s)
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/&[a-z]+;/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

const escapeHtml = (s) =>
  String(s ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

export default function (eleventyConfig) {
  // ---------- Markdown ----------
  const md = markdownIt({ html: true, linkify: true, typographer: true }).use(
    markdownItAnchor,
    { slugify, level: [2, 3] }
  );
  // External links open in a new tab, safely
  const defaultLinkOpen =
    md.renderer.rules.link_open ||
    ((tokens, idx, options, env, self) => self.renderToken(tokens, idx, options));
  md.renderer.rules.link_open = (tokens, idx, options, env, self) => {
    const href = tokens[idx].attrGet("href") || "";
    if (/^https?:\/\//.test(href) && !href.includes("recommendationsites.com")) {
      tokens[idx].attrSet("target", "_blank");
      const sponsored = /swagbucks\.com|surveystime\.site/.test(href) ? "sponsored noopener" : "noopener";
      tokens[idx].attrSet("rel", sponsored);
    }
    return defaultLinkOpen(tokens, idx, options, env, self);
  };
  // Lazy images
  md.renderer.rules.image = (tokens, idx) => {
    const t = tokens[idx];
    const src = t.attrGet("src");
    const alt = escapeHtml(t.content);
    const title = t.attrGet("title");
    return `<img src="${src}" alt="${alt}" loading="lazy" decoding="async"${
      title ? ` title="${escapeHtml(title)}"` : ""
    }>`;
  };
  eleventyConfig.setLibrary("md", md);

  // ---------- Passthrough ----------
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  eleventyConfig.addPassthroughCopy({ "src/admin": "admin" });
  eleventyConfig.ignores.add("src/admin/**");
  eleventyConfig.addPassthroughCopy({ "src/static": "/" });

  // ---------- Collections ----------
  eleventyConfig.addCollection("posts", (api) =>
    api
      .getFilteredByGlob("src/posts/*.md")
      .filter((p) => !p.data.draft && p.date <= new Date())
      .sort((a, b) => b.date - a.date)
  );

  // ---------- Filters ----------
  eleventyConfig.addFilter("slugify", slugify);
  eleventyConfig.addFilter("isoDate", (d) => new Date(d).toISOString());
  eleventyConfig.addFilter("niceDate", (d) =>
    new Date(d).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      timeZone: "UTC",
    })
  );
  eleventyConfig.addFilter("readingTime", (html) => {
    const words = String(html || "").replace(/<[^>]+>/g, " ").split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 230));
  });
  eleventyConfig.addFilter("byCategory", (posts, cat) =>
    (posts || []).filter((p) => p.data.category === cat)
  );
  eleventyConfig.addFilter("related", (posts, page, cat, n = 3) => {
    const same = (posts || []).filter((p) => p.url !== page.url && p.data.category === cat);
    const others = (posts || []).filter((p) => p.url !== page.url && p.data.category !== cat);
    return [...same, ...others].slice(0, n);
  });
  eleventyConfig.addFilter("limit", (arr, n) => (arr || []).slice(0, n));
  eleventyConfig.addFilter("skip", (arr, n) => (arr || []).slice(n));
  eleventyConfig.addFilter("featuredFirst", (arr) =>
    [...(arr || [])].sort((a, b) => (b.data.featured ? 1 : 0) - (a.data.featured ? 1 : 0))
  );
  eleventyConfig.addFilter("jsonify", (v) => JSON.stringify(v));
  eleventyConfig.addFilter("absUrl", (path, base) => new URL(path, base).toString());
  eleventyConfig.addFilter("imgSize", (src, w) => {
    if (!src) return src;
    if (/images\.unsplash\.com/.test(src)) {
      const u = new URL(src);
      u.searchParams.set("w", w);
      u.searchParams.set("q", "75");
      u.searchParams.set("auto", "format");
      u.searchParams.set("fit", "crop");
      return u.toString();
    }
    return src;
  });
  // Table of contents from rendered H2s
  eleventyConfig.addFilter("toc", (html) => {
    const out = [];
    const re = /<h2[^>]*id="([^"]+)"[^>]*>(.*?)<\/h2>/g;
    let m;
    while ((m = re.exec(String(html || "")))) {
      out.push({ id: m[1], text: m[2].replace(/<[^>]+>/g, "") });
    }
    return out;
  });
  eleventyConfig.addFilter("categoryName", (slug, cats) => {
    const c = (cats || []).find((x) => x.slug === slug);
    return c ? c.name : slug;
  });

  // Markdown filter for block text
  eleventyConfig.addFilter("md", (str) => md.render(String(str || "")));
  eleventyConfig.addFilter("mdInline", (str) => md.renderInline(String(str || "")));

  // ---------- Ads & HTML widgets ----------
  const adBox = (slot, label = "Advertisement") => {
    if (!slot || !slot.code || slot.enabled === false) return "";
    const fmt = escapeHtml(slot.format || "horizontal");
    const hideMobile = slot.mobile === false ? " ad--desktop-only" : "";
    return `<div class="ad ad--${fmt}${hideMobile}"><span class="ad__label">${escapeHtml(label)}</span><div class="ad__slot">${slot.code}</div></div>`;
  };
  eleventyConfig.addFilter("adSlot", (slot, label) => adBox(slot, label));
  // Widgets placed by position
  eleventyConfig.addFilter("widgetsAt", (widgets, place) =>
    (widgets || [])
      .filter((w) => w && w.enabled !== false && w.placement === place && w.code)
      .map((w) => (w.wrap === false ? w.code : `<div class="widget widget--${escapeHtml(place)}">${w.code}</div>`))
      .join("\n")
  );
  // Insert in-article ads before every Nth H2 (starting at the 2nd heading)
  eleventyConfig.addFilter("injectAds", (html, ads) => {
    const slot = ads && ads.inArticle;
    const box = adBox(slot, ads && ads.label);
    if (!box) return html;
    const every = Math.max(1, parseInt(slot.everyHeadings || 2, 10));
    const max = Math.max(1, parseInt(slot.max || 3, 10));
    let count = 0, placed = 0;
    return String(html).replace(/<h2[\s>]/g, (m) => {
      count++;
      if (count > 1 && (count - 1) % every === 0 && placed < max) {
        placed++;
        return box + m;
      }
      return m;
    });
  });

  // ---------- Shortcodes (usable inside articles) ----------
  // {% swagbucks %}  -> Swagbucks call-to-action box
  eleventyConfig.addShortcode("swagbucks", function (headline) {
    const site = readSite();
    const h = escapeHtml(headline || `Get a ${site.swagbucksBonus || "$5"} welcome bonus on Swagbucks`);
    return `<aside class="cta-box" aria-label="Swagbucks offer">
  <div class="cta-box__badge">${escapeHtml(site.swagbucksBonus || "$5")}<small>bonus</small></div>
  <div class="cta-box__body">
    <p class="cta-box__title">${h}</p>
    <p class="cta-box__text">Join free through our link to unlock your ${escapeHtml(
      site.swagbucksBonus || "$5"
    )} welcome bonus, then earn points for surveys, cashback and more. Redeem for PayPal cash or gift cards.</p>
    <a class="btn btn--gold" href="${escapeHtml(site.swagbucksUrl)}" target="_blank" rel="sponsored noopener" data-cta="swagbucks-inline">Claim my ${escapeHtml(
      site.swagbucksBonus || "$5"
    )} bonus &rarr;</a>
    <p class="cta-box__fine">${escapeHtml(site.bonusNote || "")}</p>
  </div>
</aside>`;
  });

  // {% youtube "VIDEO_ID", "Video title" %} -> lightweight, click-to-load YouTube player
  eleventyConfig.addShortcode("youtube", (id, title = "Video") => {
    const t = escapeHtml(title);
    const i = escapeHtml(id);
    return `<figure class="video">
  <button class="video__btn" data-yt="${i}" aria-label="Play video: ${t}" style="background-image:url('https://i.ytimg.com/vi/${i}/hqdefault.jpg')">
    <span class="video__play" aria-hidden="true"></span>
  </button>
  <figcaption>&#9654; ${t}</figcaption>
</figure>`;
  });

  // {% photo "url", "alt text", "Photographer", "https://unsplash.com/@user" %}
  eleventyConfig.addShortcode("photo", (src, alt, credit, creditUrl) => {
    const s = /images\.unsplash\.com/.test(src)
      ? (() => {
          const u = new URL(src);
          u.searchParams.set("w", "1200");
          u.searchParams.set("q", "75");
          u.searchParams.set("auto", "format");
          u.searchParams.set("fit", "crop");
          return u.toString();
        })()
      : src;
    const cap = credit
      ? `<figcaption>Photo: ${
          creditUrl
            ? `<a href="${escapeHtml(creditUrl)}?utm_source=recommendationsites&utm_medium=referral" target="_blank" rel="noopener">${escapeHtml(credit)}</a>`
            : escapeHtml(credit)
        } on Unsplash</figcaption>`
      : "";
    return `<figure class="photo"><img src="${escapeHtml(s)}" alt="${escapeHtml(
      alt
    )}" loading="lazy" decoding="async" width="1200" height="800">${cap}</figure>`;
  });

  // {% graphic "file.svg", "alt text" %} -> original infographic from /assets/img/graphics/
  eleventyConfig.addShortcode("graphic", (file, alt) =>
    `<figure class="graphic"><img src="/assets/img/graphics/${escapeHtml(file)}" alt="${escapeHtml(
      alt
    )}" loading="lazy" decoding="async"></figure>`
  );

  return {
    dir: { input: "src", includes: "_includes", data: "_data", output: "_site" },
    markdownTemplateEngine: "njk",
    htmlTemplateEngine: "njk",
    templateFormats: ["md", "njk", "html"],
  };
}
