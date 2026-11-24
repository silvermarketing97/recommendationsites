// Mobile menu
const toggle = document.querySelector(".nav-toggle");
const nav = document.getElementById("nav");
if (toggle && nav) {
  toggle.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", String(open));
  });
}

// Click-to-load YouTube (keeps pages fast)
document.querySelectorAll("[data-yt]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.getAttribute("data-yt");
    const iframe = document.createElement("iframe");
    iframe.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`;
    iframe.title = btn.getAttribute("aria-label") || "YouTube video";
    iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
    iframe.allowFullscreen = true;
    btn.replaceWith(iframe);
  });
});

// Sticky mobile call-to-action appears after scrolling a bit
const sticky = document.querySelector(".sticky-cta");
if (sticky) {
  const onScroll = () => sticky.classList.toggle("is-visible", window.scrollY > 600);
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();
}

// Highlight current section in the table of contents
const tocLinks = document.querySelectorAll(".toc a");
if (tocLinks.length && "IntersectionObserver" in window) {
  const map = new Map([...tocLinks].map((a) => [a.getAttribute("href").slice(1), a]));
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          tocLinks.forEach((a) => a.classList.remove("is-active"));
          map.get(e.target.id)?.classList.add("is-active");
        }
      });
    },
    { rootMargin: "-20% 0px -70% 0px" }
  );
  map.forEach((_, id) => {
    const el = document.getElementById(id);
    if (el) io.observe(el);
  });
}

// Track referral clicks in Google Analytics (if enabled)
document.querySelectorAll("[data-cta]").forEach((a) =>
  a.addEventListener("click", () => {
    if (window.gtag) window.gtag("event", "referral_click", { placement: a.dataset.cta });
  })
);

// ---------- Self-hosted analytics (no cookies, no external services) ----------
(() => {
  try { if (localStorage.getItem("rs_notrack")) return; } catch (e) {}
  if (navigator.webdriver) return;
  const EP = "/api/p.php";
  const rid = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
  let sid = "s";
  try {
    sid = sessionStorage.getItem("rs_sid") || rid();
    sessionStorage.setItem("rs_sid", sid);
  } catch (e) {}
  const pv = rid();
  const send = (o) => {
    const body = JSON.stringify(Object.assign({ s: sid, v: pv }, o));
    try {
      if (navigator.sendBeacon && navigator.sendBeacon(EP, new Blob([body], { type: "text/plain" }))) return;
    } catch (e) {}
    fetch(EP, { method: "POST", body, keepalive: true, headers: { "Content-Type": "text/plain" } }).catch(() => {});
  };
  const q = new URLSearchParams(location.search);
  let tz = "";
  try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ""; } catch (e) {}
  send({
    t: "pv", p: location.pathname, ti: document.title, r: document.referrer,
    us: q.get("utm_source") || "", um: q.get("utm_medium") || "", uc: q.get("utm_campaign") || "",
    tz, sw: screen.width, lang: navigator.language || ""
  });
  // Time on page (only while the tab is visible)
  let visible = 0, start = document.visibilityState === "visible" ? Date.now() : 0;
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      if (start) visible += Date.now() - start;
      start = 0;
      send({ t: "end", d: Math.round(visible / 1000) });
    } else start = Date.now();
  });
  // Swagbucks referral clicks (any element with data-cta)
  document.addEventListener("click", (e) => {
    const a = e.target.closest && e.target.closest("[data-cta]");
    if (a) send({ t: "ev", n: "referral_click", l: a.getAttribute("data-cta") || "", p: location.pathname });
  }, true);
})();
