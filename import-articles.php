<?php
/**
 * Import articole - Clasamente și UCL
 * Rulează o singură dată apoi șterge acest fișier
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');

if (empty($_SESSION['david_logged'])) {
    die('Trebuie să fii logat în admin pentru a rula acest script.');
}

$articles = [
    // ARTICOL 1 - Clasament Liga 1
    [
        'title' => 'Clasament Liga 1 (SuperLiga) 2025–2026 – actualizat azi',
        'slug' => 'clasament-liga-1-superliga-2025-2026',
        'excerpt' => 'Clasamentul SuperLigii pentru sezonul 2025–2026 este actualizat în timp real. Pozițiile complete pentru Grupa A și Grupa B, cu puncte, golaveraj și forma ultimelor 5 meciuri.',
        'category_slug' => 'statistici',
        'tags' => 'liga-1,clasament,superliga,romania,fotbal',
        'status' => 'published',
        'content' => '
<p>Clasamentul SuperLigii pentru sezonul 2025–2026 este actualizat în timp real. Mai jos găsești pozițiile complete pentru Grupa A și Grupa B, cu puncte, golaveraj și forma ultimelor 5 meciuri.</p>

<h2>🏆 Grupa A – Clasament complet</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr>
<th>Loc</th><th>Echipă</th><th>MJ</th><th>V</th><th>E</th><th>I</th><th>GM</th><th>GP</th><th>DG</th><th>Pct</th>
</tr>
</thead>
<tbody>
<tr><td><strong>1</strong></td><td>🟡 Universitatea Craiova</td><td>2</td><td>1</td><td>0</td><td>1</td><td>1</td><td>1</td><td>0</td><td><strong>33</strong></td></tr>
<tr><td><strong>2</strong></td><td>🔴 Rapid București</td><td>1</td><td>1</td><td>0</td><td>0</td><td>2</td><td>1</td><td>+1</td><td><strong>31</strong></td></tr>
<tr><td><strong>3</strong></td><td>⚪ „U" Cluj</td><td>1</td><td>1</td><td>0</td><td>0</td><td>2</td><td>1</td><td>+1</td><td><strong>30</strong></td></tr>
<tr><td><strong>4</strong></td><td>🟣 Argeș Pitești</td><td>1</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>+1</td><td><strong>28</strong></td></tr>
<tr><td><strong>5</strong></td><td>🔴 CFR Cluj</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>2</td><td>-2</td><td><strong>27</strong></td></tr>
<tr><td><strong>6</strong></td><td>🔴⚪ Dinamo București</td><td>2</td><td>0</td><td>0</td><td>2</td><td>2</td><td>4</td><td>-2</td><td><strong>26</strong></td></tr>
</tbody>
</table>
</div>

<h2>🏆 Grupa B – Clasament complet</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr>
<th>Loc</th><th>Echipă</th><th>MJ</th><th>V</th><th>E</th><th>I</th><th>GM</th><th>GP</th><th>DG</th><th>Pct</th>
</tr>
</thead>
<tbody>
<tr><td><strong>1</strong></td><td>🔴⚪ UTA Arad</td><td>1</td><td>1</td><td>0</td><td>0</td><td>3</td><td>2</td><td>+1</td><td><strong>25</strong></td></tr>
<tr><td><strong>2</strong></td><td>🔴🔵 Botoșani</td><td>1</td><td>1</td><td>0</td><td>0</td><td>2</td><td>1</td><td>+1</td><td><strong>24</strong></td></tr>
<tr><td><strong>3</strong></td><td>🔵🔴 FCSB</td><td>1</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>+1</td><td><strong>24</strong></td></tr>
<tr><td><strong>4</strong></td><td>🔵 Farul Constanța</td><td>1</td><td>1</td><td>0</td><td>0</td><td>2</td><td>1</td><td>+1</td><td><strong>22</strong></td></tr>
<tr><td><strong>5</strong></td><td>🟠 Oțelul Galați</td><td>1</td><td>0</td><td>0</td><td>1</td><td>1</td><td>2</td><td>-1</td><td><strong>20</strong></td></tr>
<tr><td><strong>6</strong></td><td>🟢 Csikszereda</td><td>1</td><td>0</td><td>0</td><td>1</td><td>1</td><td>2</td><td>-1</td><td><strong>19</strong></td></tr>
<tr><td><strong>7</strong></td><td>🟡 Petrolul Ploiești</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>1</td><td>-1</td><td><strong>18</strong></td></tr>
<tr><td><strong>8</strong></td><td>⚪ Unirea Slobozia</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>2</td><td>-2</td><td><strong>13</strong></td></tr>
<tr><td><strong>9</strong></td><td>🔴 Hermannstadt</td><td>1</td><td>0</td><td>0</td><td>1</td><td>1</td><td>3</td><td>-2</td><td><strong>12</strong></td></tr>
<tr><td><strong>10</strong></td><td>🔵 Metaloglobus</td><td>1</td><td>0</td><td>0</td><td>1</td><td>0</td><td>2</td><td>-2</td><td><strong>7</strong></td></tr>
</tbody>
</table>
</div>

<h2>📊 Analiza etapei</h2>

<p><strong>Grupa A</strong> este extrem de echilibrată, cu <strong>Universitatea Craiova</strong> pe primul loc, dar cu <strong>Rapid</strong> și <strong>U Cluj</strong> foarte aproape. Lupta pentru play-off se anunță incendiară!</p>

<p>În <strong>Grupa B</strong>, <strong>UTA Arad</strong> începe puternic sezonul, în timp ce <strong>FCSB</strong> și <strong>Botoșani</strong> sunt la egalitate de puncte. Metaloglobus ocupă ultima poziție cu doar 7 puncte.</p>

<div class="alert alert-info">
<strong>📅 Actualizare:</strong> Clasamentul este actualizat după fiecare etapă. Ultima actualizare: 20 martie 2026.
</div>
'
    ],
    
    // ARTICOL 2 - Top marcatori Liga 1
    [
        'title' => 'Top marcatori Liga 1 (SuperLiga) 2025–2026 – actualizat azi',
        'slug' => 'top-marcatori-liga-1-superliga-2025-2026',
        'excerpt' => 'Clasamentul golgheterilor din SuperLiga pentru sezonul 2025–2026, actualizat după ultimele meciuri. Cine conduce cursa pentru Gheata de Aur?',
        'category_slug' => 'statistici',
        'tags' => 'liga-1,top-marcatori,goluri,superliga,golgeteri',
        'status' => 'published',
        'content' => '
<p>Clasamentul golgheterilor din SuperLiga pentru sezonul 2025–2026, actualizat după ultimele meciuri.</p>

<h2>🥇 Top marcatori Liga 1</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr>
<th>Loc</th><th>Jucător</th><th>Echipă</th><th>Goluri</th>
</tr>
</thead>
<tbody>
<tr class="table-warning"><td>🥇 <strong>1</strong></td><td><strong>Jovo Lukić</strong></td><td>U Cluj</td><td><strong>16</strong> ⚽</td></tr>
<tr class="table-secondary"><td>🥈 <strong>2</strong></td><td><strong>Alex Dobre</strong></td><td>Rapid București</td><td><strong>15</strong> ⚽</td></tr>
<tr class="table-dark"><td>🥉 <strong>3</strong></td><td>Andrei Cordea</td><td>CFR Cluj</td><td><strong>11</strong> ⚽</td></tr>
<tr class="table-dark"><td>🥉 <strong>3</strong></td><td>Florin Tănase</td><td>FCSB</td><td><strong>11</strong> ⚽</td></tr>
<tr><td><strong>5</strong></td><td>Alexandru Ișfan</td><td>Farul Constanța</td><td><strong>10</strong> ⚽</td></tr>
</tbody>
</table>
</div>

<h2>📈 Analiză</h2>

<p><strong>Jovo Lukić</strong> domină clasamentul cu <strong>16 goluri</strong>, fiind într-o formă excelentă pentru U Cluj. Sârbul are un avans de un gol față de urmăritorul său, Alex Dobre de la Rapid București.</p>

<p><strong>Alex Dobre</strong> îl urmărește îndeaproape cu 15 goluri, iar pentru locul 3 avem o luptă strânsă între <strong>Andrei Cordea</strong> (CFR Cluj) și <strong>Florin Tănase</strong> (FCSB), ambii cu câte 11 goluri.</p>

<div class="card bg-light mb-4">
<div class="card-body">
<h5 class="card-title">🎯 Cursa pentru Gheata de Aur</h5>
<p class="card-text">Cu încă 10 etape rămase, diferența de doar 6 goluri între primul și al 5-lea clasat face competiția extrem de imprevizibilă!</p>
</div>
</div>

<h2>🔗 Vezi și</h2>
<ul>
<li><a href="/post.php?slug=clasament-liga-1-superliga-2025-2026">Clasament Liga 1 – actualizat</a></li>
<li>Program Liga 1 – etapa următoare</li>
</ul>

<div class="alert alert-info">
<strong>📅 Actualizare:</strong> Clasamentul golgheterilor este actualizat după fiecare etapă. Ultima actualizare: 20 martie 2026.
</div>
'
    ],
    
    // ARTICOL 3 - Champions League Optimi + Sferturi
    [
        'title' => 'Champions League 2025–2026 – Rezultate optimi și program sferturi',
        'slug' => 'champions-league-2025-2026-rezultate-optimi-program-sferturi',
        'excerpt' => 'Rezultatele complete din optimile Champions League și programul oficial al sferturilor de finală. Barcelona și Bayern au dominat!',
        'category_slug' => 'champions-league',
        'tags' => 'ucl,champions-league,optimi,sferturi,rezultate,program',
        'status' => 'published',
        'content' => '
<p>Rezultatele complete din optimile Champions League și programul oficial al sferturilor de finală.</p>

<h2>⚽ Rezultate optimi – manșa a doua</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr><th>Meci</th><th>Scor</th><th>Scor general</th><th>Data</th></tr>
</thead>
<tbody>
<tr><td>🦁 Sporting – Bodø/Glimt</td><td><strong>5–0</strong></td><td>5–3 ✅</td><td>17 martie</td></tr>
<tr><td>🔵 Chelsea – PSG 🔴</td><td>0–3</td><td>2–8 ❌</td><td>17 martie</td></tr>
<tr><td>🔵 Man City – Real Madrid ⚪</td><td>1–2</td><td>1–5 ❌</td><td>17 martie</td></tr>
<tr><td>🔴 Arsenal – Bayer 🔴</td><td><strong>2–0</strong></td><td>3–1 ✅</td><td>17 martie</td></tr>
<tr class="table-success"><td>🔵🔴 Barcelona – Newcastle ⚫</td><td><strong>7–2</strong></td><td><strong>8–3</strong> ✅</td><td>18 martie</td></tr>
<tr><td>⚪ Tottenham – Atlético 🔴</td><td>3–2</td><td>5–7 ❌</td><td>18 martie</td></tr>
<tr><td>🔴 Liverpool – Galatasaray 🟡</td><td><strong>4–0</strong></td><td>4–1 ✅</td><td>18 martie</td></tr>
<tr class="table-success"><td>🔴 Bayern – Atalanta 🔵</td><td><strong>4–1</strong></td><td><strong>10–2</strong> ✅</td><td>18 martie</td></tr>
</tbody>
</table>
</div>

<div class="alert alert-success">
<strong>🏆 Echipele calificate în sferturi:</strong> Real Madrid, PSG, Arsenal, Sporting, Barcelona, Atlético Madrid, Liverpool, Bayern München
</div>

<h2>📅 Program sferturi – tur</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr><th>Meci</th><th>Data</th><th>Ora</th></tr>
</thead>
<tbody>
<tr class="table-warning"><td>⚪ <strong>Real Madrid</strong> – <strong>Bayern</strong> 🔴</td><td>07 aprilie</td><td>22:00</td></tr>
<tr><td>🦁 Sporting – Arsenal 🔴</td><td>07 aprilie</td><td>22:00</td></tr>
<tr class="table-warning"><td>🔵🔴 <strong>Barcelona</strong> – <strong>Atlético Madrid</strong> 🔴</td><td>08 aprilie</td><td>22:00</td></tr>
<tr class="table-warning"><td>🔴 <strong>PSG</strong> – <strong>Liverpool</strong> 🔴</td><td>08 aprilie</td><td>22:00</td></tr>
</tbody>
</table>
</div>

<h2>📊 Analiză</h2>

<p><strong>Barcelona</strong> și <strong>Bayern</strong> au obținut cele mai clare calificări din optimi:</p>

<ul>
<li>🔵🔴 <strong>Barcelona 8–3 Newcastle</strong> – Catalanii au oferit un spectacol ofensiv incredibil, cu 7 goluri în retur!</li>
<li>🔴 <strong>Bayern 10–2 Atalanta</strong> – Bavarezii au zdrobit echipa italiană pe cele două manșe</li>
</ul>

<p>Cele mai așteptate dueluri din sferturi:</p>

<div class="row">
<div class="col-md-6 mb-3">
<div class="card border-warning">
<div class="card-body">
<h5 class="card-title">⚪🔴 Real Madrid vs Bayern</h5>
<p class="card-text">El Clásico al Champions League! Două dintre cele mai titrate echipe din istorie se întâlnesc din nou.</p>
</div>
</div>
</div>
<div class="col-md-6 mb-3">
<div class="card border-danger">
<div class="card-body">
<h5 class="card-title">🔴🔴 PSG vs Liverpool</h5>
<p class="card-text">Duel incendiar între doi favoriți la trofeu! Mbappé vs Salah?</p>
</div>
</div>
</div>
</div>

<h2>🔗 Vezi și</h2>
<ul>
<li><a href="/post.php?slug=clasament-marcatori-ucl-2026">Clasament UCL – actualizat</a></li>
<li><a href="/post.php?slug=tragere-sorti-sferturi-ucl-2026">Top marcatori UCL</a></li>
</ul>

<div class="alert alert-info">
<strong>📺 Unde vezi meciurile:</strong> Toate partidele din sferturile Champions League sunt transmise pe Digi Sport și Prima Sport.
</div>
'
    ],
    
    // ARTICOL 4 - Clasament Champions League
    [
        'title' => 'Clasament Champions League 2025–2026 – actualizat după etapa 8',
        'slug' => 'clasament-champions-league-2025-2026',
        'excerpt' => 'Clasamentul actual al Champions League pentru sezonul 2025–2026. Arsenal domină competiția cu un parcurs perfect, Bayern și Liverpool în apropiere.',
        'category_slug' => 'champions-league',
        'tags' => 'ucl,champions-league,clasament,arsenal,bayern,liverpool',
        'status' => 'published',
        'content' => '
<p>Clasamentul actual al Champions League pentru sezonul 2025–2026 arată o luptă extrem de strânsă în primele poziții. <strong>Arsenal</strong> domină competiția cu un parcurs perfect, în timp ce Bayern, Liverpool și Barcelona rămân în apropiere. Mai jos găsești clasamentul complet, actualizat după etapa a 8‑a.</p>

<h2>🏆 Clasament UCL – Etapa 8</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr>
<th>Loc</th><th>Club</th><th>MJ</th><th>V</th><th>E</th><th>I</th><th>GM</th><th>GP</th><th>DG</th><th>Pct</th>
</tr>
</thead>
<tbody>
<tr class="table-success"><td>🥇 <strong>1</strong></td><td>🔴 <strong>Arsenal</strong></td><td>8</td><td>8</td><td>0</td><td>0</td><td>23</td><td>4</td><td>+19</td><td><strong>24</strong></td></tr>
<tr class="table-success"><td>🥈 <strong>2</strong></td><td>🔴 <strong>Bayern</strong></td><td>8</td><td>7</td><td>0</td><td>1</td><td>22</td><td>8</td><td>+14</td><td><strong>21</strong></td></tr>
<tr class="table-success"><td>🥉 <strong>3</strong></td><td>🔴 <strong>Liverpool</strong></td><td>8</td><td>6</td><td>0</td><td>2</td><td>20</td><td>8</td><td>+12</td><td><strong>18</strong></td></tr>
<tr class="table-success"><td><strong>4</strong></td><td>⚪ Tottenham</td><td>8</td><td>5</td><td>2</td><td>1</td><td>17</td><td>10</td><td>+7</td><td><strong>17</strong></td></tr>
<tr class="table-success"><td><strong>5</strong></td><td>🔵🔴 Barcelona</td><td>8</td><td>5</td><td>1</td><td>2</td><td>22</td><td>14</td><td>+8</td><td><strong>16</strong></td></tr>
<tr class="table-success"><td><strong>6</strong></td><td>🔵 Chelsea</td><td>8</td><td>5</td><td>1</td><td>2</td><td>17</td><td>10</td><td>+7</td><td><strong>16</strong></td></tr>
<tr class="table-success"><td><strong>7</strong></td><td>🦁 Sporting</td><td>8</td><td>5</td><td>1</td><td>2</td><td>17</td><td>11</td><td>+6</td><td><strong>16</strong></td></tr>
<tr class="table-success"><td><strong>8</strong></td><td>🔵 Man City</td><td>8</td><td>5</td><td>1</td><td>2</td><td>16</td><td>10</td><td>+6</td><td><strong>16</strong></td></tr>
<tr class="table-warning"><td><strong>9</strong></td><td>⚪ Real Madrid</td><td>8</td><td>5</td><td>0</td><td>3</td><td>19</td><td>15</td><td>+4</td><td><strong>15</strong></td></tr>
<tr class="table-warning"><td><strong>10</strong></td><td>🔵⚫ Inter</td><td>8</td><td>5</td><td>0</td><td>3</td><td>18</td><td>13</td><td>+5</td><td><strong>15</strong></td></tr>
<tr class="table-warning"><td><strong>11</strong></td><td>🔴🔵 PSG</td><td>8</td><td>4</td><td>2</td><td>2</td><td>21</td><td>11</td><td>+10</td><td><strong>14</strong></td></tr>
<tr class="table-warning"><td><strong>12</strong></td><td>⚫⚪ Newcastle</td><td>8</td><td>4</td><td>1</td><td>3</td><td>17</td><td>10</td><td>+7</td><td><strong>13</strong></td></tr>
<tr class="table-secondary"><td><strong>13</strong></td><td>⚪⚫ Juventus</td><td>8</td><td>3</td><td>1</td><td>4</td><td>14</td><td>16</td><td>-2</td><td><strong>10</strong></td></tr>
</tbody>
</table>
</div>

<div class="row mb-4">
<div class="col-md-4">
<div class="alert alert-success mb-0">
<strong>🟢 Locurile 1-8:</strong> Calificare directă în optimi
</div>
</div>
<div class="col-md-4">
<div class="alert alert-warning mb-0">
<strong>🟡 Locurile 9-24:</strong> Play-off pentru optimi
</div>
</div>
<div class="col-md-4">
<div class="alert alert-secondary mb-0">
<strong>⚫ Locurile 25+:</strong> Eliminare din competiție
</div>
</div>
</div>

<h2>📊 Analiză</h2>

<p><strong>Arsenal</strong> continuă sezonul PERFECT, cu <strong>8 victorii din 8 meciuri</strong> și cel mai bun atac al competiției (23 goluri marcate, doar 4 primite). Este singura echipă fără înfrângere și fără egal!</p>

<p><strong>Bayern München</strong> rămâne principalul urmăritor cu 21 de puncte, având o singură înfrângere în 8 meciuri. <strong>Liverpool</strong> își păstrează poziția în top 3 cu 18 puncte.</p>

<div class="card bg-light mb-4">
<div class="card-body">
<h5 class="card-title">🏴󠁧󠁢󠁥󠁮󠁧󠁿 Dominația engleză</h5>
<p class="card-text">6 echipe engleze în top 12: Arsenal, Liverpool, Tottenham, Chelsea, Man City și Newcastle. Premier League domină Europa!</p>
</div>
</div>

<p><strong>Barcelona</strong> și <strong>Real Madrid</strong> sunt în zona de calificare, dar presiunea este mare din partea echipelor engleze. Madrilenii au suferit 3 înfrângeri surprinzătoare.</p>

<h2>🔗 Vezi și</h2>
<ul>
<li><a href="/post.php?slug=top-marcatori-champions-league-2025-2026">Top marcatori UCL – actualizat</a></li>
<li><a href="/post.php?slug=champions-league-2025-2026-rezultate-optimi-program-sferturi">Rezultate optimi + Program sferturi</a></li>
</ul>

<div class="alert alert-info">
<strong>📅 Actualizare:</strong> Clasamentul este actualizat după fiecare etapă UCL. Ultima actualizare: 20 martie 2026.
</div>
'
    ],
    
    // ARTICOL 5 - Top marcatori Champions League
    [
        'title' => 'Top marcatori Champions League 2025–2026 – clasament actualizat',
        'slug' => 'top-marcatori-champions-league-2025-2026',
        'excerpt' => 'Lista golgheterilor Champions League pentru sezonul 2025–2026 este dominată de Kylian Mbappé, aflat într-o formă extraordinară.',
        'category_slug' => 'champions-league',
        'tags' => 'ucl,champions-league,top-marcatori,mbappe,kane,haaland',
        'status' => 'published',
        'content' => '
<p>Lista golgheterilor Champions League pentru sezonul 2025–2026 este dominată de <strong>Kylian Mbappé</strong>, aflat într-o formă extraordinară. Mai jos găsești topul complet, actualizat după etapa a 8‑a.</p>

<h2>🥇 Top marcatori UCL</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead class="table-dark">
<tr>
<th>Loc</th><th>Jucător</th><th>Echipă</th><th>Goluri</th>
</tr>
</thead>
<tbody>
<tr class="table-warning"><td>🥇 <strong>1</strong></td><td><strong>Kylian Mbappé</strong> 🇫🇷</td><td>⚪ Real Madrid</td><td><strong>13</strong> ⚽</td></tr>
<tr class="table-secondary"><td>🥈 <strong>2</strong></td><td><strong>Anthony Gordon</strong> 🏴󠁧󠁢󠁥󠁮󠁧󠁿</td><td>⚫⚪ Newcastle United</td><td><strong>10</strong> ⚽</td></tr>
<tr class="table-secondary"><td>🥈 <strong>2</strong></td><td><strong>Harry Kane</strong> 🏴󠁧󠁢󠁥󠁮󠁧󠁿</td><td>🔴 Bayern München</td><td><strong>10</strong> ⚽</td></tr>
<tr class="table-dark"><td>🥉 <strong>4</strong></td><td>Erling Haaland 🇳🇴</td><td>🔵 Manchester City</td><td><strong>8</strong> ⚽</td></tr>
<tr class="table-dark"><td>🥉 <strong>4</strong></td><td>Julián Álvarez 🇦🇷</td><td>🔴⚪ Atlético Madrid</td><td><strong>8</strong> ⚽</td></tr>
</tbody>
</table>
</div>

<h2>📈 Analiză</h2>

<p><strong>Kylian Mbappé</strong> conduce detașat topul marcatorilor cu <strong>13 goluri în 8 meciuri</strong>, fiind într-o formă incredibilă în primul său sezon la Real Madrid! Francezul are o medie de 1.6 goluri pe meci în UCL.</p>

<div class="card border-warning mb-4">
<div class="card-body">
<h5 class="card-title">🌟 Mbappé în cifre</h5>
<div class="row text-center">
<div class="col-4">
<h3 class="text-warning">13</h3>
<small>Goluri UCL</small>
</div>
<div class="col-4">
<h3 class="text-warning">1.6</h3>
<small>Goluri/meci</small>
</div>
<div class="col-4">
<h3 class="text-warning">8</h3>
<small>Meciuri</small>
</div>
</div>
</div>
</div>

<p><strong>Surpriza sezonului:</strong> <strong>Anthony Gordon</strong> de la Newcastle, cu 10 goluri la egalitate cu Harry Kane! Englezul a explodat în acest sezon și atrage atenția marilor cluburi.</p>

<p><strong>Harry Kane</strong> confirmă statutul de golgheter la Bayern München, cu 10 goluri în grupele UCL. Germanul și Newcastle au peste 30% goluri în competiție!</p>

<p><strong>Erling Haaland</strong> și <strong>Julián Álvarez</strong> completează topul cu câte 8 goluri, menținând Anglia în prim‑planul competiției cu 4 jucători în top 5!</p>

<div class="alert alert-primary">
<strong>🏴󠁧󠁢󠁥󠁮󠁧󠁿 Fun fact:</strong> 3 din top 5 marcatori joacă în Premier League sau pentru echipe engleze (Gordon, Kane la Bayern, Haaland)!
</div>

<h2>🔗 Vezi și</h2>
<ul>
<li><a href="/post.php?slug=clasament-champions-league-2025-2026">Clasament UCL – actualizat</a></li>
<li><a href="/post.php?slug=champions-league-2025-2026-rezultate-optimi-program-sferturi">Rezultate optimi + Program sferturi</a></li>
</ul>

<div class="alert alert-info">
<strong>📅 Actualizare:</strong> Clasamentul golgheterilor este actualizat după fiecare etapă UCL. Ultima actualizare: 20 martie 2026.
</div>
'
    ]
];

$imported = 0;
$errors = [];

foreach ($articles as $article) {
    // Check if article already exists
    $existing = Post::getBySlug($article['slug']);
    if ($existing) {
        $errors[] = "Articolul '{$article['title']}' există deja (slug: {$article['slug']})";
        continue;
    }
    
    try {
        $id = Post::create([
            'title' => $article['title'],
            'slug' => $article['slug'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'category_slug' => $article['category_slug'],
            'tags' => $article['tags'],
            'status' => $article['status'],
            'published_at' => date('Y-m-d H:i:s'),
            'cover_image' => '' // Add cover later
        ]);
        
        if ($id) {
            $imported++;
            echo "✅ Importat: {$article['title']} (ID: $id)<br>";
        }
    } catch (Exception $e) {
        $errors[] = "Eroare la '{$article['title']}': " . $e->getMessage();
    }
}

echo "<br><hr><br>";
echo "<h3>Rezultat import:</h3>";
echo "<p><strong>$imported articole importate cu succes!</strong></p>";

if (!empty($errors)) {
    echo "<h4>Erori:</h4><ul>";
    foreach ($errors as $error) {
        echo "<li style='color:orange;'>$error</li>";
    }
    echo "</ul>";
}

echo "<br><p><a href='/admin/posts.php' class='btn btn-primary'>Vezi articolele</a></p>";
echo "<p style='color:red;'><strong>⚠️ ȘTERGE ACEST FIȘIER DUPĂ IMPORT!</strong></p>";
?>
