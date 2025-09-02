# 🔧 DEBUGGING SONDAJE PE HOSTICO

## Problemă Identificată
Sondajele create în admin nu se afișează pe pagina principală a siteului public pe serverul Hostico.

## Pași de Debugging

### 1. Verificare Directoare și Permisiuni
Accesați: `https://matchday.ro/debug-polls.php`

Acest script va verifica:
- ✅ Dacă directorul `data/polls/` există și are permisiuni de scriere
- ✅ Lista tuturor sondajelor existente
- ✅ Test de creare a unui sondaj
- ✅ Testarea API-ului polls

### 2. Test Interactiv Creare Sondaje
Accesați: `https://matchday.ro/test-polls-hostico.html`

Acest tool interactiv permite:
- 🎯 Testarea creării unui sondaj prin AJAX (ca în admin)
- 📋 Verificarea încărcării sondajelor active
- 🔍 Rularea scriptului de debug

### 3. Verificare Error Logs
Pe Hostico, verificați error log-urile PHP pentru mesajele de debug adăugate:
```
CreatePoll called with data: {...}
Polls directory: /path/to/data/polls
Directory exists: YES/NO
Directory writable: YES/NO
Attempting to save poll to: /path/to/file.json
Poll saved successfully. Bytes written: X
```

### 4. Probleme Posibile și Soluții

#### A. Permisiuni de Directoare
```bash
# Verificați dacă directorul data/polls există
ls -la data/

# Setați permisiuni corecte
chmod 755 data/
chmod 755 data/polls/
chmod 644 data/polls/*.json
```

#### B. Probleme cu file_put_contents()
- Verificați dacă serverul permite scrirea fișierelor
- Verificați dacă există suficient spațiu pe disk
- Verificați dacă nu există restricții de securitate

#### C. Probleme cu JSON encoding
- Verificați dacă PHP are suport pentru JSON
- Verificați caracterele speciale în întrebări/opțiuni

#### D. Cache Issues
- API-ul folosește cache - verificați dacă se invalidează corect
- Încercați să ștergeți cache-ul manual din `data/cache/`

### 5. Fix-uri Rapide

#### Fix 1: Recreare Directoare cu Permisiuni Corecte
```php
// Adăugați în debug-polls.php sau polls-actions.php
$dataDir = __DIR__ . '/data';
$pollsDir = $dataDir . '/polls';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}
if (!is_dir($pollsDir)) {
    mkdir($pollsDir, 0755, true);
}

// Verificare permisiuni
chmod($dataDir, 0755);
chmod($pollsDir, 0755);
```

#### Fix 2: Verificare și Clear Cache
```php
// În polls-actions.php după salvarea cu succes
if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
    $cacheDir = __DIR__ . '/../data/cache';
    if (is_dir($cacheDir)) {
        array_map('unlink', glob("$cacheDir/*"));
    }
}
```

#### Fix 3: Fallback pentru file_put_contents
```php
// Înlocuiește file_put_contents cu verificări suplimentare
$tempFile = $pollFile . '.tmp';
if (file_put_contents($tempFile, $jsonData, LOCK_EX) !== false) {
    if (rename($tempFile, $pollFile)) {
        // Success
    } else {
        unlink($tempFile);
        throw new Exception('Eroare la redenumirea fișierului');
    }
} else {
    throw new Exception('Eroare la scrierea fișierului temporar');
}
```

### 6. Informații de Contact pentru Debugging
- Script debug principal: `/debug-polls.php`
- Tool test interactiv: `/test-polls-hostico.html`
- API endpoint: `/polls_api.php?action=get_active_polls`
- Admin polls actions: `/admin/polls-actions.php`

### 7. Verificare Finală
După aplicarea fix-urilor:
1. Accesați admin și creați un sondaj nou
2. Verificați dacă fișierul .json apare în `data/polls/`
3. Testați API-ul: `polls_api.php?action=get_active_polls`
4. Verificați dacă sondajul apare pe pagina principală

---
**Notă**: Această documentație a fost generată pentru debugging-ul problemei cu sondajele pe serverul Hostico.
