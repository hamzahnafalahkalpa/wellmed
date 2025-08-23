#!/bin/sh
set -e

# Tambahkan Git safe directory
git config --global --add safe.directory /app

cd /app

# --- Clone or Update Repo ---
if [ ! -f artisan ] && [ ! -d .git ]; then
    echo "🚀 Cloning Laravel project from GitLab..."
    rm -rf /app/* /app/.[!.]* /app/..?* || true
    git clone --recurse-submodules --branch 1.x https://gitlab.com/bundling-features/klinik-microtenant.git /app
    cd /app
fi
if [ -d .git ]; then
    echo "🔄 Updating existing repo..."
    git fetch origin 1.x || true
    git checkout 1.x || true
    git pull origin 1.x --no-rebase || true
    echo "📦 Updating submodules..."
    git submodule update --init --recursive -f
    git submodule foreach "git fetch origin 1.x && git checkout 1.x && git pull origin 1.x --no-rebase || true"
else
    echo "⚠️  /app is not a git repo, skipping clone/update."
fi

# --- Install dependencies ---
if [ ! -d vendor ]; then
    echo "📦 Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# --- Starterpack Install ---
if [ -f artisan ]; then
    if [ ! -f .starterpack_installed ]; then
        echo "⚡ Running klinik-starterpack:install..."
        php artisan klinik-starterpack:install || true
        touch .starterpack_installed
    else
        echo "✅ Starterpack already installed, skipping."
    fi
fi

# --- Start Laravel Octane ---
exec php artisan octane:frankenphp
