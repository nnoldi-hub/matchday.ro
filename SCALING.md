# MatchDay.ro - Ghid Scalare

Acest document descrie strategia de scalare pentru MatchDay.ro pe măsură ce traficul crește.

## Niveluri de Scalare

| Nivel | Vizitatori/zi | Acțiuni Necesare |
|-------|---------------|------------------|
| **Startup** | 500-1,000 | Shared hosting OK |
| **Growth** | 5,000-10,000 | VPS + caching |
| **Scale** | 50,000+ | Cloud + CDN |
| **Enterprise** | 100,000+ | Cluster + microservices |

---

## Nivel Actual: Startup (✓ Implementat)

### Ce avem acum
- ✅ File-based cache (`data/cache/`)
- ✅ Database optimization (indexes, prepared statements)
- ✅ Image optimization guidelines
- ✅ Gzip compression via .htaccess
- ✅ Health monitoring (`/health.php`)
- ✅ Error logging și alerting

### Verificări Periodice
```bash
# Verifică dimensiunea cache
du -sh data/cache/

# Verifică loguri
wc -l data/logs/error-*.log

# Test health endpoint
curl -s https://matchday.ro/health.php | jq
```

---

## Nivel Growth: 5-10K Vizitatori/zi

### 1. Upgrade Infrastructure

**VPS Recomandat:**
- 2-4 GB RAM
- 2 vCPU
- SSD storage
- Providers: DigitalOcean, Hetzner, Vultr

**MySQL Dedicated:**
```ini
# my.cnf optimizations
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
query_cache_type = 1
max_connections = 100
```

### 2. OPcache Configuration

```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 3. CDN pentru Static Assets (Cloudflare)

**Setup:**
1. Adaugă domeniul în Cloudflare
2. Schimbă nameservers
3. Activează:
   - Auto Minify (JS, CSS, HTML)
   - Brotli compression
   - Browser cache TTL: 1 month pentru assets
   - Always Online

**Page Rules:**
```
# Cache static assets
matchday.ro/assets/*
Cache Level: Cache Everything
Edge Cache TTL: 1 month

# Bypass cache for admin
matchday.ro/admin/*
Cache Level: Bypass
```

### 4. Redis pentru Sessions (Opțional)

```php
// config/session.php
if (extension_loaded('redis')) {
    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', 'tcp://127.0.0.1:6379');
}
```

---

## Nivel Scale: 50K+ Vizitatori/zi

### 1. Full-Page Cache (Varnish/Nginx)

**Nginx FastCGI Cache:**
```nginx
# nginx.conf
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=MATCHDAY:100m inactive=60m;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

server {
    location ~ \.php$ {
        fastcgi_cache MATCHDAY;
        fastcgi_cache_valid 200 60m;
        fastcgi_cache_bypass $cookie_session;
        add_header X-Cache-Status $upstream_cache_status;
    }
}
```

### 2. Database Read Replicas

```
┌─────────────────┐
│   Application   │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌───────┐ ┌───────┐
│Master │ │Replica│ ──── Read queries
│ (R/W) │ │ (R)   │
└───────┘ └───────┘
```

**PHP Implementation:**
```php
// config/database.php
class Database {
    private static $master;
    private static $replica;
    
    public static function getRead() {
        if (self::$replica === null) {
            self::$replica = new PDO(
                'mysql:host=replica.matchday.ro;dbname=matchday',
                DB_USER, DB_PASS
            );
        }
        return self::$replica;
    }
    
    public static function getWrite() {
        if (self::$master === null) {
            self::$master = new PDO(
                'mysql:host=master.matchday.ro;dbname=matchday',
                DB_USER, DB_PASS
            );
        }
        return self::$master;
    }
}
```

### 3. Queue pentru Background Jobs

**Beanstalkd sau Redis Queue:**
```php
// jobs/SendNewsletterJob.php
class SendNewsletterJob {
    public function handle($data) {
        $subscribers = NewsletterSubscriber::active()->get();
        foreach ($subscribers as $subscriber) {
            Email::send($subscriber->email, $data['subject'], $data['body']);
        }
    }
}

// Dispatch
Queue::push(new SendNewsletterJob([
    'subject' => 'Weekend în Liga 1',
    'body' => $htmlContent
]));
```

### 4. Elasticsearch pentru Search

```php
// includes/Search.php
class Search {
    private $client;
    
    public function __construct() {
        $this->client = Elasticsearch\ClientBuilder::create()
            ->setHosts(['localhost:9200'])
            ->build();
    }
    
    public function indexPost($post) {
        $this->client->index([
            'index' => 'posts',
            'id' => $post['id'],
            'body' => [
                'title' => $post['title'],
                'content' => strip_tags($post['content']),
                'category' => $post['category_name'],
                'created_at' => $post['created_at']
            ]
        ]);
    }
    
    public function search($query) {
        return $this->client->search([
            'index' => 'posts',
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['title^3', 'content']
                    ]
                ]
            ]
        ]);
    }
}
```

---

## Nivel Enterprise: 100K+ Vizitatori/zi

### 1. Container Architecture (Docker + Kubernetes)

```yaml
# docker-compose.yml
version: '3.8'
services:
  web:
    image: matchday/web:latest
    replicas: 3
    ports:
      - "80:80"
    depends_on:
      - mysql
      - redis
      
  mysql:
    image: mysql:8.0
    volumes:
      - mysql_data:/var/lib/mysql
      
  redis:
    image: redis:alpine
    
  worker:
    image: matchday/worker:latest
    replicas: 2
```

### 2. Microservices Split

```
┌──────────────────────────────────────────────────┐
│                   API Gateway                     │
│                  (nginx/kong)                     │
└────────────────────────┬─────────────────────────┘
                         │
     ┌───────────────────┼───────────────────┐
     │                   │                   │
     ▼                   ▼                   ▼
┌─────────┐       ┌─────────────┐      ┌─────────┐
│  Posts  │       │ Live Scores │      │  Users  │
│ Service │       │   Service   │      │ Service │
└─────────┘       └─────────────┘      └─────────┘
```

### 3. Global CDN (CloudFront/Fastly)

**Edge locations:**
- Europa de Est (România)
- Europa de Vest (Frankfurt)
- Asia (pentru diaspora)

**Cache Strategy:**
```
/                    → 5 minute TTL
/posts/*             → 15 minute TTL
/assets/*            → 1 year TTL (versioned)
/api/livescores      → 10 second TTL
/admin/*             → No cache
```

### 4. Real-time cu WebSockets

**Pentru Scoruri Live:**
```javascript
// client-side
const ws = new WebSocket('wss://live.matchday.ro/scores');

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    updateScore(data.matchId, data.homeScore, data.awayScore);
};

// server-side (Ratchet/Swoole)
class LiveScoresServer implements MessageComponentInterface {
    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }
}
```

---

## API Fallback Strategy

```php
<?php
// includes/LiveScores.php

class LiveScores {
    private static $providers = [
        'api-football' => 'ApiFootballProvider',
        'football-data' => 'FootballDataProvider',
        'manual' => 'ManualScoresProvider'
    ];
    
    public static function getLiveMatches() {
        foreach (self::$providers as $name => $class) {
            try {
                $provider = new $class();
                $data = $provider->getLiveMatches();
                
                // Cache successful response as backup
                Cache::set('live_matches_backup', $data, 300);
                
                Logger::api($name, '/matches/live', 200, count($data));
                return $data;
                
            } catch (ApiException $e) {
                Logger::api($name, '/matches/live', $e->getCode(), 0);
                continue;
            }
        }
        
        // All providers failed, use stale cache
        $backup = Cache::get('live_matches_backup');
        if ($backup) {
            Logger::api('cache', '/matches/live', 200, count($backup), ['stale' => true]);
            return $backup;
        }
        
        // Ultimate fallback
        return [];
    }
}
```

---

## Monitoring Checklist

### Daily
- [ ] Check error logs
- [ ] Verify health endpoint
- [ ] Monitor response times

### Weekly
- [ ] Review KPIs dashboard
- [ ] Check disk space
- [ ] Analyze slow queries

### Monthly
- [ ] Review infrastructure costs
- [ ] Analyze traffic trends
- [ ] Test backup restoration
- [ ] Security audit

---

## Cost Estimation

| Nivel | Componente | Cost/lună |
|-------|------------|-----------|
| Startup | Shared hosting | ~20 RON |
| Growth | VPS 4GB + CDN | ~100 RON |
| Scale | VPS cluster + services | ~500 RON |
| Enterprise | Cloud managed | ~2000+ RON |

---

## Acțiuni Imediate

### Pentru 5K vizitatori/zi:
1. [ ] Activează Cloudflare (gratuit)
2. [ ] Configurează OPcache
3. [ ] Optimizează imagini (WebP)
4. [ ] Setează cron pentru cache warmup
5. [ ] Monitorizare uptime (UptimeRobot)

### Pentru 10K+ vizitatori/zi:
1. [ ] Migrează pe VPS dedicat
2. [ ] Configurează MySQL optimizat
3. [ ] Implementează Redis pentru cache
4. [ ] Adaugă full-page cache

---

*Document actualizat: Ianuarie 2025*
