<?php
require dirname(__DIR__) . '/_auth.php';
rs_require_login();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Estadísticas · Recommendation Sites</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<style>
:root{--ink:#0b1f1a;--ink2:#123129;--green:#0f9d6b;--green2:#0b7f56;--mint:#d9f5e9;--gold:#ffc23d;--line:#e3ebe7;--muted:#5d6f69;--bg:#f4f8f6;--red:#d4482a}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:#22302c;font-size:14px}
header{background:var(--ink);color:#fff;position:sticky;top:0;z-index:10}
.hin{max-width:1320px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.hin img{width:30px;height:30px}.hin h1{font-size:17px;margin:0;flex:1;min-width:180px}
.hin a{color:#cfe9df;text-decoration:none;font-weight:600;font-size:13px;padding:8px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.18)}
.hin a.gold{background:var(--gold);color:#2a1d00;border:0}
main{max-width:1320px;margin:0 auto;padding:20px}
.bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.tabs{display:flex;background:#fff;border:1px solid var(--line);border-radius:999px;padding:4px;gap:2px;flex-wrap:wrap}
.tabs button{border:0;background:none;padding:8px 13px;border-radius:999px;font-weight:600;font-size:13px;color:var(--muted);cursor:pointer}
.tabs button.on{background:var(--ink);color:#fff}
.custom{display:none;gap:6px;align-items:center}.custom.on{display:flex}
.custom input{border:1px solid var(--line);border-radius:10px;padding:7px 8px;font:inherit}
.custom button{border:0;background:var(--green);color:#fff;border-radius:10px;padding:8px 12px;font-weight:700;cursor:pointer}
.live{margin-left:auto;display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:999px;padding:8px 14px;font-weight:700}
.dot{width:9px;height:9px;border-radius:50%;background:#16c47f;box-shadow:0 0 0 0 rgba(22,196,127,.6);animation:pulse 1.8s infinite}
@keyframes pulse{70%{box-shadow:0 0 0 10px rgba(22,196,127,0)}100%{box-shadow:0 0 0 0 rgba(22,196,127,0)}}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px 18px}
.kpi .l{color:var(--muted);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.kpi .v{font-size:28px;font-weight:800;color:var(--ink);margin:6px 0 2px;letter-spacing:-.02em}
.kpi .c{font-size:12px;font-weight:700}.up{color:var(--green)}.down{color:var(--red)}.flat{color:var(--muted)}
.kpi.gold{background:linear-gradient(160deg,var(--ink),#174c3c);border:0}.kpi.gold .l{color:#9fd8c3}.kpi.gold .v{color:var(--gold)}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:18px}
.card h2{font-size:15px;margin:0 0 12px;color:var(--ink);display:flex;justify-content:space-between;align-items:center;gap:10px}
.card h2 small{font-weight:500;color:var(--muted);font-size:12px}
.legend{display:flex;gap:16px;font-size:12px;color:var(--muted);font-weight:600}.legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:-1px}
#chart{width:100%;height:280px;position:relative}
#chart svg{width:100%;height:100%;overflow:visible}
.tip{position:absolute;pointer-events:none;background:var(--ink);color:#fff;padding:8px 10px;border-radius:10px;font-size:12px;white-space:nowrap;transform:translate(-50%,-110%);display:none}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
.grid2 .card,.grid3 .card{margin-bottom:0}
.rows{display:grid;gap:4px}
.row{position:relative;display:grid;grid-template-columns:1fr auto auto;gap:12px;align-items:center;padding:8px 10px;border-radius:8px;overflow:hidden}
.row .fill{position:absolute;left:0;top:0;bottom:0;background:var(--mint);border-radius:8px;z-index:0}
.row>*:not(.fill){position:relative;z-index:1}
.row .n{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500}
.row .n small{display:block;color:var(--muted);font-weight:400;font-size:11px;overflow:hidden;text-overflow:ellipsis}
.row .a{font-weight:700;color:var(--ink);min-width:44px;text-align:right}.row .b{color:var(--muted);min-width:56px;text-align:right;font-size:12px}
.rhead{display:grid;grid-template-columns:1fr auto auto;gap:12px;padding:0 10px 6px;color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.rhead span:nth-child(2){min-width:44px;text-align:right}.rhead span:nth-child(3){min-width:56px;text-align:right}
.empty{color:var(--muted);padding:12px 10px;font-size:13px}
.recent{display:grid;gap:6px;font-size:13px}.recent div{display:flex;gap:10px;justify-content:space-between;border-bottom:1px dashed var(--line);padding:6px 0}
.recent span:first-child{color:var(--muted);min-width:46px}.recent b{flex:1;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.note{font-size:12px;color:var(--muted);margin-top:6px}
.err{background:#fff1ee;border:1px solid #ffd1c6;color:#a3361a;padding:14px;border-radius:12px;margin-bottom:18px;display:none}
.loading{opacity:.5;transition:opacity .2s}
.alerts{display:grid;gap:10px;margin-bottom:18px}
.alert{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:14px;font-size:14px;line-height:1.45}
.alert b{display:block;font-size:15px;margin-bottom:2px}
.alert.warn{background:#fff8e6;border:1px solid #ffe2a1;color:#6b4a00}
.alert.bad{background:#fff1ee;border:1px solid #ffd1c6;color:#8a2c14}
.alert.ok{background:#e9f8f1;border:1px solid #bfe9d5;color:#0b5c3f}
.alert.info{background:#eef4ff;border:1px solid #cddcff;color:#1f3f8a}
.alert .ic{font-size:22px;line-height:1}
.section-title{display:flex;align-items:center;gap:10px;margin:26px 0 12px;font-size:18px;color:var(--ink)}
.section-title small{font-size:13px;color:var(--muted);font-weight:500}
.kpis.bots .kpi{background:#f7f9fc;border-color:#e2e8f2}
.cat{display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:#eef1f6;color:#4a5a77;margin-left:6px;vertical-align:1px;text-transform:uppercase;letter-spacing:.03em}
.cat.ai{background:#efe7ff;color:#5b2fc0}.cat.search{background:#e6efff;color:#1f4fb5}.cat.seo{background:#fff1c9;color:#8a5a00}.cat.social{background:#e9f8f1;color:#0b7f56}.cat.script{background:#ffe9e2;color:#b1401b}
.row.botrow .fill{background:#e8edf6}
.src{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.src i{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;font-style:normal;font-size:12px;font-weight:800;color:#fff;flex:0 0 auto}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:6px 8px;border-bottom:1px solid var(--line)}
.tbl td{padding:9px 8px;border-bottom:1px dashed var(--line);vertical-align:middle}
.tbl td.u{max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tbl a,.rows a{color:var(--green2);text-decoration:none}.tbl a:hover{text-decoration:underline}
.tblwrap{overflow-x:auto}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:6px}
.chip2{background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-width:120px}
.chip2 b{display:block;font-size:20px;color:var(--ink)}.chip2 span{font-size:12px;color:var(--muted)}
@media(max-width:1000px){.kpis{grid-template-columns:repeat(2,1fr)}.grid3{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.grid2,.grid3{grid-template-columns:1fr}.kpi .v{font-size:23px}.live{margin-left:0}main{padding:14px}}
</style>
</head>
<body>
<header><div class="hin">
  <img src="/assets/img/logo.svg" alt="">
  <h1>📊 Estadísticas · Recommendation Sites</h1>
  <a href="/" target="_blank">Ver sitio</a>
  <a class="gold" href="/admin/">Panel</a>
  <a href="/admin/logout.php">Cerrar sesión</a>
</div></header>
<main id="app">
  <div class="bar">
    <div class="tabs" id="tabs">
      <button data-r="today">Hoy</button><button data-r="yesterday">Ayer</button><button data-r="7d">7 días</button>
      <button data-r="30d" class="on">30 días</button><button data-r="90d">90 días</button><button data-r="12m">12 meses</button><button data-r="custom">Personalizado</button>
    </div>
    <div class="custom" id="custom"><input type="date" id="from"> – <input type="date" id="to"><button id="apply">Aplicar</button></div>
    <div class="live"><span class="dot"></span><span id="active">0</span>&nbsp;visitantes ahora</div>
  </div>
  <div class="err" id="err"></div>
  <div class="alerts" id="alerts"></div>

  <div class="kpis" id="kpis"></div>

  <div class="card">
    <h2><span>Visitas en el tiempo <small id="rangeLabel"></small></span>
      <span class="legend"><span><i style="background:#0f9d6b"></i>Visitantes</span><span><i style="background:#ffc23d"></i>Páginas vistas</span><span><i style="background:#8b9bb8"></i>Robots</span></span></h2>
    <div id="chart"><div class="tip" id="tip"></div></div>
  </div>

  <h2 class="section-title">🔗 ¿De dónde vienen tus visitas? <small>referer / origen</small></h2>
  <div class="card"><div class="chips" id="channelChips"></div>
    <p class="note">Por qué canal llegan las personas: buscadores (Google, Bing…), redes sociales (Facebook, Instagram, TikTok…), otros sitios web, asistentes de IA, campañas o directo (escribieron tu dirección, un enlace guardado o una app que no informa el origen).</p></div>
  <div class="grid2" style="margin-bottom:18px">
    <div class="card"><h2>Fuentes <small>Google, Facebook, Instagram…</small></h2><div id="sources"></div></div>
    <div class="card"><h2>Referer exacto <small>página desde donde llegaron</small></h2><div id="refUrls"></div>
      <p class="note">Google y la mayoría de buscadores no envían la palabra buscada; para ver eso usa Google Search Console.</p></div>
  </div>
  <div class="card"><h2>Últimas llegadas <small>cada visita nueva, con su origen</small></h2><div class="tblwrap" id="arrivals"></div></div>

  <div class="grid2" style="margin-bottom:18px">
    <div class="card"><h2>Páginas más vistas <small>visitas · tiempo prom.</small></h2><div id="pages"></div></div>
    <div class="card"><h2>Páginas de entrada <small>donde empiezan las visitas</small></h2><div id="entryPages"></div></div>
  </div>

  <div class="grid2" style="margin-bottom:18px">
    <div class="card"><h2>⭐ Clics en tu link de Swagbucks <small>por ubicación del botón</small></h2><div id="clickPlacements"></div>
      <h2 style="margin-top:18px">Páginas que más generan clics</h2><div id="clickPages"></div></div>
    <div class="card"><h2>🟢 En vivo <small>últimas visitas</small></h2><div id="recent" class="recent"></div>
      <h2 style="margin-top:18px">Páginas activas (5 min)</h2><div id="livePages"></div></div>
  </div>

  <div class="grid3" style="margin-bottom:18px">
    <div class="card"><h2>Países</h2><div id="countries"></div></div>
    <div class="card"><h2>Dispositivos</h2><div id="devices"></div><h2 style="margin-top:18px">Sistemas</h2><div id="os"></div></div>
    <div class="card"><h2>Navegadores</h2><div id="browsers"></div><h2 style="margin-top:18px">Idiomas</h2><div id="languages"></div></div>
  </div>

  <div class="grid2">
    <div class="card"><h2>Sitios que te enlazan <small>dominio</small></h2><div id="referrers"></div></div>
    <div class="card"><h2>Campañas (UTM)</h2><div id="campaigns"></div>
      <p class="note">Agrega <b>?utm_campaign=nombre</b> a tus enlaces en redes para medirlos aquí.</p></div>
  </div>
  <h2 class="section-title">🤖 Robots y rastreadores <small>visitas automáticas (no son personas)</small></h2>
  <div class="kpis bots" id="botKpis"></div>
  <div class="grid2" style="margin-bottom:18px">
    <div class="card"><h2>Robots que visitaron tu sitio <small>visitas · última vez</small></h2><div id="botNames"></div></div>
    <div class="card"><h2>Tipos de robots</h2><div id="botCats"></div>
      <h2 style="margin-top:18px">Páginas más rastreadas</h2><div id="botPaths"></div>
      <h2 style="margin-top:18px">⚠️ Páginas no encontradas (404) pedidas por robots</h2><div id="botErrors"></div></div>
  </div>
  <p class="note">🔎 <b>Buscadores</b> (Google, Bing…) es bueno: significa que te están indexando. 🤖 <b>IA</b> son rastreadores de ChatGPT, Claude, Perplexity, etc. 🛠️ <b>Herramientas SEO</b> son servicios como Ahrefs o Semrush. ⚙️ <b>Scripts</b> pueden ser escaneos automáticos; si son muchos, avísame.</p>
  <p class="note" style="margin-top:16px">Estadísticas propias guardadas en tu hosting. Sin cookies ni servicios externos. Los visitantes únicos se cuentan por día de forma anónima, así que una persona que vuelve otro día se cuenta de nuevo. Tus visitas como administrador no se cuentan en los navegadores donde inicies sesión. Horario: Colombia.</p>
</main>

<script>
const $ = (id) => document.getElementById(id);
const fmt = (n) => Number(n || 0).toLocaleString('es-CO');
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const dur = (s) => { s = Math.round(s || 0); if (!s) return '—'; const m = Math.floor(s / 60), r = s % 60; return m ? `${m}m ${r}s` : `${r}s`; };
const flag = (cc) => cc && cc.length === 2 ? String.fromCodePoint(...[...cc.toUpperCase()].map((c) => 127397 + c.charCodeAt())) : '🌐';
const countryNames = (() => { try { return new Intl.DisplayNames(['es'], { type: 'region' }); } catch (e) { return null; } })();
const cName = (cc) => (countryNames && cc ? countryNames.of(cc) : cc) || 'Desconocido';
const placementNames = { topbar: 'Barra superior', nav: 'Botón del menú', hero: 'Portada (inicio)', spotlight: 'Franja Swagbucks (inicio)', sidebar: 'Barra lateral', 'swagbucks-inline': 'Recuadro dentro del artículo', 'post-end': 'Final del artículo', sticky: 'Botón fijo (celular)', block: 'Bloque de botón' };
const deviceNames = { Mobile: '📱 Celular', Desktop: '💻 Computador', Tablet: '📲 Tablet' };
const channelNames = { Search: '🔎 Buscadores', Direct: '➡️ Directo', Social: '💬 Redes sociales', Referral: '🔗 Otros sitios', Campaign: '📣 Campañas', AI: '🤖 Asistentes IA' };

let range = '30d', from = '', to = '';
const srcStyle = { Google: ['G', '#4285f4'], Bing: ['B', '#008373'], Yahoo: ['Y', '#6001d2'], DuckDuckGo: ['D', '#de5833'], Facebook: ['f', '#1877f2'], Instagram: ['IG', '#e1306c'], 'X / Twitter': ['X', '#000'], LinkedIn: ['in', '#0a66c2'], Pinterest: ['P', '#e60023'], Reddit: ['R', '#ff4500'], YouTube: ['▶', '#ff0000'], TikTok: ['♪', '#111'], WhatsApp: ['W', '#25d366'], Telegram: ['T', '#229ed9'], Messenger: ['M', '#0084ff'], Quora: ['Q', '#b92b27'], ChatGPT: ['AI', '#10a37f'], Claude: ['AI', '#d97757'], Perplexity: ['AI', '#20808d'], Gemini: ['AI', '#4285f4'], Copilot: ['AI', '#0078d4'], Direct: ['→', '#5d6f69'] };
const srcBadge = (name, medium) => { const st = srcStyle[name] || (medium === 'Referral' ? ['🔗', '#8b9bb8'] : medium === 'Campaign' ? ['📣', '#f5a400'] : [String(name || '?').slice(0, 1).toUpperCase(), '#8b9bb8']); return `<span class="src"><i style="background:${st[1]}">${st[0]}</i>${esc(name === 'Direct' ? 'Directo' : name)}</span>`; };
const catNames = { 'Search engine': ['🔎 Buscadores', 'search'], 'AI': ['🤖 Inteligencia artificial', 'ai'], 'SEO tool': ['🛠️ Herramientas SEO', 'seo'], 'Social preview': ['💬 Vista previa redes', 'social'], 'Monitoring': ['📡 Monitoreo', ''], 'Script / unknown': ['⚙️ Scripts / desconocidos', 'script'], 'Other bot': ['❔ Otros robots', ''] };
const ago = (ts) => { if (!ts) return 'nunca'; const s = Math.floor(Date.now() / 1000 - ts); if (s < 60) return 'hace segundos'; if (s < 3600) return `hace ${Math.floor(s / 60)} min`; if (s < 86400) return `hace ${Math.floor(s / 3600)} h`; return `hace ${Math.floor(s / 86400)} días`; };

function alerts(h, k, b, r) {
  const out = [];
  const started = h.firstData && (h.now - h.firstData) > 86400;
  if (!h.lastHuman) out.push(['info', '📭', 'Todavía no hay visitas de personas', 'Es normal en un sitio nuevo: Google puede tardar días o semanas en empezar a mostrarlo. Comparte tus artículos en redes y registra el sitio en Google Search Console para acelerar.']);
  else if (h.daysWithoutVisits >= 3) out.push(['bad', '🚨', `${h.daysWithoutVisits} días seguidos sin visitas de personas`, `La última visita fue ${ago(h.lastHuman)}. Revisa que el sitio cargue bien y que el contador funcione. Si todo está bien, comparte contenido para atraer tráfico.`]);
  else if (h.daysWithoutVisits >= 1) out.push(['warn', '⚠️', 'Hoy todavía no hay visitas de personas', `Última visita de una persona: ${ago(h.lastHuman)}.`]);
  else out.push(['ok', '✅', `${fmt(h.todayVisitors)} ${h.todayVisitors === 1 ? 'persona ha' : 'personas han'} visitado tu sitio hoy`, `Última visita: ${ago(h.lastHuman)}.`]);
  if (!r.hourly && started && h.zeroBuckets > 0 && h.lastHuman) out.push(['warn', '📉', `${h.zeroBuckets} de ${r.days} días sin visitas en este periodo`, 'Mira la gráfica para ver cuáles fueron.']);
  const total = k.pageviews + b.total;
  if (total > 20 && b.total / total > 0.85) out.push(['info', '🤖', `La mayoría del tráfico son robots (${Math.round((b.total / total) * 100)}%)`, 'Es común en sitios nuevos. Las cifras de personas de arriba ya excluyen a los robots.']);
  if (b.lastGoogle && (h.now - b.lastGoogle) < 7 * 86400) out.push(['ok', '🔎', `Google visitó tu sitio ${ago(b.lastGoogle)}`, 'Buena señal: Googlebot está rastreando tus páginas para indexarlas.']);
  else if (started) out.push(['warn', '🔎', 'Google no ha rastreado tu sitio en los últimos 7 días', 'Registra el sitio y envía el sitemap en Google Search Console para que Google lo descubra.']);
  $('alerts').innerHTML = out.map(([c, i, t, d]) => `<div class="alert ${c}"><span class="ic">${i}</span><div><b>${t}</b>${d}</div></div>`).join('');
}

function bots(b, k) {
  const total = k.pageviews + b.total;
  const items = [
    ['Visitas de robots', fmt(b.total), change(b.total, b.previous, true)],
    ['% del tráfico que son robots', total ? Math.round((b.total / total) * 100) + '%' : '0%', '<span class="c flat">personas + robots</span>'],
    ['Última visita de Google', ago(b.lastGoogle), '<span class="c flat">Googlebot</span>'],
    ['Última visita de Bing', ago(b.lastBing), '<span class="c flat">Bingbot</span>'],
  ];
  $('botKpis').innerHTML = items.map(([l, v, c]) => `<div class="kpi"><div class="l">${l}</div><div class="v">${v}</div>${c}</div>`).join('');
  const catTag = (c) => { const x = catNames[c] || [c, '']; return `<span class="cat ${x[1]}">${esc(x[0].replace(/^\S+\s/, ''))}</span>`; };
  list('botNames', b.byName, { h: 'Robot', a: 'Visitas', b: 'Última vez', name: (r) => `${esc(r.name)}${catTag(r.category)}`, bval: (r) => ago(r.last) });
  list('botCats', b.byCategory, { h: 'Tipo', b: 'Robots', name: (r) => (catNames[r.name] || [esc(r.name)])[0] });
  list('botPaths', b.paths, { h: 'Página', b: 'Robots' });
  list('botErrors', b.errors, { h: 'Página', b: 'Robots' });
  document.querySelectorAll('#botNames .row,#botCats .row,#botPaths .row,#botErrors .row').forEach((x) => x.classList.add('botrow'));
}

function list(el, rows, opts = {}) {
  const box = $(el);
  if (!rows || !rows.length) { box.innerHTML = '<div class="empty">Sin datos todavía</div>'; return; }
  const max = Math.max(...rows.map((r) => +r.views || 0), 1);
  box.innerHTML = `<div class="rhead"><span>${opts.h || ''}</span><span>${opts.a || 'Visitas'}</span><span>${opts.b || 'Visitantes'}</span></div><div class="rows">` +
    rows.map((r) => {
      const name = opts.name ? opts.name(r) : esc(r.name);
      const b = opts.bval ? opts.bval(r) : fmt(r.visitors);
      return `<div class="row"><span class="fill" style="width:${(r.views / max) * 100}%"></span><span class="n">${name}</span><span class="a">${fmt(r.views)}</span><span class="b">${b}</span></div>`;
    }).join('') + '</div>';
}

function change(cur, prev, invert) {
  if (!prev) return cur ? '<span class="c up">nuevo</span>' : '<span class="c flat">—</span>';
  const p = ((cur - prev) / prev) * 100;
  if (Math.abs(p) < 0.5) return '<span class="c flat">= vs. periodo anterior</span>';
  const good = invert ? p < 0 : p > 0;
  return `<span class="c ${good ? 'up' : 'down'}">${p > 0 ? '▲' : '▼'} ${Math.abs(p).toFixed(0)}% vs. periodo anterior</span>`;
}

function kpis(k, p) {
  const items = [
    ['Visitantes únicos', fmt(k.visitors), change(k.visitors, p.visitors)],
    ['Páginas vistas', fmt(k.pageviews), change(k.pageviews, p.pageviews)],
    ['Tiempo promedio', dur(k.avgDuration), change(k.avgDuration, p.avgDuration)],
    ['Tasa de rebote', k.bounceRate + '%', change(k.bounceRate, p.bounceRate, true)],
    ['Sesiones', fmt(k.sessions), change(k.sessions, p.sessions)],
    ['Páginas por sesión', k.pagesPerSession, change(k.pagesPerSession, p.pagesPerSession)],
    ['Clics a Swagbucks', fmt(k.clicks), change(k.clicks, p.clicks), 'gold'],
    ['% de sesiones con clic', k.ctr + '%', change(k.ctr, p.ctr), 'gold'],
  ];
  $('kpis').innerHTML = items.map(([l, v, c, cls]) => `<div class="kpi ${cls || ''}"><div class="l">${l}</div><div class="v">${v}</div>${c}</div>`).join('');
}

function chart(series, hourly) {
  const box = $('chart'); const tip = $('tip');
  box.querySelector('svg')?.remove();
  const W = box.clientWidth, H = box.clientHeight, pl = 40, pr = 10, pt = 10, pb = 28;
  const max = Math.max(5, ...series.map((s) => Math.max(s.pageviews, s.visitors, s.bots || 0)));
  const step = Math.pow(10, Math.floor(Math.log10(max))); const top = Math.ceil(max / step) * step;
  const n = series.length, x = (i) => pl + (n === 1 ? (W - pl - pr) / 2 : (i * (W - pl - pr)) / (n - 1)), y = (v) => pt + (H - pt - pb) * (1 - v / top);
  const path = (k) => series.map((s, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(s[k]).toFixed(1)}`).join('');
  const area = (k) => `${path(k)}L${x(n - 1)},${y(0)}L${x(0)},${y(0)}Z`;
  let g = '';
  for (let i = 0; i <= 4; i++) { const v = (top / 4) * i, yy = y(v); g += `<line x1="${pl}" x2="${W - pr}" y1="${yy}" y2="${yy}" stroke="#eef3f1"/><text x="${pl - 8}" y="${yy + 4}" text-anchor="end" font-size="11" fill="#8a9a95">${fmt(v)}</text>`; }
  const every = Math.ceil(n / (W < 600 ? 5 : 10));
  series.forEach((s, i) => { if (i % every === 0 || i === n - 1) { const lab = hourly ? s.label : s.label.slice(5).split('-').reverse().join('/'); g += `<text x="${x(i)}" y="${H - 8}" text-anchor="middle" font-size="11" fill="#8a9a95">${lab}</text>`; } });
  const svg = `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs>
    <linearGradient id="ga" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0f9d6b" stop-opacity=".28"/><stop offset="1" stop-color="#0f9d6b" stop-opacity="0"/></linearGradient>
    <linearGradient id="gb" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffc23d" stop-opacity=".25"/><stop offset="1" stop-color="#ffc23d" stop-opacity="0"/></linearGradient></defs>
    ${g}<path d="${area('pageviews')}" fill="url(#gb)"/><path d="${path('pageviews')}" fill="none" stroke="#f5a400" stroke-width="2.2"/>
    <path d="${area('visitors')}" fill="url(#ga)"/><path d="${path('visitors')}" fill="none" stroke="#0f9d6b" stroke-width="2.6"/>
    <path d="${path('bots')}" fill="none" stroke="#8b9bb8" stroke-width="1.8" stroke-dasharray="5 4"/>
    <line id="cursor" y1="${pt}" y2="${H - pb}" stroke="#0b1f1a" stroke-dasharray="3 3" opacity="0"/>
    <rect x="${pl}" y="0" width="${W - pl - pr}" height="${H}" fill="transparent" id="hover"/></svg>`;
  box.insertAdjacentHTML('afterbegin', svg);
  const hover = box.querySelector('#hover'), cur = box.querySelector('#cursor');
  hover.addEventListener('mousemove', (e) => {
    const r = box.getBoundingClientRect(); const px = e.clientX - r.left;
    const i = Math.max(0, Math.min(n - 1, Math.round(((px - pl) / (W - pl - pr)) * (n - 1))));
    const s = series[i]; cur.setAttribute('x1', x(i)); cur.setAttribute('x2', x(i)); cur.setAttribute('opacity', '.4');
    tip.style.display = 'block'; tip.style.left = x(i) + 'px'; tip.style.top = y(Math.max(s.pageviews, s.visitors, s.bots || 0)) + 'px';
    tip.innerHTML = `<b>${hourly ? s.label : new Date(s.label + 'T12:00').toLocaleDateString('es-CO', { weekday: 'short', day: 'numeric', month: 'short' })}</b><br>👤 ${fmt(s.visitors)} visitantes<br>📄 ${fmt(s.pageviews)} páginas<br>🤖 ${fmt(s.bots || 0)} robots`;
  });
  hover.addEventListener('mouseleave', () => { tip.style.display = 'none'; cur.setAttribute('opacity', '0'); });
}

function realtime(rt) {
  $('active').textContent = fmt(rt.active);
  $('recent').innerHTML = rt.recent && rt.recent.length ? rt.recent.map((r) => {
    const t = new Date(r.ts * 1000).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    return `<div><span>${t}</span><b>${esc(r.path)}</b><span>${flag(r.country)} ${r.device === 'Mobile' ? '📱' : r.device === 'Tablet' ? '📲' : '💻'} ${esc(r.source)}</span></div>`;
  }).join('') : '<div class="empty">Sin visitas todavía</div>';
  list('livePages', (rt.pages || []).map((p) => ({ name: p.name, views: p.visitors, visitors: p.visitors })), { a: 'Ahora', b: '', bval: () => '' });
}

async function load() {
  $('app').classList.add('loading');
  const qs = new URLSearchParams({ range }); if (range === 'custom') { qs.set('from', from); qs.set('to', to); }
  try {
    const res = await fetch('api.php?' + qs, { cache: 'no-store' });
    if (res.status === 401) { location.reload(); return; }
    const d = await res.json(); if (d.error) throw new Error(d.error);
    $('err').style.display = 'none';
    const r = d.range; $('rangeLabel').textContent = r.from === r.to ? `· ${r.from} (por hora)` : `· ${r.from} a ${r.to}`;
    kpis(d.kpis, d.previous); chart(d.series, r.hourly); window._series = [d.series, r.hourly];
    list('pages', d.pages, { h: 'Página', b: 'Tiempo', name: (p) => `${esc(p.title || p.name)}<small>${esc(p.name)}</small>`, bval: (p) => dur(p.avgdur) });
    list('entryPages', d.entryPages, { h: 'Página', b: '', bval: () => '' });
    list('sources', d.sources, { h: 'Fuente', name: (r) => srcBadge(r.name, '') });
    list('refUrls', d.refUrls, { h: 'URL de origen', name: (r) => `<a href="https://${esc(r.name)}" target="_blank" rel="noopener noreferrer">${esc(r.name)}</a>` });
    const chTotal = d.channels.reduce((t, c) => t + (+c.views), 0) || 1;
    $('channelChips').innerHTML = d.channels.length ? d.channels.map((c) => `<div class="chip2"><b>${Math.round((c.views / chTotal) * 100)}%</b><span>${channelNames[c.name] || esc(c.name)} · ${fmt(c.views)}</span></div>`).join('') : '<div class="empty">Sin datos todavía</div>';
    $('arrivals').innerHTML = d.arrivals && d.arrivals.length ? `<table class="tbl"><thead><tr><th>Cuándo</th><th>Origen</th><th>Referer</th><th>Llegó a</th><th>País</th><th></th></tr></thead><tbody>` + d.arrivals.map((r) => {
      const when = new Date(r.ts * 1000).toLocaleString('es-CO', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
      const ref = r.ref_url ? `<a href="https://${esc(r.ref_url)}" target="_blank" rel="noopener noreferrer">${esc(r.ref_url)}</a>` : (r.utm_campaign ? 'Campaña: ' + esc(r.utm_campaign) : '<span style="color:#8a9a95">— (directo)</span>');
      return `<tr><td>${when}</td><td>${srcBadge(r.source, r.medium)}</td><td class="u">${ref}</td><td class="u">${esc(r.path)}</td><td>${flag(r.country)}</td><td>${r.device === 'Mobile' ? '📱' : r.device === 'Tablet' ? '📲' : '💻'}</td></tr>`;
    }).join('') + '</tbody></table>' : '<div class="empty">Sin visitas todavía</div>';
    
    list('referrers', d.referrers, { h: 'Sitio' });
    list('campaigns', d.campaigns, { h: 'Campaña' });
    list('countries', d.countries, { h: 'País', name: (c) => `${flag(c.name)} ${esc(cName(c.name))}` });
    list('devices', d.devices, { h: 'Dispositivo', name: (c) => deviceNames[c.name] || esc(c.name) });
    list('os', d.os, { h: 'Sistema' }); list('browsers', d.browsers, { h: 'Navegador' }); list('languages', d.languages, { h: 'Idioma' });
    list('clickPlacements', d.clickPlacements, { h: 'Botón', a: 'Clics', name: (c) => esc(placementNames[c.name] || c.name) });
    list('clickPages', d.clickPages, { h: 'Página', a: 'Clics' });
    realtime(d.realtime);
    bots(d.bots, d.kpis); alerts(d.health, d.kpis, d.bots, r);
  } catch (e) {
    $('err').style.display = 'block'; $('err').textContent = 'No se pudieron cargar las estadísticas: ' + e.message;
  }
  $('app').classList.remove('loading');
}

$('tabs').addEventListener('click', (e) => {
  const b = e.target.closest('button'); if (!b) return;
  document.querySelectorAll('#tabs button').forEach((x) => x.classList.toggle('on', x === b));
  range = b.dataset.r; $('custom').classList.toggle('on', range === 'custom');
  if (range !== 'custom') load();
});
$('apply').addEventListener('click', () => { from = $('from').value; to = $('to').value; if (from && to) load(); });
window.addEventListener('resize', () => window._series && chart(...window._series));
setInterval(async () => { try { const r = await fetch('api.php?realtime=1', { cache: 'no-store' }); if (r.ok) realtime(await r.json()); } catch (e) {} }, 20000);
const t = new Date(); $('to').value = t.toISOString().slice(0, 10); t.setDate(t.getDate() - 29); $('from').value = t.toISOString().slice(0, 10);
load();
</script>
</body>
</html>
