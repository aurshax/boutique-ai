#!/usr/bin/env bash
# boutique-ai: bepul hero va tkan rasmlarini yuklab olish (Unsplash License — tijoratda bepul).
# Ishga tushirish (loyiha papkasidan):  bash scripts/download-assets.sh
# Video ixtiyoriy:                        bash scripts/download-assets.sh --video
# Qayta ishga tushirsa bo'ladi — bor fayllar qayta yuklanmaydi.
set -uo pipefail
cd "$(dirname "$0")/.."

UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Safari/537.36"
FAILED=()

dl() {
  local url="$1" out="$2"
  if [ -s "$out" ]; then echo "  bor:        $out"; return; fi
  echo "  yuklanmoqda: $out"
  if curl -fsSL --retry 2 --max-time 180 -A "$UA" -o "$out.part" "$url"; then
    mv "$out.part" "$out"
  else
    rm -f "$out.part"
    FAILED+=("$out  <=  $url")
  fi
}

mkdir -p public/media/hero public/media/fabrics

echo "Hero rasmlari (Unsplash, 1920px):"
dl "https://unsplash.com/photos/Ci_fZ5cL9Jo/download?force=true&w=1920" public/media/hero/p1-blue-suit.jpg
dl "https://unsplash.com/photos/HgyAIYvMWkw/download?force=true&w=1920" public/media/hero/p2-blue-suit.jpg
dl "https://unsplash.com/photos/RuBXafq461A/download?force=true&w=1920" public/media/hero/p3-grey-suit-stairs.jpg
dl "https://unsplash.com/photos/vS0Kya7E5V4/download?force=true&w=1920" public/media/hero/p4-black-suit.jpg
dl "https://unsplash.com/photos/G-jo31ESuRE/download?force=true&w=1920" public/media/hero/p5-pinstripe.jpg
dl "https://unsplash.com/photos/YGJ9vfuwyUg/download?force=true&w=1920" public/media/hero/p6-black-suit.jpg

echo "Tkan teksturalari (Unsplash, 800px):"
dl "https://unsplash.com/photos/SIOtRQKmi10/download?force=true&w=800" public/media/fabrics/f1-grey-check.jpg
dl "https://unsplash.com/photos/t3mSDMjd9ZY/download?force=true&w=800" public/media/fabrics/f2-brown-tweed.jpg
dl "https://unsplash.com/photos/kwpJY3RbObo/download?force=true&w=800" public/media/fabrics/f3-blue-grey.jpg

if [ "${1:-}" = "--video" ]; then
  echo "Hero video (Pexels — hajmi katta bo'lishi mumkin):"
  dl "https://www.pexels.com/download/video/6765466/" public/media/hero/v2-tailor-suit.mp4
fi

cat > public/media/CREDITS.md <<'MD'
# Media manbalari

Barcha rasmlar Unsplash License (https://unsplash.com/license) va Pexels License (https://www.pexels.com/license/) asosida.

| Fayl | Manba |
|---|---|
| hero/p1-blue-suit.jpg | https://unsplash.com/photos/man-in-blue-suit-standing-Ci_fZ5cL9Jo |
| hero/p2-blue-suit.jpg | https://unsplash.com/photos/man-in-blue-suit-standing-HgyAIYvMWkw |
| hero/p3-grey-suit-stairs.jpg | https://unsplash.com/photos/man-in-gray-suit-jacket-and-black-pants-wearing-black-sunglasses-standing-on-gray-concrete-stairs-RuBXafq461A |
| hero/p4-black-suit.jpg | https://unsplash.com/photos/man-in-black-suit-jacket-and-black-pants-standing-on-gray-concrete-floor-during-daytime-vS0Kya7E5V4 |
| hero/p5-pinstripe.jpg | https://unsplash.com/photos/man-in-white-and-black-pinstripe-suit-jacket-G-jo31ESuRE |
| hero/p6-black-suit.jpg | https://unsplash.com/photos/man-in-black-suit-jacket-and-black-dress-pants-YGJ9vfuwyUg |
| fabrics/f1-grey-check.jpg | https://unsplash.com/photos/a-close-up-of-a-gray-and-black-checkered-fabric-SIOtRQKmi10 |
| fabrics/f2-brown-tweed.jpg | https://unsplash.com/photos/a-close-up-of-a-brown-and-black-fabric-t3mSDMjd9ZY |
| fabrics/f3-blue-grey.jpg | https://unsplash.com/photos/a-close-up-of-a-blue-and-grey-fabric-kwpJY3RbObo |
| hero/v2-tailor-suit.mp4 (ixtiyoriy) | https://www.pexels.com/video/tailor-adjusting-suit-for-male-customer-6765466/ |
MD

echo
if [ ${#FAILED[@]} -eq 0 ]; then
  echo "Tayyor. Fayllar:"; ls -lh public/media/hero public/media/fabrics
else
  echo "Ba'zilari yuklanmadi — brauzerda ochib, qo'lda yuklab, shu nom bilan saqlang:"
  printf '  %s\n' "${FAILED[@]}"
  exit 1
fi
