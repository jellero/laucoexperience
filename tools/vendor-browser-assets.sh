#!/usr/bin/env bash
# Build-time only. No npm or CDN requests are needed by the deployed website.
set -euo pipefail
cd "$(dirname "$0")/.."
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
mkdir -p "$tmp/out"
pack() {
  local spec="$1" name="$2"
  local dest="$tmp/out/$name"
  mkdir -p "$dest"
  npm pack --ignore-scripts --json --pack-destination "$tmp" "$spec" > "$tmp/$name.json"
  local tarball
  tarball="$(node -e 'const r=require(process.argv[1])[0];process.stdout.write(r.filename)' "$tmp/$name.json")"
  tar -xzf "$tmp/$tarball" -C "$dest" --strip-components=1
}
pack leaflet@1.9.4 leaflet
pack leaflet-gesture-handling@1.2.2 leaflet-gesture-handling
pack @tmcw/togeojson@5.8.1 togeojson
pack @turf/turf@6.5.0 turf
pack d3@6.5.0 d3
pack @raruto/leaflet-elevation@2.5.2 leaflet-elevation
# Elevation's fallback dynamic imports use these exact upstream versions.
pack d3@7.8.4 d3-elevation
pack @tmcw/togeojson@5.6.2 togeojson-elevation
pack leaflet-geometryutil@0.10.1 leaflet-geometryutil
pack leaflet-almostover@1.0.1 leaflet-almostover
pack leaflet.motion@0.3.2 leaflet-motion
python3 - "$tmp" <<'PY'
import json, pathlib, sys, shutil
root = pathlib.Path(sys.argv[1])
out = root / 'out'
replacement = {
 'https://unpkg.com/leaflet.motion@0.3.2/dist/leaflet.motion.min.js': '/assets/vendor/leaflet-motion/dist/leaflet.motion.min.js',
 'https://unpkg.com/d3@7.8.4/dist/d3.min.js': '/assets/vendor/d3-elevation/dist/d3.min.js',
 'https://unpkg.com/@tmcw/togeojson@5.6.2/dist/togeojson.umd.js': '/assets/vendor/togeojson-elevation/dist/togeojson.umd.js',
 'https://unpkg.com/leaflet-geometryutil@0.10.1/src/leaflet.geometryutil.js': '/assets/vendor/leaflet-geometryutil/src/leaflet.geometryutil.js',
 'https://unpkg.com/leaflet-almostover@1.0.1/src/leaflet.almostover.js': '/assets/vendor/leaflet-almostover/src/leaflet.almostover.js',
}
elevation = out / 'leaflet-elevation'
for p in elevation.rglob('*.js'):
    s = p.read_text(encoding='utf-8')
    changed = s
    for old, new in replacement.items():
        changed = changed.replace(old, new)
    if changed != s:
        p.write_text(changed, encoding='utf-8')
# Retain browser distributions and all runtime subdirectories for Elevation.
# Keep package metadata and license/notice files; remove unrelated build tooling.
keep = {
 'leaflet': {'dist'}, 'leaflet-gesture-handling': {'dist'},
 'togeojson': {'dist'}, 'turf': {'turf.min.js'},
 'd3': {'dist'}, 'd3-elevation': {'dist'}, 'togeojson-elevation': {'dist'},
 'leaflet-elevation': {'dist','src','libs','images'},
 'leaflet-geometryutil': {'src'}, 'leaflet-almostover': {'src'}, 'leaflet-motion': {'dist'},
}
for name, entries in keep.items():
    for p in (out / name).iterdir():
        if p.name in entries or p.name == 'package.json' or p.name.lower().startswith(('license','licence','copying','notice','copyright','readme')):
            continue
        if p.is_dir():
            shutil.rmtree(p)
        else:
            p.unlink()
# Assert every public entrypoint, local dynamic import and common Leaflet icon.
required = [
 'leaflet/dist/leaflet.js','leaflet/dist/leaflet.css',
 'leaflet/dist/images/marker-icon.png','leaflet/dist/images/marker-icon-2x.png',
 'leaflet/dist/images/marker-shadow.png','leaflet/dist/images/layers.png',
 'leaflet/dist/images/layers-2x.png',
 'leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js',
 'leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css',
 'togeojson/dist/togeojson.umd.js','turf/turf.min.js','d3/dist/d3.min.js',
 'leaflet-elevation/dist/leaflet-elevation.min.js','leaflet-elevation/dist/leaflet-elevation.min.css',
 'leaflet-elevation/src/components/chart.js','leaflet-elevation/src/components/marker.js',
 'leaflet-elevation/src/components/summary.js','leaflet-elevation/src/handlers/altitude.js',
 'leaflet-elevation/libs/leaflet-distance-marker.min.js','leaflet-elevation/libs/leaflet-hotline.min.js',
 'leaflet-elevation/libs/leaflet-edgescale.min.js','leaflet-elevation/images/elevation.svg',
 'd3-elevation/dist/d3.min.js','togeojson-elevation/dist/togeojson.umd.js',
 'leaflet-geometryutil/src/leaflet.geometryutil.js','leaflet-almostover/src/leaflet.almostover.js',
 'leaflet-motion/dist/leaflet.motion.min.js',
]
for rel in required:
    p = out / rel
    if not p.is_file() or not p.stat().st_size:
        raise SystemExit('Missing required local asset: ' + rel)
# Fail closed if upstream runtime still points at the CDN.
for p in elevation.rglob('*.js'):
    if 'https://unpkg.com/' in p.read_text(encoding='utf-8'):
        raise SystemExit('Unresolved CDN dependency in ' + str(p))
manifest = {}
for name in sorted(keep):
    entry = json.loads((root / (name + '.json')).read_text())[0]
    manifest[name] = {k: entry[k] for k in ('name','version','integrity','shasum') if k in entry}
(out / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
PY
# Replace only this dedicated vendor directory, not any existing template assets.
mkdir -p assets/vendor
cp -a "$tmp/out/." assets/vendor/
test -s assets/vendor/html5shiv/dist/html5shiv.min.js
test -s assets/vendor/respond/dest/respond.min.js
echo "Local browser assets generated and verified."
