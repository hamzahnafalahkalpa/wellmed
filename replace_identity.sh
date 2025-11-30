#!/bin/bash

# ====================================
# Konfigurasi Folder Target
# ====================================
REPO_DIR="repositories"
PROJECTS_DIR="projects"
APP_DIR="app"
CONFIG_DIR="config"
ROUTES_DIR="routes"
BOOTSTRAP_DIR="bootstrap"
DATABASE_DIR="database"
STUBS_DIR="stubs"
ROOT_COMPOSER="composer.json"

# ====================================
# Mapping String Lama -> Baru
# ====================================
declare -A replacements=(
  ["subagadigitalmedika"]="hanafalah"
  ["Subagadigitalmedika"]="Hanafalah"
  ["subaga"]="hamzah nur al falah"
  ["subaga@mail.com"]="hamzahnafalah@gmail.com"
)

# ====================================
# Fungsi Replace isi file
# ====================================
replace_in_file() {
  local file="$1"
  for search in "${!replacements[@]}"; do
    local replace="${replacements[$search]}"
    sed -i "s/${search}/${replace}/g" "$file"
  done
}

# ====================================
# Fungsi Replace di folder recursive
# ====================================
replace_in_directory() {
  local target_dir="$1"
  echo "🔍 Scanning folder: $target_dir ..."
  for search in "${!replacements[@]}"; do
    echo "➡️  Ganti '$search' ➜ '${replacements[$search]}'"
    grep -ril --exclude-dir={.git,node_modules,vendor,storage,logs} "$search" "$target_dir" | while read -r file; do
      replace_in_file "$file"
    done
  done
}

# ====================================
# Eksekusi Utama
# ====================================
echo "🚀 Mulai proses replace string di seluruh sistem..."

# Daftar folder target
targets=(
  "$REPO_DIR"
  "$PROJECTS_DIR"
  "$APP_DIR"
  "$CONFIG_DIR"
  "$ROUTES_DIR"
  "$BOOTSTRAP_DIR"
  "$DATABASE_DIR"
  "$STUBS_DIR"
)

# Jalankan per folder
for dir in "${targets[@]}"; do
  if [ -d "$dir" ]; then
    replace_in_directory "$dir"
  else
    echo "⚠️  Folder '$dir' tidak ditemukan, skip"
  fi
done

# composer.json di root
if [ -f "$ROOT_COMPOSER" ]; then
  echo "🧩 Update composer.json di root..."
  replace_in_file "$ROOT_COMPOSER"
else
  echo "⚠️  composer.json di root tidak ditemukan, skip"
fi

echo "✅ Semua proses selesai!"
