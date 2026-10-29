// Draws a stylised street map of central Berane with a pin on the company address (O nama page photo).
// Map data © OpenStreetMap contributors (ODbL) — fetched once from Overpass into photos/osm-berane.json,
// delete that file to refresh it. Output: photos/berane-mapa.jpg (then run build-images.mjs).
//   node tools/build-map.mjs
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';
import sharp from 'sharp';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const cache = path.join(root, 'photos', 'osm-berane.json');
const out = path.join(root, 'photos', 'berane-mapa.jpg');
const fontDir = path.join(root, 'theme', 'espam', 'assets', 'fonts');

// Pin: VIII crnogorske brigade (OSM "8. crnogorske brigade"), Berane. View: 1600 × 1200 px around it.
const PIN = { lat: 42.84372, lon: 19.87515 };
const W = 1600;
const H = 1200;
const METERS_PER_PX = 0.85;
const mPerDegLat = 111320;
const mPerDegLon = 111320 * Math.cos((PIN.lat * Math.PI) / 180);
const halfLat = (H / 2) * METERS_PER_PX / mPerDegLat;
const halfLon = (W / 2) * METERS_PER_PX / mPerDegLon;
const pad = 1.25;
const bbox = [PIN.lat - halfLat * pad, PIN.lon - halfLon * pad, PIN.lat + halfLat * pad, PIN.lon + halfLon * pad].map((n) => n.toFixed(5)).join(',');

if (!fs.existsSync(cache)) {
  const query = `[out:json][timeout:60];(
    way["highway"](${bbox});
    way["building"](${bbox});
    way["waterway"](${bbox});
    nwr["natural"="water"](${bbox});
    way["leisure"~"park|pitch|stadium"](${bbox});
    way["landuse"~"grass|recreation_ground|forest|meadow"](${bbox});
  );out geom;`;
  const res = await fetch(process.env.OVERPASS || 'https://overpass-api.de/api/interpreter', {
    method: 'POST',
    headers: { 'User-Agent': 'espam-site-build/1.0', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'data=' + encodeURIComponent(query),
  });
  if (!res.ok) throw new Error(`Overpass ${res.status}`);
  fs.writeFileSync(cache, await res.text());
}
const { elements } = JSON.parse(fs.readFileSync(cache, 'utf8'));

const x = (lon) => W / 2 + ((lon - PIN.lon) * mPerDegLon) / METERS_PER_PX;
const y = (lat) => H / 2 - ((lat - PIN.lat) * mPerDegLat) / METERS_PER_PX;
const d = (geom, close) => 'M' + geom.map((g) => `${x(g.lon).toFixed(1)} ${y(g.lat).toFixed(1)}`).join('L') + (close ? 'Z' : '');
const ways = (e) => (e.geometry ? [e.geometry] : (e.members || []).filter((m) => m.geometry).map((m) => m.geometry));

const layers = { green: [], water: [], river: [], buildings: [], minor: [], streets: [], main: [] };
const major = /^(primary|secondary|tertiary|trunk)(_link)?$/;
const minor = /^(footway|path|steps|pedestrian|cycleway|track|service)$/;
for (const e of elements) {
  const t = e.tags || {};
  for (const g of ways(e)) {
    if (t.building) layers.buildings.push(d(g, true));
    else if (t.natural === 'water') layers.water.push(d(g, true));
    else if (t.waterway === 'river') layers.river.push(d(g));
    else if (t.leisure || t.landuse) layers.green.push(d(g, true));
    else if (t.highway && major.test(t.highway)) layers.main.push(d(g));
    else if (t.highway && minor.test(t.highway)) layers.minor.push(d(g));
    else if (t.highway) layers.streets.push(d(g));
  }
}
const path_ = (list, attrs) => list.map((p) => `<path d="${p}" ${attrs}/>`).join('');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
  <rect width="${W}" height="${H}" fill="#EEF4FB"/>
  <g>${path_(layers.green, 'fill="#DDEEDF"')}</g>
  <g>${path_(layers.water, 'fill="#BFDAF3"')}${path_(layers.river, 'fill="none" stroke="#BFDAF3" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"')}</g>
  <g>${path_(layers.buildings, 'fill="#D5E1EF" stroke="#C3D3E6" stroke-width="1"')}</g>
  <g fill="none" stroke-linecap="round" stroke-linejoin="round">
    ${path_(layers.minor, 'stroke="#FFFFFF" stroke-width="3" stroke-dasharray="1 7" opacity="0.9"')}
    ${path_(layers.streets, 'stroke="#C6D4E5" stroke-width="15"')}
    ${path_(layers.main, 'stroke="#9DB9D9" stroke-width="25"')}
    ${path_(layers.streets, 'stroke="#FFFFFF" stroke-width="11"')}
    ${path_(layers.main, 'stroke="#FFFFFF" stroke-width="20"')}
  </g>
</svg>`;

const mark = fs.readFileSync(path.join(root, 'plugin', 'espam-core', 'assets', 'logo', 'logo-mark.svg'), 'utf8');
const font = (file) => 'data:font/woff2;base64,' + fs.readFileSync(path.join(fontDir, file)).toString('base64');
const html = `<!doctype html><html><head><meta charset="utf-8"><style>
  @font-face { font-family: 'PJS'; src: url(${font('plus-jakarta-sans-latin-wght-normal.woff2')}) format('woff2'); font-weight: 200 800; unicode-range: U+0000-00FF; }
  @font-face { font-family: 'PJS'; src: url(${font('plus-jakarta-sans-latin-ext-wght-normal.woff2')}) format('woff2'); font-weight: 200 800; unicode-range: U+0100-024F; }
  html, body { margin: 0; width: ${W}px; height: ${H}px; overflow: hidden; font-family: 'PJS', sans-serif; }
  .map { position: absolute; inset: 0; }
  .glow { position: absolute; left: ${W / 2 - 170}px; top: ${H / 2 - 170}px; width: 340px; height: 340px; border-radius: 50%;
    background: radial-gradient(circle, rgba(10,101,184,.22) 0, rgba(10,101,184,.10) 45%, rgba(10,101,184,0) 70%); }
  .ring { position: absolute; left: ${W / 2 - 46}px; top: ${H / 2 - 46}px; width: 92px; height: 92px; border-radius: 50%; border: 3px solid rgba(10,101,184,.35); }
  .pin { position: absolute; left: ${W / 2 - 38}px; top: ${H / 2 - 92}px; width: 76px; height: 92px; filter: drop-shadow(0 14px 18px rgba(11,58,102,.35)); }
  .pin .head { position: absolute; left: 0; top: 0; width: 76px; height: 76px; border-radius: 50% 50% 50% 6px; transform: rotate(-45deg); background: #0B3A66; }
  .pin .logo { position: absolute; left: 12px; top: 12px; width: 52px; height: 52px; border-radius: 50%; background: #fff; display: grid; place-items: center; }
  .pin .logo svg { width: 36px; height: auto; }
  .card { position: absolute; left: ${W / 2 + 60}px; top: ${H / 2 - 150}px; padding: 22px 28px; border-radius: 18px; background: #fff;
    box-shadow: 0 24px 48px -20px rgba(11,58,102,.45), 0 0 0 1px #E3EAF3; color: #13243A; }
  .card b { display: block; font-size: 30px; font-weight: 800; letter-spacing: .03em; }
  .card span { display: block; margin-top: 4px; font-size: 24px; font-weight: 500; color: #5B6B80; }
  .card i { display: block; width: 44px; height: 4px; margin-bottom: 14px; border-radius: 2px; background: #0A65B8; }
  .credit { position: absolute; left: 120px; bottom: 16px; font-size: 16px; color: #7A8899; background: rgba(255,255,255,.75); padding: 3px 9px; border-radius: 6px; }
</style></head><body>
  <div class="map">${svg}</div>
  <div class="glow"></div><div class="ring"></div>
  <div class="pin"><div class="head"></div><div class="logo">${mark}</div></div>
  <div class="card"><i></i><b>ESPAM PLUS</b><span>VIII crnogorske brigade 5</span><span>Berane</span></div>
  <div class="credit">© OpenStreetMap</div>
</body></html>`;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 2 });
await page.setContent(html, { waitUntil: 'load' });
await page.evaluate(() => document.fonts.ready);
const png = await page.screenshot({ type: 'png' });
await browser.close();
await sharp(png).resize({ width: W }).jpeg({ quality: 90, mozjpeg: true }).toFile(out);
console.log('map:', path.relative(root, out), elements.length, 'OSM elements');
