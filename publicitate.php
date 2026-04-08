<?php
/**
 * Advertise with Us Page
 * MatchDay.ro - Sponsorship & Advertising Information
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

$pageTitle = 'Publicitate pe MatchDay.ro';
$pageDescription = 'Promovează-ți brandul pe cea mai pasionată platformă de fotbal din România. Pachete de sponsorizare, bannere și articole sponsorizate.';

// Get some stats for the page
$db = Database::getInstance();
$totalPosts = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")->fetch()['count'] ?? 0;
$totalViews = $db->query("SELECT SUM(views) as total FROM posts")->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= SITE_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>/publicitate.php">
    
    <!-- Bootstrap CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Main CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    
    <style>
        .advertise-hero {
            background: linear-gradient(135deg, #D32F2F 0%, #B71C1C 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 1rem;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .package-card {
            border: 2px solid #e9ecef;
            border-radius: 1rem;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }
        .package-card:hover {
            border-color: #D32F2F;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .package-card.featured {
            border-color: #D32F2F;
            background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
        }
        .package-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: -12px;
            right: 20px;
            background: #D32F2F;
            color: white;
            padding: 0.25rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 4px;
        }
        .package-price {
            font-size: 2rem;
            font-weight: 700;
            color: #D32F2F;
        }
        .package-price small {
            font-size: 1rem;
            font-weight: 400;
            color: #6c757d;
        }
        .ad-position {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            height: 100%;
        }
        .ad-position img {
            max-width: 100%;
            height: auto;
        }
        .ad-position h5 {
            margin-bottom: 0.5rem;
        }
        .ad-specs {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 2rem;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 50px;
            height: 3px;
            background: #D32F2F;
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/includes/header.php'); ?>
    
    <!-- Hero Section -->
    <section class="advertise-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-4 fw-bold mb-3">Publicitate pe MatchDay.ro</h1>
                    <p class="lead mb-4">
                        Ajunge la mii de pasionați de fotbal din România. 
                        Promovează-ți brandul pe platforma noastră.
                    </p>
                    <a href="#contact" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-envelope me-2"></i>Contactează-ne
                    </a>
                    <a href="#pachete" class="btn btn-outline-light btn-lg">
                        Vezi Pachetele
                    </a>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($totalPosts) ?>+</div>
                                <div>Articole publicate</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($totalViews / 1000) ?>K+</div>
                                <div>Vizualizări</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number">25-45</div>
                                <div>Vârstă medie</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number">85%</div>
                                <div>Bărbați</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container pb-5">
        <!-- Audience Section -->
        <section class="mb-5">
            <h2 class="section-title">Audiența Noastră</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-users fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5>Pasionați de Fotbal</h5>
                            <p class="text-muted mb-0">Vizitatorii noștri sunt fani dedicați ai fotbalului românesc și internațional, cu interes activ în știri, analize și rezultate.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-chart-line fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5>Engagement Ridicat</h5>
                            <p class="text-muted mb-0">Timp mediu pe pagină peste 2 minute. Comunitate activă în comentarii și sondaje.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-mobile-alt fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5>Mobile First</h5>
                            <p class="text-muted mb-0">65% din trafic vine de pe dispozitive mobile. Site optimizat pentru toate ecranele.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Packages Section -->
        <section class="mb-5" id="pachete">
            <h2 class="section-title">Pachete Publicitate</h2>
            <div class="row g-4">
                <!-- Basic -->
                <div class="col-md-6 col-lg-3">
                    <div class="package-card">
                        <h4 class="text-muted">Basic</h4>
                        <div class="package-price mb-3">200 <small>RON/lună</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>1 banner sidebar</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>10.000 impresii</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Raport lunar</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-muted me-2"></i>Articol sponsorizat</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-danger w-100">Alege Basic</a>
                    </div>
                </div>
                
                <!-- Standard -->
                <div class="col-md-6 col-lg-3">
                    <div class="package-card featured">
                        <h4 class="text-danger">Standard</h4>
                        <div class="package-price mb-3">500 <small>RON/lună</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Banner header + sidebar</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>30.000 impresii</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Raport săptămânal</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>A/B testing</li>
                        </ul>
                        <a href="#contact" class="btn btn-danger w-100">Alege Standard</a>
                    </div>
                </div>
                
                <!-- Premium -->
                <div class="col-md-6 col-lg-3">
                    <div class="package-card">
                        <h4 class="text-muted">Premium</h4>
                        <div class="package-price mb-3">1.000 <small>RON/lună</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Toate pozițiile</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>100.000 impresii</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>1 articol sponsorizat</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support prioritar</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-danger w-100">Alege Premium</a>
                    </div>
                </div>
                
                <!-- Exclusive -->
                <div class="col-md-6 col-lg-3">
                    <div class="package-card">
                        <h4 class="text-muted">Exclusiv</h4>
                        <div class="package-price mb-3">2.000 <small>RON/lună</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Exclusivitate categorie</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Impresii nelimitate</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>4 articole sponsorizate</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Branded content</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-danger w-100">Alege Exclusiv</a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Ad Positions -->
        <section class="mb-5">
            <h2 class="section-title">Poziții Disponibile</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="ad-position">
                        <i class="fas fa-window-maximize fa-2x text-danger mb-2"></i>
                        <h5>Header Banner</h5>
                        <p class="ad-specs mb-0">728 x 90 px<br>Leaderboard</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ad-position">
                        <i class="fas fa-th-large fa-2x text-danger mb-2"></i>
                        <h5>Sidebar</h5>
                        <p class="ad-specs mb-0">300 x 250 px<br>Medium Rectangle</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ad-position">
                        <i class="fas fa-newspaper fa-2x text-danger mb-2"></i>
                        <h5>In-Article</h5>
                        <p class="ad-specs mb-0">336 x 280 px<br>Large Rectangle</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-light rounded">
                <h5><i class="fas fa-info-circle text-danger me-2"></i>Specificații Tehnice</h5>
                <ul class="mb-0">
                    <li>Formate acceptate: JPG, PNG, GIF, HTML5</li>
                    <li>Dimensiune maximă: 150KB pentru imagine, 200KB pentru HTML5</li>
                    <li>Animații: maxim 15 secunde, fără auto-play audio</li>
                    <li>Landing page obligatorie</li>
                </ul>
            </div>
        </section>
        
        <!-- Sponsored Content -->
        <section class="mb-5">
            <h2 class="section-title">Articole Sponsorizate</h2>
            <div class="row">
                <div class="col-lg-8">
                    <p>
                        Oferim posibilitatea de a publica articole sponsorizate pe platformă. 
                        Conținutul este integrat natural în flux-ul editorial și ajunge la o audiență 
                        relevantă și interesată.
                    </p>
                    
                    <h5 class="mt-4">Politica Noastră</h5>
                    <ul>
                        <li><strong>Transparență:</strong> Toate articolele sponsorizate sunt marcate clar ca atare</li>
                        <li><strong>Relevanță:</strong> Acceptăm doar conținut relevant pentru audiența noastră</li>
                        <li><strong>Calitate:</strong> Review editorial complet înainte de publicare</li>
                        <li><strong>Echilibru:</strong> Maxim 20% din conținutul publicat poate fi sponsorizat</li>
                    </ul>
                    
                    <div class="mt-4 p-3 border-start border-4 border-warning bg-light">
                        <h6><i class="fas fa-ad text-warning me-2"></i>Exemplu Disclaimer</h6>
                        <p class="mb-0 fst-italic">
                            "Acest articol este realizat în parteneriat cu [Brand]. 
                            Opiniile exprimate aparțin redacției MatchDay.ro."
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-danger text-white p-4 rounded">
                        <h5><i class="fas fa-ban me-2"></i>Nu Acceptăm</h5>
                        <ul class="mb-0 small">
                            <li>Jocuri de noroc / pariuri</li>
                            <li>Conținut pentru adulți</li>
                            <li>Produse ilegale</li>
                            <li>Fake news / dezinformare</li>
                            <li>Conținut care încalcă regulamentele sportive</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contact Form -->
        <section id="contact">
            <h2 class="section-title">Solicită Ofertă</h2>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body p-4">
                            <form action="/send_contact.php" method="POST">
                                <input type="hidden" name="subject" value="Cerere publicitate">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nume complet *</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Companie</label>
                                        <input type="text" class="form-control" name="company">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pachet Interesat</label>
                                        <select class="form-select" name="package">
                                            <option value="">Selectează...</option>
                                            <option value="basic">Basic (200 RON/lună)</option>
                                            <option value="standard">Standard (500 RON/lună)</option>
                                            <option value="premium">Premium (1.000 RON/lună)</option>
                                            <option value="exclusiv">Exclusiv (2.000 RON/lună)</option>
                                            <option value="custom">Ofertă personalizată</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Mesaj *</label>
                                        <textarea class="form-control" name="message" rows="4" required 
                                                  placeholder="Descrieți ce doriți să promovați și obiectivele campaniei..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-danger btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Trimite Cererea
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="fas fa-envelope text-danger me-2"></i>Contact Direct</h5>
                            <p class="mb-3">
                                Pentru întrebări sau oferte personalizate:
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-at text-muted me-2"></i>
                                <a href="mailto:publicitate@matchday.ro">publicitate@matchday.ro</a>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock text-muted me-2"></i>
                                Răspundem în max. 24h
                            </p>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5><i class="fas fa-file-alt text-danger me-2"></i>Media Kit</h5>
                            <p class="small mb-3">
                                Descarcă prezentarea completă cu statistici, audiență și studii de caz.
                            </p>
                            <a href="#" class="btn btn-outline-danger btn-sm" onclick="alert('Media kit în pregătire'); return false;">
                                <i class="fas fa-download me-1"></i>Descarcă PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include(__DIR__ . '/includes/footer.php'); ?>
    
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
