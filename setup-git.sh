#!/bin/bash

# Script pentru inițializarea și configurarea Git pentru MatchDay.ro
# Autor: David Nyikora

echo "🚀 Inițializare Git pentru MatchDay.ro..."

# Verifică dacă Git este instalat
if ! command -v git &> /dev/null; then
    echo "❌ Git nu este instalat. Te rog instalează Git mai întâi."
    exit 1
fi

# Inițializează repository-ul dacă nu există
if [ ! -d ".git" ]; then
    echo "📦 Inițializare Git repository..."
    git init
    echo "✅ Repository inițializat!"
fi

# Configurează Git user (dacă nu sunt setate)
if [ -z "$(git config user.name)" ]; then
    echo "👤 Configurare Git user..."
    read -p "Nume complet (ex: David Nyikora): " git_name
    read -p "Email (ex: contact@matchday.ro): " git_email
    
    git config user.name "$git_name"
    git config user.email "$git_email"
    echo "✅ User configurat!"
fi

# Adaugă toate fișierele
echo "📝 Adăugare fișiere în staging..."
git add .

# Primul commit
if [ -z "$(git log --oneline -n 1 2>/dev/null)" ]; then
    echo "🎯 Primul commit..."
    git commit -m "🚀 Initial commit: MatchDay.ro - Complete website

    ✨ Features:
    - SEO optimized blog system
    - Interactive polls system  
    - Comments with moderation
    - Editorial calendar
    - Admin dashboard
    - Mobile responsive design
    
    👤 Author: David Nyikora
    📧 Contact: contact@matchday.ro
    🌐 Website: https://matchday.ro"
    echo "✅ Primul commit realizat!"
else
    echo "🔄 Commit cu modificările recente..."
    git commit -m "📝 Update: Latest changes to MatchDay.ro

    - Updated author name to David Nyikora
    - Added contact information
    - Configured GitHub Actions deployment
    - Improved project structure"
    echo "✅ Commit realizat!"
fi

echo ""
echo "🎉 Git configurat cu succes!"
echo ""
echo "📋 Următorii pași:"
echo "1. Creează un repository pe GitHub"
echo "2. Copiază URL-ul repository-ului"
echo "3. Rulează: git remote add origin <URL_GITHUB>"
echo "4. Rulează: git branch -M main"  
echo "5. Rulează: git push -u origin main"
echo ""
echo "🔐 Nu uita să configurezi secrets în GitHub Actions!"
echo "📄 Vezi DEPLOYMENT-CONFIG.md pentru detalii"
