#!/bin/bash
#
# Script untuk sinkronisasi folder dari packages/* ke repositories/*
# Description:
#   - Replace src/, assets/, dan composer.json
#   - Jika repository module belum ada, dilewati
#   - Jalankan script ini dari root project

set -e

echo ""
echo "🚀  Memulai sinkronisasi packages → repositories ..."
echo ""

# Loop semua folder dalam packages/
for dir in packages/*; do
  # Pastikan hanya folder
  [ -d "$dir" ] || continue

  module=$(basename "$dir")
  repo_path="repositories/$module"

  if [ -d "$repo_path" ]; then
    echo "🔄 Sinkronisasi module: $module"

    # Pastikan folder src & assets dihapus dulu agar tidak tercampur
    if [ -d "$repo_path/src" ]; then
      rm -rf "$repo_path/src"
    fi
    if [ -d "$repo_path/assets" ]; then
      rm -rf "$repo_path/assets"
    fi

    # Copy folder src dan assets dari packages ke repositories
    if [ -d "$dir/src" ]; then
      cp -r "$dir/src" "$repo_path/"
    fi
    if [ -d "$dir/assets" ]; then
      cp -r "$dir/assets" "$repo_path/"
    fi

    # Replace composer.json kalau ada
    if [ -f "$dir/composer.json" ]; then
      cp "$dir/composer.json" "$repo_path/"
    fi

    echo "✅  $module selesai disinkronisasi."
    echo ""
  else
    echo "⚠️  Lewati $module — folder repositories/$module tidak ditemukan."
  fi
done

echo "✨  Semua sinkronisasi selesai!"
