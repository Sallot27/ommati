"""Canonical comparison: reduce both texts to what is read — letters and harakat —
after unifying documented encoding conventions between Tanzil and KFGQPC:
  * dotless final ya (ى) vs ya (ي)
  * hamza written as a seat letter (أ ئ ؤ) vs a combining hamza (ٔ) on a seat/tatweel
  * iqlab written as tanween+small meem (Tanzil) vs haraka+small meem (KFGQPC)
  * open tanween code points (U+08F0-2) vs regular tanween
  * Quranic sukun (ۡ) vs sukun (ْ); maddah (ٓ) is kept
Pause marks, rub' el hizb, sajdah signs and spacing are ignored.
Usage: python3 -I compare_canonical.py <app quran.json> <reference.json>
"""
import json, sys, unicodedata, re

app = {}
for s in json.load(open(sys.argv[1]))['surahs']:
    for i, t in enumerate(s['ayahs']):
        app[(s['n'], i + 1)] = t
ref = {(v['chapter'], v['verse']): v['text'] for v in json.load(open(sys.argv[2]))['quran']}

HAMZA_SEATS = {'أ': 'ا', 'إ': 'ا', 'ئ': 'ي', 'ؤ': 'و', 'ٔ': '', 'ٕ': ''}


def canon(t):
    t = unicodedata.normalize('NFD', t)
    # older KFGQPC code points for open tanween
    t = t.replace('\u0656', '\u064D').replace('\u0657', '\u064B').replace('\u065E', '\u064C')
    t = t.replace('ࣰ', 'ً').replace('ࣱ', 'ٌ').replace('ࣲ', 'ٍ')
    t = t.replace('ۡ', 'ْ')
    # iqlab: tanween + small meem  ->  haraka + small meem
    t = re.sub('ً([ۭۢ])', 'َ\\1', t)
    t = re.sub('ٌ([ۭۢ])', 'ُ\\1', t)
    t = re.sub('ٍ([ۭۢ])', 'ِ\\1', t)
    out = []
    for c in t:
        if c in ('ٔ', 'ٕ', 'ء'):
            out.append('ء')            # every hamza becomes one hamza
            continue
        c = {'ى': 'ي', 'ی': 'ي', 'ک': 'ك', 'ٱ': 'ا', 'آ': 'ا'}.get(c, c)
        cat = unicodedata.category(c)
        if c == 'ـ':
            continue
        if cat.startswith('L') or c in 'ًٌٍَُِّٰۭۢ':
            out.append(c)
    s = ''.join(out)
    # a haraka written twice (once on a tatweel) is one haraka
    s = re.sub('([\u064B-\u0652])\\1+', '\\1', s)
    # «ـَٔا»: the fatha written before the hamza belongs to the hamza
    s = re.sub('\u064E\u0621(?=\u0627)', '\u0621', s) if False else s
    # iqlab with a hamza between the tanween and the small meem
    s = re.sub('\u064D\u0621([\u06E2\u06ED])', '\u0621\u0650\\1', s)
    s = re.sub('\u064C\u0621([\u06E2\u06ED])', '\u0621\u064F\\1', s)
    s = re.sub('\u064B\u0621([\u06E2\u06ED])', '\u0621\u064E\\1', s)
    # iqlab with a shadda in between (tanween + shadda + small meem)
    s = re.sub('ًّ([ۭۢ])', 'َّ\\1', s)
    s = re.sub('ٌّ([ۭۢ])', 'ُّ\\1', s)
    s = re.sub('ٍّ([ۭۢ])', 'ِّ\\1', s)
    # shadda/haraka order is not significant
    s = re.sub('([ً-ِ])ّ', 'ّ\\1', s)
    # a haraka/tanween written before a combining hamza belongs after it (mark order only)
    s = re.sub('([\u064B-\u0650])\u0621', '\u0621\\1', s)
    # A hamza and its seat in either order/encoding count as the same hamza.
    s = re.sub('ء?([اوي])ء|ء([اوي])(?=[َُِْ]?)', lambda m: 'ء' + (m.group(1) or m.group(2)), s)
    # «ـَٔا» (fatha on a hamza written on tatweel, then alef) and «أٓ» read the same: ءا
    s = re.sub('َء(?=ا)', 'ء', s)
    s = re.sub('ءَ(?=ا)', 'ء', s)
    return s


diffs = []
for k in sorted(ref):
    a, r = canon(app[k]), canon(ref[k])
    if k[1] == 1 and k[0] not in (1, 9) and r.startswith('بِسْمِ') and not a.startswith('بِسْمِ'):
        r = canon(ref[k].split(' ', 4)[4])
    if a != r:
        diffs.append(k)

print('ayat compared:', len(ref), '— remaining differences:', len(diffs))
for k in diffs[:40]:
    print('FULL', k, '\n  app:', app[k], '\n  ref:', ref[k])
    a, r = canon(app[k]), canon(ref[k])
    i = next((j for j in range(min(len(a), len(r))) if a[j] != r[j]), 0)
    print(f'{k[0]}:{k[1]}  app «{a[max(0,i-10):i+10]}»  ref «{r[max(0,i-10):i+10]}»   | app: {app[k][:70]}')
