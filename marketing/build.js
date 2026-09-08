// Builds the marketing artwork in this folder from the screenshots/ captures
// and the plugin's own fonts and colour tokens.  Run:  node marketing/build.js

const fs = require('fs'), path = require('path'), cp = require('child_process');
const REPO = path.resolve(__dirname, '..');
const OUT  = path.join(REPO, 'marketing');
const SP   = fs.mkdtempSync(path.join(require('os').tmpdir(), 'deepfield-mk-'));
// Any Chrome/Chromium binary works; override with CHROME=/path/to/chrome
const CHROME = process.env.CHROME
  || '/home/grv/.cache/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-linux64/chrome-headless-shell';

const FONTFILE = {Orbitron:'Orbitron-Variable.woff2', SpaceGrotesk:'SpaceGrotesk-Variable.woff2', JetBrainsMono:'JetBrainsMono-Variable.woff2'};
const b64 = n => fs.readFileSync(path.join(REPO, 'fonts', FONTFILE[n])).toString('base64');
const shot = n => 'data:image/png;base64,' + fs.readFileSync(path.join(REPO, 'screenshots', n)).toString('base64');

const FONTS = `
@font-face{font-family:'Orbitron';src:url(data:font/woff2;base64,${b64('Orbitron')}) format('woff2');font-weight:400 900;font-display:block}
@font-face{font-family:'Space Grotesk';src:url(data:font/woff2;base64,${b64('SpaceGrotesk')}) format('woff2');font-weight:300 700;font-display:block}
@font-face{font-family:'JetBrains Mono';src:url(data:font/woff2;base64,${b64('JetBrainsMono')}) format('woff2');font-weight:100 800;font-display:block}`;

const BASE = `
*{margin:0;padding:0;box-sizing:border-box}
html,body{background:#050614;overflow:hidden}
:root{
  --void:#050614; --deep:#0b0a1e; --card:#100f24;
  --cyan:#38e1ff; --teal:#5eead4; --violet:#a78bfa; --magenta:#f472b6;
  --ink:#e8ecff; --ink-soft:#c8cff0; --ink-muted:#8b90b8; --ink-dim:#656b91;
}
body{font-family:'Space Grotesk',sans-serif;color:var(--ink);-webkit-font-smoothing:antialiased}
.frame{position:relative;overflow:hidden;background:
  radial-gradient(120% 90% at 8% 0%, rgba(94,234,212,.10), transparent 55%),
  radial-gradient(110% 100% at 92% 100%, rgba(167,139,250,.16), transparent 60%),
  radial-gradient(80% 70% at 60% 40%, rgba(56,225,255,.05), transparent 70%),
  linear-gradient(160deg,#070818 0%,#050614 45%,#0a0820 100%);}
canvas.sky{position:absolute;inset:0;width:100%;height:100%}
.vig{position:absolute;inset:0;background:radial-gradient(130% 110% at 50% 45%, transparent 45%, rgba(3,4,12,.85) 100%);pointer-events:none}
.grain{position:absolute;inset:0;opacity:.035;pointer-events:none;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3'/></filter><rect width='140' height='140' filter='url(%23n)'/></svg>")}
.wordmark{font-family:'Orbitron',sans-serif;font-weight:600;letter-spacing:.14em;
  background:linear-gradient(100deg,#38e1ff 0%,#5eead4 38%,#a78bfa 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
  filter:drop-shadow(0 0 22px rgba(56,225,255,.45)) drop-shadow(0 0 60px rgba(167,139,250,.30));}
.rule{height:2px;border-radius:2px;background:linear-gradient(90deg,#38e1ff,#5eead4 45%,#a78bfa 90%,transparent);box-shadow:0 0 18px rgba(94,234,212,.5)}
.pill{display:inline-flex;align-items:center;gap:.5em;border:1px solid rgba(167,139,250,.30);
  background:rgba(16,15,36,.72);border-radius:999px;color:var(--ink-soft);backdrop-filter:blur(6px)}
.pill i{width:.5em;height:.5em;border-radius:50%;background:linear-gradient(120deg,#38e1ff,#a78bfa);box-shadow:0 0 10px rgba(56,225,255,.8);font-style:normal}
.shotwrap{position:relative;border-radius:14px;overflow:hidden;
  border:1px solid rgba(167,139,250,.34);
  box-shadow:0 40px 120px rgba(3,4,12,.85), 0 0 0 1px rgba(56,225,255,.10), 0 0 90px rgba(94,234,212,.13);
  background:var(--card)}
.shotwrap .bar{height:34px;display:flex;align-items:center;gap:8px;padding:0 14px;
  background:linear-gradient(180deg,rgba(20,22,43,.98),rgba(11,10,30,.98));border-bottom:1px solid rgba(167,139,250,.20)}
.shotwrap .bar u{width:9px;height:9px;border-radius:50%;text-decoration:none;display:block}
.shotwrap .bar .t{margin-left:10px;font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:.14em;color:#5b6089;text-transform:uppercase}
.shotwrap img{display:block;width:100%;height:auto}
.shotwrap::after{content:'';position:absolute;inset:0;pointer-events:none;
  background:linear-gradient(190deg,rgba(56,225,255,.05),transparent 30%)}
`;

const SKY = `
function sky(cv, opt){
  opt = opt || {};
  const dpr = 1, W = cv.width = cv.clientWidth, H = cv.height = cv.clientHeight;
  const g = cv.getContext('2d');
  let s = opt.seed || 20240816;
  const rnd = () => (s = (s*1664525 + 1013904223) % 4294967296) / 4294967296;
  const layers = [
    {n: Math.round(W*H/5200), r:[.35,.85], a:[.18,.45], c:'#cfe6ff'},
    {n: Math.round(W*H/11000), r:[.7,1.35], a:[.35,.75], c:'#e6f2ff'},
    {n: Math.round(W*H/38000), r:[1.2,2.1], a:[.6,1],    c:'#ffffff'}
  ];
  for (const L of layers){
    for (let i=0;i<L.n;i++){
      const x=rnd()*W, y=rnd()*H, r=L.r[0]+rnd()*(L.r[1]-L.r[0]), a=L.a[0]+rnd()*(L.a[1]-L.a[0]);
      const tint = rnd();
      g.beginPath(); g.arc(x,y,r,0,6.2832);
      g.fillStyle = tint>.90 ? 'rgba(94,234,212,'+a+')' : tint>.80 ? 'rgba(167,139,250,'+a+')' : L.c.replace(')', '')==='#ffffff'? 'rgba(255,255,255,'+a+')' : 'rgba(220,235,255,'+a+')';
      g.fill();
      if (r>1.5){ g.beginPath(); g.arc(x,y,r*3.4,0,6.2832);
        const rg=g.createRadialGradient(x,y,0,x,y,r*3.4); rg.addColorStop(0,'rgba(180,225,255,'+(a*.30)+')'); rg.addColorStop(1,'rgba(180,225,255,0)');
        g.fillStyle=rg; g.fill(); }
    }
  }
  (opt.meteors||[]).forEach(m=>{
    const [x,y,len,ang] = m;
    const dx=Math.cos(ang)*len, dy=Math.sin(ang)*len;
    const lg=g.createLinearGradient(x,y,x+dx,y+dy);
    lg.addColorStop(0,'rgba(94,234,212,0)'); lg.addColorStop(.55,'rgba(94,234,212,.35)'); lg.addColorStop(1,'rgba(190,245,255,.95)');
    g.strokeStyle=lg; g.lineCap='round';
    g.lineWidth=2.2; g.beginPath(); g.moveTo(x,y); g.lineTo(x+dx,y+dy); g.stroke();
    const hx=x+dx, hy=y+dy;
    const hg=g.createRadialGradient(hx,hy,0,hx,hy,26);
    hg.addColorStop(0,'rgba(220,255,255,.95)'); hg.addColorStop(.35,'rgba(94,234,212,.35)'); hg.addColorStop(1,'rgba(94,234,212,0)');
    g.fillStyle=hg; g.beginPath(); g.arc(hx,hy,26,0,6.2832); g.fill();
  });
}
document.querySelectorAll('canvas.sky').forEach(c=>sky(c, JSON.parse(c.dataset.opt||'{}')));
document.documentElement.dataset.ready='1';
`;

function page(w, h, body, extraCss){
  return `<!doctype html><html><head><meta charset="utf-8"><style>${FONTS}${BASE}
  .frame{width:${w}px;height:${h}px}
  ${extraCss||''}</style></head><body><div class="frame">${body}</div>
  <script>${SKY}</script></body></html>`;
}

function render(name, w, h, html){
  const f = path.join(SP, name + '.html'); fs.mkdirSync(path.dirname(f), {recursive:true});
  fs.writeFileSync(f, html);
  const out = path.join(OUT, name + '.png');
  fs.mkdirSync(path.dirname(out), {recursive:true});
  cp.execFileSync(CHROME, [
    '--headless', '--disable-gpu', '--hide-scrollbars', '--no-sandbox',
    '--force-color-profile=srgb', '--font-render-hinting=none',
    '--default-background-color=00000000',
    `--window-size=${w},${h}`, '--virtual-time-budget=4000',
    `--screenshot=${out}`, 'file://' + f
  ], {stdio:'inherit'});
  console.log('  ->', out, fs.statSync(out).size + 'b');
}

/* ---------------- 1. GitHub social preview 1280x640 ---------------- */
const social = page(1280, 640, `
  <canvas class="sky" data-opt='{"seed":90211,"meteors":[[500,14,130,0.70],[372,528,118,0.68]]}'></canvas>
  <div class="vig"></div>
  <div class="copy">
    <div class="eyebrow"><i></i>PELICAN PANEL THEME PLUGIN</div>
    <div class="wordmark" style="font-size:73px;line-height:1;letter-spacing:.115em">DEEPFIELD</div>
    <div class="rule" style="width:210px;margin:22px 0 20px"></div>
    <p class="tag">Run your servers from the edge of<br>the observable universe.</p>
    <div class="pills">
      <span class="pill"><i></i>Parallax starfield</span>
      <span class="pill"><i></i>Aurora accents</span>
      <span class="pill"><i></i>CRT console</span>
      <span class="pill"><i></i>Dark + light</span>
    </div>
    <div class="foot">github.com/gurvinny/pelican-deepfield &nbsp;·&nbsp; MIT</div>
  </div>
  <div class="shotwrap tilt">
    <div class="bar"><u style="background:#f472b6"></u><u style="background:#fbbf24"></u><u style="background:#5eead4"></u><span class="t">server console</span></div>
    <img src="${shot('03-server-console.png')}">
  </div>
  <div class="grain"></div>`, `
  .copy{position:absolute;left:64px;top:98px;width:600px;z-index:3}
  .eyebrow{display:inline-flex;align-items:center;gap:9px;font-family:'JetBrains Mono',monospace;
    font-size:12px;letter-spacing:.30em;color:#7dd3fc;text-transform:uppercase;margin-bottom:20px}
  .eyebrow i{width:6px;height:6px;border-radius:50%;background:#5eead4;box-shadow:0 0 12px #5eead4;display:block}
  .tag{font-size:22px;line-height:1.45;color:var(--ink-soft);font-weight:300;letter-spacing:.005em}
  .pills{display:flex;flex-wrap:wrap;gap:9px;margin-top:28px;max-width:520px}
  .pill{font-size:13.5px;padding:7px 14px}
  .foot{position:absolute;top:386px;left:0;font-family:'JetBrains Mono',monospace;font-size:12.5px;color:#5b6089;letter-spacing:.05em}
  .tilt{position:absolute;left:716px;top:100px;width:790px;z-index:2;
    transform:perspective(1900px) rotateY(-15deg) rotateX(3deg) rotateZ(-1.2deg)}
`);
render('social-preview', 1280, 640, social);

/* ---------------- Pelican Hub 21:9 frames ---------------- */
const HUB_W = 2520, HUB_H = 1080;

const hero = page(HUB_W, HUB_H, `
  <canvas class="sky" data-opt='{"seed":770231,"meteors":[[598,38,205,0.70],[286,752,182,0.66],[1900,896,165,0.70]]}'></canvas>
  <div class="vig"></div>
  <div class="copy">
    <div class="eyebrow"><i></i>THEME PLUGIN FOR PELICAN PANEL</div>
    <div class="wordmark" style="font-size:132px;line-height:1">DEEPFIELD</div>
    <div class="rule" style="width:300px;margin:34px 0 30px"></div>
    <p class="tag">Run your servers from the edge<br>of the observable universe.</p>
    <div class="pills">
      <span class="pill"><i></i>Parallax starfield &amp; nebula</span>
      <span class="pill"><i></i>Aurora accents</span>
      <span class="pill"><i></i>CRT terminal</span>
      <span class="pill"><i></i>Dark + light palettes</span>
      <span class="pill"><i></i>Admin · App · Server</span>
    </div>
  </div>
  <div class="shotwrap tilt">
    <div class="bar"><u style="background:#f472b6"></u><u style="background:#fbbf24"></u><u style="background:#5eead4"></u><span class="t">server console</span></div>
    <img src="${shot('03-server-console.png')}">
  </div>
  <div class="grain"></div>`, `
  .copy{position:absolute;left:130px;top:300px;width:1010px;z-index:3}
  .eyebrow{display:inline-flex;align-items:center;gap:12px;font-family:'JetBrains Mono',monospace;
    font-size:17px;letter-spacing:.34em;color:#7dd3fc;text-transform:uppercase;margin-bottom:28px}
  .eyebrow i{width:8px;height:8px;border-radius:50%;background:#5eead4;box-shadow:0 0 14px #5eead4;display:block}
  .tag{font-size:34px;line-height:1.45;color:var(--ink-soft);font-weight:300}
  .pills{display:flex;flex-wrap:wrap;gap:12px;margin-top:40px;max-width:900px}
  .pill{font-size:18px;padding:10px 20px}
  .tilt{position:absolute;left:1246px;top:106px;width:1490px;z-index:2;
    transform:perspective(3200px) rotateY(-16deg) rotateX(3deg) rotateZ(-1deg)}
`);
render('hub/01-hero', HUB_W, HUB_H, hero);

const panels = [
  ['02-console',  '03-server-console.png', 'server console', 'Terminal-first console',
   'CRT monitor chrome, optional phosphor bloom and scanlines, and Start / Restart / Stop pills that glow in their own status colors. The xterm palette is patched at runtime — four presets, including a 1:1 Minecraft vanilla match.'],
  ['03-admin',    '01-admin-dashboard.png', 'admin panel', 'Glass over deep space',
   'Sidebar and topbar float as translucent slabs above a three-tier parallax starfield with drifting nebula fog. Aurora accents mark active nav, focus rings and primary actions.'],
  ['04-servers',  '02-server-list.png', 'server list', 'Skins all three panels',
   'Admin, app and server panels share one set of colour tokens, so tables, cards and status rails read the same everywhere. Motion is disabled automatically under prefers-reduced-motion.'],
  ['05-settings', '04-plugin-settings.png', 'plugin settings', 'Configurable in-panel',
   'Starfield density, nebula hue, CRT bloom, scanlines and terminal palette are all toggleable from a native Filament settings page. Values persist to .env — no migrations, survives updates.'],
  ['06-login',    '00-login.png', 'sign in', 'Self-hosted typography',
   'Orbitron display, Space Grotesk UI and JetBrains Mono code, all served from the plugin. No CDN calls and no Google Fonts round-trip on any page.'],
];

panels.forEach(([name, file, barlabel, title, body], idx) => {
  const html = page(HUB_W, HUB_H, `
    <canvas class="sky" data-opt='{"seed":${4400+idx*913},"meteors":[[${150+idx*40},${850-idx*28},150,0.66],[${90+idx*30},${40+idx*14},112,0.70]]}'></canvas>
    <div class="vig"></div>
    <div class="copy">
      <div class="mark">DEEPFIELD</div>
      <div class="rule" style="width:160px;margin:26px 0 34px"></div>
      <h2>${title}</h2>
      <p>${body}</p>
    </div>
    <div class="shotwrap card">
      <div class="bar"><u style="background:#f472b6"></u><u style="background:#fbbf24"></u><u style="background:#5eead4"></u><span class="t">${barlabel}</span></div>
      <img src="${shot(file)}">
    </div>
    <div class="grain"></div>`, `
    .copy{position:absolute;left:110px;top:250px;width:600px;z-index:3}
    .mark{font-family:'Orbitron',sans-serif;font-weight:600;letter-spacing:.24em;font-size:30px;
      background:linear-gradient(100deg,#38e1ff,#a78bfa);-webkit-background-clip:text;background-clip:text;color:transparent;
      filter:drop-shadow(0 0 18px rgba(56,225,255,.40))}
    h2{font-size:52px;line-height:1.15;font-weight:500;letter-spacing:-.01em;margin-bottom:26px;color:#f2f5ff}
    .copy p{font-size:23px;line-height:1.62;color:var(--ink-muted);font-weight:300}
    .card{position:absolute;left:800px;top:52px;width:1620px}
  `);
  render('hub/' + name, HUB_W, HUB_H, html);
});
