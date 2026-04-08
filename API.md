# MatchDay.ro API Documentation

## Base URL

```
https://matchday.ro/
```

## Authentication

API-urile publice nu necesită autentificare. 
Pentru acțiuni care modifică date (POST), este necesar un token CSRF valid.

### Obținere CSRF Token

Token-ul CSRF este disponibil în sesiune și trebuie inclus în fiecare request POST:

```php
<?php echo $_SESSION['csrf_token']; ?>
```

---

## Rate Limiting

| Endpoint | Limită | Fereastră |
|----------|--------|-----------|
| Comments API | 5 requests | 1 minut |
| Polls API | 10 requests | 1 minut |
| General API | 60 requests | 1 minut |

Response când limita e depășită:
```json
{
    "success": false,
    "error": "rate_limit_exceeded",
    "message": "Prea multe cereri. Încearcă din nou în X secunde.",
    "retry_after": 45
}
```

---

## Comments API

### Endpoint: `/comments_api.php`

### GET - Încarcă comentarii pentru un articol

**Request:**
```
GET /comments_api.php?post_id=123
GET /comments_api.php?post_id=123&sort=newest
GET /comments_api.php?post_id=123&sort=popular
```

**Parameters:**
| Param | Tip | Obligatoriu | Descriere |
|-------|-----|-------------|-----------|
| post_id | integer | Da | ID-ul articolului |
| sort | string | Nu | newest (default) sau popular |

**Response Success:**
```json
{
    "success": true,
    "comments": [
        {
            "id": 1,
            "author_name": "Mihai Popescu",
            "content": "Articol foarte bine documentat! Bravo!",
            "likes": 15,
            "created_at": "2025-01-15 14:30:00",
            "time_ago": "acum 2 ore",
            "is_liked": false,
            "replies": [
                {
                    "id": 2,
                    "author_name": "Ana Maria",
                    "content": "Sunt de acord!",
                    "likes": 3,
                    "created_at": "2025-01-15 15:00:00",
                    "time_ago": "acum 1 oră"
                }
            ]
        }
    ],
    "total": 5,
    "page": 1,
    "per_page": 20
}
```

---

### POST - Adaugă comentariu nou

**Request:**
```
POST /comments_api.php
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "author_name": "Ion Ionescu",
    "author_email": "ion@example.com",
    "content": "Comentariul meu aici...",
    "parent_id": null,
    "csrf_token": "abc123..."
}
```

**Parameters:**
| Param | Tip | Obligatoriu | Descriere |
|-------|-----|-------------|-----------|
| post_id | integer | Da | ID articol |
| author_name | string | Da | Nume autor (2-100 caractere) |
| author_email | string | Da | Email valid |
| content | string | Da | Conținut (10-2000 caractere) |
| parent_id | integer | Nu | ID comentariu părinte (pentru reply) |
| csrf_token | string | Da | Token CSRF valid |

**Response Success:**
```json
{
    "success": true,
    "message": "Comentariul a fost adăugat.",
    "comment": {
        "id": 25,
        "author_name": "Ion Ionescu",
        "content": "Comentariul meu aici...",
        "created_at": "2025-01-15 16:00:00"
    },
    "moderation": false
}
```

**Response Error:**
```json
{
    "success": false,
    "error": "validation_error",
    "message": "Conținutul trebuie să aibă minim 10 caractere."
}
```

---

### POST - Like comentariu

**Request:**
```
POST /comments_api.php
Content-Type: application/json
```

**Body:**
```json
{
    "action": "like",
    "comment_id": 1,
    "csrf_token": "abc123..."
}
```

**Response:**
```json
{
    "success": true,
    "likes": 16,
    "liked": true
}
```

---

## Polls API

### Endpoint: `/polls_api.php`

### GET - Obține sondaj

**Request:**
```
GET /polls_api.php?id=5
GET /polls_api.php?active=true
GET /polls_api.php?category=liga-1
```

**Response:**
```json
{
    "success": true,
    "poll": {
        "id": 5,
        "question": "Cine va câștiga titlul în 2025?",
        "options": [
            "CFR Cluj",
            "FCSB",
            "U Craiova",
            "Rapid București"
        ],
        "votes": [142, 98, 67, 45],
        "total_votes": 352,
        "percentages": [40.3, 27.8, 19.0, 12.8],
        "user_voted": false,
        "user_vote": null,
        "active": true,
        "expires_at": "2025-02-01 00:00:00"
    }
}
```

---

### POST - Votează în sondaj

**Request:**
```
POST /polls_api.php
Content-Type: application/json
```

**Body:**
```json
{
    "action": "vote",
    "poll_id": 5,
    "option_index": 1
}
```

**Parameters:**
| Param | Tip | Obligatoriu | Descriere |
|-------|-----|-------------|-----------|
| action | string | Da | "vote" |
| poll_id | integer | Da | ID-ul sondajului |
| option_index | integer | Da | Index opțiune (0-based) |

**Response Success:**
```json
{
    "success": true,
    "message": "Votul a fost înregistrat.",
    "votes": [142, 99, 67, 45],
    "total_votes": 353,
    "percentages": [40.2, 28.0, 19.0, 12.7]
}
```

**Response Error (deja votat):**
```json
{
    "success": false,
    "error": "already_voted",
    "message": "Ai votat deja în acest sondaj."
}
```

---

## Live Scores API

### Endpoint: `/livescores_api.php`

### GET - Meciuri active

**Request:**
```
GET /livescores_api.php
GET /livescores_api.php?status=live
GET /livescores_api.php?status=upcoming
GET /livescores_api.php?competition=liga-1
GET /livescores_api.php?date=2025-01-15
```

**Parameters:**
| Param | Tip | Descriere |
|-------|-----|-----------|
| status | string | live, upcoming, finished |
| competition | string | Slug competiție |
| date | string | Format YYYY-MM-DD |

**Response:**
```json
{
    "success": true,
    "matches": [
        {
            "id": 101,
            "competition": "Liga 1",
            "competition_logo": "/assets/images/competitions/liga1.png",
            "home_team": {
                "name": "CFR Cluj",
                "logo": "/assets/images/teams/cfr.png"
            },
            "away_team": {
                "name": "FCSB",
                "logo": "/assets/images/teams/fcsb.png"
            },
            "home_score": 2,
            "away_score": 1,
            "status": "LIVE",
            "minute": 67,
            "kickoff": "2025-01-15 20:00:00",
            "events": [
                {
                    "type": "goal",
                    "minute": 23,
                    "team": "home",
                    "player": "Deac"
                },
                {
                    "type": "goal",
                    "minute": 45,
                    "team": "away",
                    "player": "Coman"
                },
                {
                    "type": "goal",
                    "minute": 56,
                    "team": "home",
                    "player": "Petrila"
                }
            ]
        }
    ],
    "last_updated": "2025-01-15T20:45:00Z"
}
```

---

## RSS Feed

### Endpoint: `/rss.php`

### GET - Feed RSS

**Request:**
```
GET /rss.php
GET /rss.php?category=liga-1
GET /rss.php?limit=20
```

**Response:** XML în format RSS 2.0

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>MatchDay.ro</title>
        <link>https://matchday.ro</link>
        <description>Știri și analize despre fotbal</description>
        <language>ro</language>
        <lastBuildDate>Wed, 15 Jan 2025 12:00:00 +0200</lastBuildDate>
        <item>
            <title>CFR Cluj 2-1 FCSB: Analiza meciului</title>
            <link>https://matchday.ro/posts/cfr-cluj-fcsb-analiza</link>
            <description>...</description>
            <pubDate>Wed, 15 Jan 2025 10:00:00 +0200</pubDate>
            <guid>https://matchday.ro/posts/cfr-cluj-fcsb-analiza</guid>
            <category>Liga 1</category>
        </item>
    </channel>
</rss>
```

---

## Search Suggestions API

### Endpoint: `/search-suggestions.php`

### GET - Sugestii căutare

**Request:**
```
GET /search-suggestions.php?q=cfr
```

**Response:**
```json
{
    "success": true,
    "suggestions": [
        {
            "type": "post",
            "title": "CFR Cluj câștigă derby-ul cu FCSB",
            "url": "/posts/cfr-cluj-castiga-derby"
        },
        {
            "type": "category",
            "title": "CFR Cluj",
            "url": "/category/cfr-cluj"
        },
        {
            "type": "tag",
            "title": "#CFR",
            "url": "/search.php?tag=cfr"
        }
    ],
    "query": "cfr"
}
```

---

## Error Codes

| Cod | Descriere |
|-----|-----------|
| validation_error | Date de intrare invalide |
| csrf_invalid | Token CSRF invalid sau expirat |
| not_found | Resursa nu a fost găsită |
| rate_limit_exceeded | Prea multe cereri |
| already_voted | Utilizatorul a votat deja |
| comment_disabled | Comentariile sunt dezactivate |
| unauthorized | Acțiune neautorizată |
| server_error | Eroare internă server |

---

## Exemple Cod

### JavaScript (Fetch API)

```javascript
// Încarcă comentarii
async function loadComments(postId) {
    const response = await fetch(`/comments_api.php?post_id=${postId}`);
    const data = await response.json();
    
    if (data.success) {
        return data.comments;
    } else {
        throw new Error(data.message);
    }
}

// Adaugă comentariu
async function addComment(postId, name, email, content, csrfToken) {
    const response = await fetch('/comments_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            post_id: postId,
            author_name: name,
            author_email: email,
            content: content,
            csrf_token: csrfToken
        })
    });
    
    return await response.json();
}

// Votează în sondaj
async function votePoll(pollId, optionIndex) {
    const response = await fetch('/polls_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'vote',
            poll_id: pollId,
            option_index: optionIndex
        })
    });
    
    return await response.json();
}
```

### PHP (cURL)

```php
// Obține scoruri live
function getLiveScores() {
    $ch = curl_init('https://matchday.ro/livescores_api.php?status=live');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

---

*Documentație actualizată: Ianuarie 2025*
