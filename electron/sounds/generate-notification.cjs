const fs = require('fs');
const path = require('path');

const SAMPLE_RATE = 44100;
const BIT_DEPTH = 16;
const CHANNELS = 1;

function synth(durationSec) {
  return new Float32Array(Math.floor(SAMPLE_RATE * durationSec));
}

function addBellTone(buf, startSec, freq, durationSec, amp, decayTau, harmonics) {
  const start = Math.floor(startSec * SAMPLE_RATE);
  const len = Math.floor(durationSec * SAMPLE_RATE);
  const attackSamples = Math.floor(0.0008 * SAMPLE_RATE);
  for (let i = 0; i < len; i++) {
    const t = i / SAMPLE_RATE;
    const attackEnv = i < attackSamples ? i / attackSamples : 1;
    let s = 0;
    for (const h of harmonics) {
      const hDecay = h.decayScale ? decayTau * h.decayScale : decayTau;
      const hEnv = Math.exp(-t / hDecay);
      s += h.a * hEnv * Math.sin(2 * Math.PI * freq * h.m * t);
    }
    const idx = start + i;
    if (idx < buf.length) buf[idx] += amp * attackEnv * s;
  }
}

function applyTailFade(buf, fadeSec) {
  const fadeSamples = Math.floor(fadeSec * SAMPLE_RATE);
  const startFade = buf.length - fadeSamples;
  for (let i = 0; i < fadeSamples; i++) {
    const t = i / fadeSamples;
    const envFade = 0.5 * (1 + Math.cos(Math.PI * t));
    buf[startFade + i] *= envFade;
  }
}

function writeWav(filepath, samples) {
  const dataLen = samples.length * 2;
  const header = Buffer.alloc(44);
  header.write('RIFF', 0);
  header.writeUInt32LE(36 + dataLen, 4);
  header.write('WAVE', 8);
  header.write('fmt ', 12);
  header.writeUInt32LE(16, 16);
  header.writeUInt16LE(1, 20);
  header.writeUInt16LE(CHANNELS, 22);
  header.writeUInt32LE(SAMPLE_RATE, 24);
  header.writeUInt32LE(SAMPLE_RATE * CHANNELS * BIT_DEPTH / 8, 28);
  header.writeUInt16LE(CHANNELS * BIT_DEPTH / 8, 32);
  header.writeUInt16LE(BIT_DEPTH, 34);
  header.write('data', 36);
  header.writeUInt32LE(dataLen, 40);

  let peak = 0;
  for (let i = 0; i < samples.length; i++) {
    if (Math.abs(samples[i]) > peak) peak = Math.abs(samples[i]);
  }
  const scale = peak > 0 ? 0.95 / peak : 1;

  const body = Buffer.alloc(dataLen);
  for (let i = 0; i < samples.length; i++) {
    const s = Math.max(-1, Math.min(1, samples[i] * scale));
    body.writeInt16LE(Math.round(s * 32767), i * 2);
  }

  fs.writeFileSync(filepath, Buffer.concat([header, body]));
}

const harmonics = [
  { m: 1.0,  a: 1.00, decayScale: 1.00 },
  { m: 2.0,  a: 0.40, decayScale: 0.55 },
  { m: 3.0,  a: 0.25, decayScale: 0.35 },
  { m: 4.01, a: 0.20, decayScale: 0.22 },
  { m: 5.4,  a: 0.14, decayScale: 0.15 },
  { m: 6.8,  a: 0.08, decayScale: 0.10 },
];

const F1 = 392.00;
const F2 = 587.33;
const total = 1.4;
const offset = 0.18;

const buf = synth(total);
addBellTone(buf, 0.00,   F1, total,          0.9, 0.45, harmonics);
addBellTone(buf, offset, F2, total - offset, 0.7, 0.40, harmonics);
applyTailFade(buf, 0.40);

writeWav(path.join(__dirname, 'notification.wav'), buf);
console.log('wrote notification.wav');
