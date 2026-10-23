#!/usr/bin/env bash
# boutique-ai: download the free hero and fabric originals (Unsplash License — free for commercial use).
# Run from the project root:  bash scripts/download-assets.sh
# Optional video:             bash scripts/download-assets.sh --video
# Safe to re-run — existing files are not downloaded again.
set -uo pipefail
cd "$(dirname "$0")/.."

UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Safari/537.36"
FAILED=()

dl() {
  local url="$1" out="$2"
  if [ -s "$out" ]; then echo "  exists:      $out"; return; fi
  echo "  downloading: $out"
  if curl -fsSL --retry 2 --max-time 180 -A "$UA" -o "$out.part" "$url"; then
    mv "$out.part" "$out"
  else
    rm -f "$out.part"
    FAILED+=("$out  <=  $url")
  fi
}

mkdir -p public/media/hero public/media/fabrics

echo "Hero photos (Unsplash, 1920px):"
dl "https://unsplash.com/photos/Ci_fZ5cL9Jo/download?force=true&w=1920" public/media/hero/p1-blue-suit.jpg
dl "https://unsplash.com/photos/HgyAIYvMWkw/download?force=true&w=1920" public/media/hero/p2-blue-suit.jpg
dl "https://unsplash.com/photos/RuBXafq461A/download?force=true&w=1920" public/media/hero/p3-grey-suit-stairs.jpg
dl "https://unsplash.com/photos/vS0Kya7E5V4/download?force=true&w=1920" public/media/hero/p4-black-suit.jpg
dl "https://unsplash.com/photos/G-jo31ESuRE/download?force=true&w=1920" public/media/hero/p5-pinstripe.jpg
dl "https://unsplash.com/photos/YGJ9vfuwyUg/download?force=true&w=1920" public/media/hero/p6-black-suit.jpg

echo "Fabric textures (Unsplash, 800px):"
dl "https://unsplash.com/photos/SIOtRQKmi10/download?force=true&w=800" public/media/fabrics/f1-grey-check.jpg
dl "https://unsplash.com/photos/t3mSDMjd9ZY/download?force=true&w=800" public/media/fabrics/f2-brown-tweed.jpg
dl "https://unsplash.com/photos/kwpJY3RbObo/download?force=true&w=800" public/media/fabrics/f3-blue-grey.jpg

if [ "${1:-}" = "--video" ]; then
  echo "Hero video (Pexels — may be large):"
  dl "https://www.pexels.com/download/video/6765466/" public/media/hero/v2-tailor-suit.mp4
fi

# Credits are maintained by hand in public/media/CREDITS.md (this script does not overwrite them).

echo
if [ ${#FAILED[@]} -eq 0 ]; then
  echo "Done. Files:"; ls -lh public/media/hero public/media/fabrics
else
  echo "Some downloads failed — open them in a browser, download manually and save under the same name:"
  printf '  %s\n' "${FAILED[@]}"
  exit 1
fi
