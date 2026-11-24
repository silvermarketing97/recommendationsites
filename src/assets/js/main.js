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
