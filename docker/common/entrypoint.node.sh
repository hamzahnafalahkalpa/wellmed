#!/bin/sh
set -e

echo "[NODE] Starting entrypoint..."

# Pastikan folder CSS ada
mkdir -p /public/css/wellmed-backbone
mkdir -p /public/css/hq

# Install dependencies di container (npm ci lebih cepat kalau ada package-lock.json)
echo "[NODE] Installing dependencies..."
npm install

# Build CSS backbone
echo "[NODE] Building backbone export.css..."
npm run build:backbone

# Build CSS hq
echo "[NODE] Building hq export.css..."
npm run build:hq

# Biar container tetap hidup
echo "[NODE] Node container ready. Keeping alive..."
tail -f /dev/null
