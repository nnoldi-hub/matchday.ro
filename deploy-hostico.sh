#!/bin/bash

# Script de deployment pentru MatchDay.ro pe Hostico
# Autor: David Nyikora

echo "🚀 Starting MatchDay.ro deployment..."

# Variables
REPO_URL="https://github.com/username/matchday.ro.git"
TEMP_DIR="/tmp/matchday-deploy"
WEB_DIR="/public_html"
BACKUP_DIR="/backups/matchday-$(date +%Y%m%d-%H%M%S)"

echo "📦 Creating backup of current site..."
mkdir -p $BACKUP_DIR
cp -r $WEB_DIR/* $BACKUP_DIR/ 2>/dev/null || true

echo "📥 Downloading latest version from GitHub..."
rm -rf $TEMP_DIR
git clone $REPO_URL $TEMP_DIR

if [ $? -ne 0 ]; then
    echo "❌ Error: Could not clone repository"
    exit 1
fi

echo "📋 Copying files..."
# Preserve config and data
cp $WEB_DIR/config/config.php $TEMP_DIR/config/config.php 2>/dev/null || true
cp -r $WEB_DIR/data/* $TEMP_DIR/data/ 2>/dev/null || true
cp -r $WEB_DIR/assets/uploads/* $TEMP_DIR/assets/uploads/ 2>/dev/null || true

# Deploy new files
rsync -av --exclude='.git' $TEMP_DIR/ $WEB_DIR/

echo "🔧 Setting permissions..."
chmod 755 $WEB_DIR/data/
chmod 755 $WEB_DIR/assets/uploads/
find $WEB_DIR/data/ -type f -name "*.json" -exec chmod 644 {} \;

echo "🗑️ Clearing cache..."
rm -f $WEB_DIR/data/cache/*.cache

echo "🧹 Cleanup..."
rm -rf $TEMP_DIR

echo "✅ Deployment completed successfully!"
echo "📂 Backup saved to: $BACKUP_DIR"
echo "🌐 Site available at: https://matchday.ro"
