#!/usr/bin/env bash
# Menyusun paket siap pasang untuk hosting cPanel (kode + vendor tanpa paket dev),
# lalu (dengan --push) menyimpannya sebagai commit baru di branch "hosting".
#
#   composer install --no-dev --optimize-autoloader
#   bash scripts/publish-hosting.sh            # hanya menyusun dan memeriksa
#   bash scripts/publish-hosting.sh --push     # menyusun, memeriksa, lalu push ke branch hosting
#
# Server tinggal "git pull --ff-only" di checkout branch hosting. File .env, database SQLite,
# lampiran murid, dan materi guru tidak pernah ikut (diabaikan .gitignore), jadi aman saat pull.
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"
SHA="$(git rev-parse HEAD)"
SHORT="$(git rev-parse --short HEAD)"
OUT="$(mktemp -d)"

if [ ! -f vendor/autoload.php ]; then
  echo "vendor/ belum ada. Jalankan: composer install --no-dev --optimize-autoloader" >&2
  exit 1
fi
if [ -d vendor/phpunit ]; then
  echo "vendor/ masih berisi paket dev. Jalankan: composer install --no-dev --optimize-autoloader" >&2
  exit 1
fi

# 1. Kode yang ter-commit saja (bukan file lokal seperti .env atau database).
git archive HEAD | tar -x -C "$OUT"
cp -R vendor "$OUT/vendor"

# 2. Buang yang tidak dibutuhkan server.
rm -rf "$OUT/tests" "$OUT/.github" "$OUT/scripts" "$OUT/phpunit.xml" \
       "$OUT/package.json" "$OUT/vite.config.js" "$OUT/resources/css" "$OUT/resources/js"

# 3. Branch hosting menyimpan vendor/, tetapi tetap mengabaikan .env, database, dan file runtime.
grep -v '^/vendor$' .gitignore > "$OUT/.gitignore"
printf 'source: %s\nbuilt: %s\n' "$SHA" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$OUT/build-source.txt"

# 4. Periksa paket: tidak ada rahasia, dan aplikasi bisa dinyalakan.
for f in .env database/database.sqlite; do
  if [ -e "$OUT/$f" ]; then echo "Berkas rahasia ikut: $f" >&2; exit 1; fi
done
if grep -rIlE 'AIza[0-9A-Za-z_-]{30,}|gsk_[0-9A-Za-z]{30,}' "$OUT" --exclude-dir=vendor >/dev/null; then
  echo "Ada pola API key di paket. Batal." >&2; exit 1
fi
CHECK_DB="$(mktemp -d)/cek.sqlite"
touch "$CHECK_DB"
( cd "$OUT" && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" APP_ENV=production APP_DEBUG=false \
    DB_DATABASE="$CHECK_DB" CACHE_STORE=array SESSION_DRIVER=array \
    php artisan migrate --force --no-interaction >/dev/null \
  && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" APP_ENV=production DB_DATABASE="$CHECK_DB" \
    CACHE_STORE=array php artisan route:list --except-vendor >/dev/null )
rm -rf "$OUT/bootstrap/cache/"*.php
echo "Paket hosting siap dan lolos pemeriksaan: $OUT (sumber $SHORT)"

[ "${1:-}" = "--push" ] || exit 0

# 5. Commit di atas branch hosting yang sudah ada (riwayat lurus, server cukup pull --ff-only).
PUB="$(mktemp -d)/hosting"
if git ls-remote --exit-code --heads origin hosting >/dev/null 2>&1; then
  git fetch --quiet origin hosting
  git worktree add --quiet -B hosting "$PUB" origin/hosting
else
  git worktree add --quiet --detach "$PUB"
  git -C "$PUB" checkout --quiet --orphan hosting
fi

find "$PUB" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp -R "$OUT/." "$PUB/"
git -C "$PUB" add -A
if git -C "$PUB" diff --cached --quiet; then
  echo "Branch hosting sudah sama dengan $SHORT."
else
  git -C "$PUB" -c user.name="github-actions[bot]" -c user.email="41898282+github-actions[bot]@users.noreply.github.com" \
    commit --quiet -m "Paket hosting dari $SHORT"
  git -C "$PUB" push --quiet origin hosting
  echo "Branch hosting diperbarui dari $SHORT."
fi
git worktree remove --force "$PUB"
