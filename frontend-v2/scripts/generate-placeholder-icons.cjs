/**
 * Generates placeholder branding PNG files for HRIS.
 * Replace these files with actual brand assets — filenames must stay the same.
 * Run: node scripts/generate-placeholder-icons.cjs
 */

'use strict';

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

// ── CRC32 ────────────────────────────────────────────────────────────────────

const CRC_TABLE = (() => {
  const t = new Uint32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    t[n] = c;
  }
  return t;
})();

function crc32(buf) {
  let c = 0xffffffff;
  for (let i = 0; i < buf.length; i++) c = (c >>> 8) ^ CRC_TABLE[(c ^ buf[i]) & 0xff];
  return (c ^ 0xffffffff) >>> 0;
}

// ── PNG builder ──────────────────────────────────────────────────────────────

function chunk(type, data) {
  const len = Buffer.allocUnsafe(4);
  const typeB = Buffer.from(type, 'ascii');
  const crcB = Buffer.allocUnsafe(4);
  len.writeUInt32BE(data.length, 0);
  crcB.writeUInt32BE(crc32(Buffer.concat([typeB, data])), 0);
  return Buffer.concat([len, typeB, data, crcB]);
}

/**
 * Create a PNG with a solid background and a centred white letter.
 * @param {number} w
 * @param {number} h
 * @param {number[]} bg  [r, g, b]  background colour
 * @param {string}  label  1–3 chars to render (very rough bitmap font)
 */
function makePNG(w, h, bg, label = '') {
  const [br, bg2, bb] = bg;

  // ── Tiny 5×7 bitmap font for A–Z and digits ───────────────────────────────
  // Each char: 5 columns × 7 rows, stored as 7 bytes (bit 4 = leftmost pixel)
  const FONT = {
    H: [0b10001, 0b10001, 0b10001, 0b11111, 0b10001, 0b10001, 0b10001],
    R: [0b11110, 0b10001, 0b10001, 0b11110, 0b10100, 0b10010, 0b10001],
    I: [0b11111, 0b00100, 0b00100, 0b00100, 0b00100, 0b00100, 0b11111],
    S: [0b01111, 0b10000, 0b10000, 0b01110, 0b00001, 0b00001, 0b11110],
  };

  // Build per-pixel array  (4 channels RGBA)
  const pixels = new Uint8Array(w * h * 4);
  for (let i = 0; i < w * h; i++) {
    pixels[i * 4 + 0] = br;
    pixels[i * 4 + 1] = bg2;
    pixels[i * 4 + 2] = bb;
    pixels[i * 4 + 3] = 255;
  }

  // Render label chars
  const chars = label.toUpperCase().split('').filter((c) => FONT[c]);
  const CW = 5, CH = 7, GAP = 1;
  const totalW = chars.length * CW + Math.max(0, chars.length - 1) * GAP;
  const startX = Math.floor((w - totalW) / 2);
  const startY = Math.floor((h - CH) / 2);

  chars.forEach((ch, ci) => {
    const rows = FONT[ch];
    for (let row = 0; row < CH; row++) {
      for (let col = 0; col < CW; col++) {
        if (rows[row] & (1 << (CW - 1 - col))) {
          const px = startX + ci * (CW + GAP) + col;
          const py = startY + row;
          if (px >= 0 && px < w && py >= 0 && py < h) {
            const idx = (py * w + px) * 4;
            pixels[idx + 0] = 255;
            pixels[idx + 1] = 255;
            pixels[idx + 2] = 255;
            pixels[idx + 3] = 255;
          }
        }
      }
    }
  });

  // Pack into raw (filter-byte 0 + RGBA row per scanline)
  const rowLen = 1 + w * 4;
  const raw = Buffer.allocUnsafe(h * rowLen);
  for (let y = 0; y < h; y++) {
    raw[y * rowLen] = 0; // filter: None
    for (let x = 0; x < w; x++) {
      const src = (y * w + x) * 4;
      const dst = y * rowLen + 1 + x * 4;
      raw[dst + 0] = pixels[src + 0];
      raw[dst + 1] = pixels[src + 1];
      raw[dst + 2] = pixels[src + 2];
      raw[dst + 3] = pixels[src + 3];
    }
  }

  const idat = zlib.deflateSync(raw, { level: 6 });

  const ihdr = Buffer.allocUnsafe(13);
  ihdr.writeUInt32BE(w, 0);
  ihdr.writeUInt32BE(h, 4);
  ihdr[8] = 8;  // bit depth
  ihdr[9] = 6;  // colour type: RGBA
  ihdr[10] = 0; // compression
  ihdr[11] = 0; // filter
  ihdr[12] = 0; // interlace

  return Buffer.concat([
    Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]), // PNG sig
    chunk('IHDR', ihdr),
    chunk('IDAT', idat),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

// ── Minimal ICO builder (single 32×32 image) ─────────────────────────────────

function makeICO(pngBuf) {
  // ICO wraps a PNG directly (modern ICO format)
  const header = Buffer.allocUnsafe(6);
  header.writeUInt16LE(0, 0);   // reserved
  header.writeUInt16LE(1, 2);   // type: icon
  header.writeUInt16LE(1, 4);   // image count

  const entry = Buffer.allocUnsafe(16);
  entry[0] = 32; // width  (0 = 256)
  entry[1] = 32; // height
  entry[2] = 0;  // colour count (0 = no palette)
  entry[3] = 0;  // reserved
  entry.writeUInt16LE(1, 4);  // colour planes
  entry.writeUInt16LE(32, 6); // bits per pixel
  entry.writeUInt32LE(pngBuf.length, 8); // size of image data
  entry.writeUInt32LE(22, 12);           // offset of image data (6 + 16)

  return Buffer.concat([header, entry, pngBuf]);
}

// ── Generate files ────────────────────────────────────────────────────────────

const OUT = path.join(__dirname, '..', 'public');
const BLUE  = [37, 99, 235]; // #2563eb — primary-600

const files = [
  // name, w, h, bg, label
  ['logo.png',              400, 120, BLUE,  'HRIS'],
  ['logo-icon.png',          64,  64, BLUE,  'H'],
  ['favicon-16.png',         16,  16, BLUE,  'H'],
  ['favicon-32.png',         32,  32, BLUE,  'H'],
  ['apple-touch-icon.png',  180, 180, BLUE,  'H'],
  ['pwa-192.png',           192, 192, BLUE,  'H'],
  ['pwa-512.png',           512, 512, BLUE,  'H'],
  ['maskable-icon-512.png', 512, 512, BLUE,  'H'],   // add your own safe-zone padding when replacing
];

files.forEach(([name, w, h, bg, label]) => {
  const buf = makePNG(w, h, bg, label);
  fs.writeFileSync(path.join(OUT, name), buf);
  console.log(`  ✓ ${name}  (${w}×${h})`);
});

// favicon.ico wraps the 32×32 PNG
const ico = makeICO(makePNG(32, 32, BLUE, 'H'));
fs.writeFileSync(path.join(OUT, 'favicon.ico'), ico);
console.log('  ✓ favicon.ico  (32×32, ICO wrapper)');

console.log('\nDone — replace these files with your actual brand assets.');
console.log('Filenames must stay exactly the same.\n');
