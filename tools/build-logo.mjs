// Builds the ESPAM PLUS logo set: the mark is re-drawn as vector polygons traced from the
// client's raster logo (logo/source/), the wordmark is Plus Jakarta Sans converted to outlines.
//   node tools/build-logo.mjs
// Output: logo/*.svg + PNG exports, and the files the plugin renders (plugin/espam-core/assets/logo).
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import opentype from 'opentype.js';
import sharp from 'sharp';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '..');
const out = path.join(root, 'logo');
const pluginOut = path.join(root, 'plugin', 'espam-core', 'assets', 'logo');
fs.mkdirSync(out, { recursive: true });
fs.mkdirSync(pluginOut, { recursive: true });

// Brand tokens (sampled from the logo; keep in sync with theme.json)
const BLUE = '#0A65B8';
const GREY = '#929395';
const INK = '#13243A';
const WHITE = '#FFFFFF';

// ---- Mark: four pieces, coordinates traced from the 1475×1066 source, moved to the origin ----
const OX = 10, OY = 24; // source offset
const pieces = {
  e: { fill: GREY, pts: [[296, 24], [792, 24], [650, 173], [366, 173], [264, 281], [808, 281], [650, 445], [337, 445], [511, 626], [409, 731], [10, 321]] },
  p: { fill: BLUE, pts: [[870, 24], [1152, 24], [1450, 330], [1070, 721], [843, 721], [1228, 325], [1082, 175], [724, 175]] },
  bar: { fill: BLUE, pts: [[754, 460], [998, 460], [577, 895], [454, 768]] },
  dot: { fill: BLUE, pts: [[739, 823], [849, 938], [737, 1053], [625, 938]] },
};
const MW = 1440, MH = 1029; // mark bounding box

const poly = (pts, s, x, y) => pts.map(([px, py]) => `${(x + (px - OX) * s).toFixed(2)},${(y + (py - OY) * s).toFixed(2)}`).join(' ');
function mark(x, y, h, { mono } = {}) {
  const s = h / MH;
  return Object.entries(pieces)
    .map(([k, p]) => `<polygon class="ep-${k}" points="${poly(p.pts, s, x, y)}" fill="${mono || p.fill}"/>`)
    .join('');
}

// ---- Type ----
const fsrc = (file) => path.join(here, 'node_modules/@fontsource/plus-jakarta-sans/files', file);
const load = (p) => opentype.parse(fs.readFileSync(p).buffer.slice(0));
const black = [load(fsrc('plus-jakarta-sans-latin-800-normal.woff')), load(fsrc('plus-jakarta-sans-latin-ext-800-normal.woff'))];
const semi = [load(fsrc('plus-jakarta-sans-latin-700-normal.woff')), load(fsrc('plus-jakarta-sans-latin-ext-700-normal.woff'))];

function pick(fonts, ch) {
  for (const f of fonts) {
    const g = f.charToGlyph(ch);
    if (g && g.index !== 0) return { font: f, glyph: g };
  }
  throw new Error(`Glyph missing: ${ch}`);
}
// Own serializer: opentype.js toPathData() can emit "NaN" for valid coordinates.
const f2 = (n) => (+n).toFixed(2);
const pathData = (p) => p.commands.map((c) => {
  if (c.type === 'M' || c.type === 'L') return `${c.type}${f2(c.x)} ${f2(c.y)}`;
  if (c.type === 'Q') return `Q${f2(c.x1)} ${f2(c.y1)} ${f2(c.x)} ${f2(c.y)}`;
  if (c.type === 'C') return `C${f2(c.x1)} ${f2(c.y1)} ${f2(c.x2)} ${f2(c.y2)} ${f2(c.x)} ${f2(c.y)}`;
  return 'Z';
}).join('');
function textPath(fonts, str, x, y, size, tracking = 0) {
  let cx = x;
  const parts = [];
  for (const ch of str) {
    const { font, glyph } = pick(fonts, ch);
    if (ch !== ' ') parts.push(pathData(glyph.getPath(cx, y, size)));
    cx += (glyph.advanceWidth / font.unitsPerEm) * size + tracking * size;
  }
  return { d: parts.join(''), width: cx - x - tracking * size };
}
const capHeight = (fonts, size) => (pick(fonts, 'E').glyph.getBoundingBox().y2 / pick(fonts, 'E').font.unitsPerEm) * size;

const svg = (w, h, body, title = 'ESPAM PLUS') =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${Math.ceil(w)} ${Math.ceil(h)}" width="${Math.ceil(w)}" height="${Math.ceil(h)}" role="img" aria-label="${title}"><title>${title}</title>${body}</svg>\n`;

const files = {};
files['logo-mark.svg'] = svg(MW / 10, MH / 10, mark(0, 0, MH / 10));

// Horizontal: mark + "ESPAM PLUS" over "VELEPRODAJA", both lines inside the mark's height.
function horizontal(light) {
  const h = 100;
  const mw = (MW / MH) * h;
  const x0 = mw + 22;
  const bigSize = 50;
  const bigCap = capHeight(black, bigSize);
  const smallSize = 15;
  const smallCap = capHeight(semi, smallSize);
  const gap = 14;
  const top = (h - (bigCap + gap + smallCap)) / 2;
  const espam = textPath(black, 'ESPAM', x0, top + bigCap, bigSize, -0.01);
  const plus = textPath(black, 'PLUS', x0 + espam.width + bigSize * 0.32, top + bigCap, bigSize, -0.01);
  const sub = textPath(semi, 'VELEPRODAJA', x0 + 2, top + bigCap + gap + smallCap, smallSize, 0.42);
  const w = Math.max(x0 + espam.width + bigSize * 0.32 + plus.width, x0 + sub.width) + 4;
  return svg(w, h,
    mark(0, 0, h, light ? { mono: undefined } : {}) +
    `<path d="${espam.d}" fill="${light ? WHITE : INK}"/><path d="${plus.d}" fill="${light ? WHITE : BLUE}"/><path d="${sub.d}" fill="${light ? '#B9BBBE' : GREY}"/>`);
}
files['logo-horizontal.svg'] = horizontal(false);
files['logo-horizontal-white.svg'] = horizontal(true);

// Site icon: mark centred on a white square.
files['site-icon.svg'] = svg(512, 512, `<rect width="512" height="512" fill="${WHITE}"/>` + mark((512 - (MW / MH) * 330) / 2, (512 - 330) / 2 + 6, 330));

// Default share image 1200×630: blueprint grid, mark, wordmark, address.
{
  const W = 1200, H = 630;
  let grid = '';
  for (let x = 0; x <= W; x += 40) grid += `<rect x="${x}" y="0" width="1" height="${H}" fill="#1B2330"/>`;
  for (let y = 0; y <= H; y += 40) grid += `<rect x="0" y="${y}" width="${W}" height="1" fill="#1B2330"/>`;
  const big = textPath(black, 'ESPAM PLUS', 80, 470, 86, -0.01);
  const sub = textPath(semi, 'VELEPRODAJA ŠIROKOG ASORTIMANA', 84, 532, 24, 0.18);
  const addr = textPath(semi, 'BERANE · CRNA GORA', 84, 572, 18, 0.2);
  files['og-default.svg'] = svg(W, H,
    `<rect width="${W}" height="${H}" fill="${INK}"/>${grid}<rect x="0" y="${H - 14}" width="${W}" height="14" fill="${BLUE}"/>` +
    mark(80, 70, 280) + `<path d="${big.d}" fill="${WHITE}"/><path d="${sub.d}" fill="${GREY}"/><path d="${addr.d}" fill="#6E7279"/>`);
}

for (const [name, content] of Object.entries(files)) fs.writeFileSync(path.join(out, name), content);
for (const name of ['logo-mark.svg', 'logo-horizontal.svg', 'logo-horizontal-white.svg']) fs.copyFileSync(path.join(out, name), path.join(pluginOut, name));

const png = (src, dest, width, height) =>
  sharp(Buffer.from(files[src]), { density: 300 }).resize(width, height, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).png().toFile(path.join(out, dest));
await png('site-icon.svg', 'site-icon-512.png', 512, 512);
await png('site-icon.svg', 'apple-touch-icon-180.png', 180, 180);
await png('site-icon.svg', 'favicon-32.png', 32, 32);
await png('logo-horizontal.svg', 'logo-horizontal-1200.png', 1200);
await png('og-default.svg', 'og-default-1200x630.png', 1200, 630);
await sharp(path.join(out, 'og-default-1200x630.png')).jpeg({ quality: 88 }).toFile(path.join(out, 'og-default-1200x630.jpg'));
for (const name of ['site-icon-512.png', 'og-default-1200x630.jpg']) fs.copyFileSync(path.join(out, name), path.join(pluginOut, name));
console.log('logo files:', fs.readdirSync(out).join(', '));
