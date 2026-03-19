@echo off
echo 🚀 Inițializare Git pentru MatchDay.ro...

REM Verifică dacă Git este instalat
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Git nu este instalat. Te rog instalează Git mai întâi.
    echo 📥 Download: https://git-scm.com/download/win
    pause
    exit /b 1
)

REM Inițializează repository-ul dacă nu există
if not exist ".git" (
    echo 📦 Inițializare Git repository...
    git init
    echo ✅ Repository inițializat!
)

REM Configurează Git user (dacă nu sunt setate)
for /f "tokens=*" %%i in ('git config user.name 2^>nul') do set git_name=%%i
if not defined git_name (
    echo 👤 Configurare Git user...
    set /p git_name="Nume complet (ex: David Nyikora): "
    set /p git_email="Email (ex: contact@matchday.ro): "
    
    git config user.name "%git_name%"
    git config user.email "%git_email%"
    echo ✅ User configurat!
)

REM Adaugă toate fișierele
echo 📝 Adăugare fișiere în staging...
git add .

REM Verifică dacă există commits
git log --oneline -n 1 >nul 2>&1
if errorlevel 1 (
    echo 🎯 Primul commit...
    git commit -m "🚀 Initial commit: MatchDay.ro - Complete website" -m "" -m "✨ Features:" -m "- SEO optimized blog system" -m "- Interactive polls system" -m "- Comments with moderation" -m "- Editorial calendar" -m "- Admin dashboard" -m "- Mobile responsive design" -m "" -m "👤 Author: David Nyikora" -m "📧 Contact: contact@matchday.ro" -m "🌐 Website: https://matchday.ro"
    echo ✅ Primul commit realizat!
) else (
    echo 🔄 Commit cu modificările recente...
    git commit -m "📝 Update: Latest changes to MatchDay.ro" -m "" -m "- Updated author name to David Nyikora" -m "- Added contact information" -m "- Configured GitHub Actions deployment" -m "- Improved project structure"
    echo ✅ Commit realizat!
)

echo.
echo 🎉 Git configurat cu succes!
echo.
echo 📋 Următorii pași:
echo 1. Creează un repository pe GitHub
echo 2. Copiază URL-ul repository-ului
echo 3. Rulează: git remote add origin ^<URL_GITHUB^>
echo 4. Rulează: git branch -M main
echo 5. Rulează: git push -u origin main
echo.
echo 🔐 Nu uita să configurezi secrets în GitHub Actions!
echo 📄 Vezi DEPLOYMENT-CONFIG.md pentru detalii
echo.
pause
