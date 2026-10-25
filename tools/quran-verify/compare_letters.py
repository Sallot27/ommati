"""Compare the app's bundled mushaf letter-by-letter against a reference text.

Usage: python3 -I compare_quran.py <app quran.json> <reference.json (fawazahmed0 format)> <label>

Both are reduced to their letters (the rasm): diacritics, Quranic annotation
marks and stop signs are removed, and alternative Unicode encodings of the same
letter are unified. Any remaining difference is a real difference in letters.
"""
import json
import re
import sys
import unicodedata

app = {}
for s in json.load(open(sys.argv[1]))['surahs']:
    for i, t in enumerate(s['ayahs']):
        app[(s['n'], i + 1)] = t

ref = {}
for v in json.load(open(sys.argv[2]))['quran']:
    ref[(v['chapter'], v['verse'])] = v['text']
label = sys.argv[3]

BASMALA_LETTERS = 'بسماللهالرحمنالرحيم'

# Map of encoding variants to one canonical letter.
CANON = str.maketrans({
    'ٱ': 'ا', 'أ': 'ا', 'إ': 'ا', 'آ': 'ا', 'ٲ': 'ا', 'ٳ': 'ا',
    'ى': 'ي', 'ی': 'ي', 'ې': 'ي', 'ئ': 'ي',
    'ک': 'ك', 'ؤ': 'و', 'ۥ': '', 'ۦ': '',
    'ة': 'ه', 'ۀ': 'ه', 'ہ': 'ه',
    'ء': '',
})


def letters(text):
    out = []
    for ch in unicodedata.normalize('NFC', text):
        cat = unicodedata.category(ch)
        # Keep Arabic letters only; drop marks (Mn), signs, digits, punctuation, spaces.
        if cat.startswith('L') and ch != '\u0640':
            out.append(ch)
    return ''.join(out).translate(CANON)


diffs = []
for key in sorted(ref):
    if key not in app:
        diffs.append((key, 'missing in app', '', ''))
        continue
    a, r = letters(app[key]), letters(ref[key])
    # Some references prefix the basmala to ayah 1; the app keeps it separate.
    if key[1] == 1 and key[0] not in (1, 9) and r.startswith(BASMALA_LETTERS) and not a.startswith(BASMALA_LETTERS):
        r = r[len(BASMALA_LETTERS):]
    if a != r:
        diffs.append((key, 'letters differ', a, r))
extra = [k for k in app if k not in ref]

print(f'{label}: compared {len(ref)} ayat — {len(diffs)} differ, {len(extra)} extra in app')
for key, kind, a, r in diffs[:200]:
    if kind != 'letters differ':
        print(key, kind)
        continue
    # Show the first point of difference with context.
    i = next((j for j in range(min(len(a), len(r))) if a[j] != r[j]), min(len(a), len(r)))
    print(f'{key[0]}:{key[1]}  app …{a[max(0, i - 12):i + 12]}…  ref …{r[max(0, i - 12):i + 12]}…')
