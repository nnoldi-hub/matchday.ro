<?php
/**
 * Admin Style Guide
 * MatchDay.ro Design System Documentation
 * 
 * Visual reference for all UI components
 */

$pageTitle = 'Style Guide - Admin';
require_once(__DIR__ . '/admin-header.php');

// Only admins can view style guide
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
?>

<style>
.style-section {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: var(--md-radius-lg, 0.75rem);
    box-shadow: var(--md-shadow-card, 0 4px 16px rgba(14, 77, 146, 0.08));
}
.style-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--md-primary, #1a5f3c);
    color: var(--md-primary, #1a5f3c);
}
.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}
.color-swatch {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.color-swatch .color-box {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.color-swatch .color-info {
    padding: 0.5rem;
    background: #f8f9fa;
    font-size: 0.75rem;
}
.color-swatch .color-info code {
    display: block;
    color: #6c757d;
}
.typography-sample {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}
.component-demo {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.spacing-demo {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}
.spacing-box {
    background: var(--md-primary, #1a5f3c);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}
.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 1rem;
}
.icon-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}
.icon-item i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
}
.icon-item code {
    font-size: 0.65rem;
    color: #6c757d;
}
</style>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-palette me-2"></i>Style Guide</h1>
    <p class="text-muted mb-0">MatchDay.ro Design System v1.0</p>
</div>

<!-- Quick Navigation -->
<nav class="mb-4">
    <div class="btn-group flex-wrap">
        <a href="#colors" class="btn btn-outline-primary btn-sm">Culori</a>
        <a href="#typography" class="btn btn-outline-primary btn-sm">Tipografie</a>
        <a href="#spacing" class="btn btn-outline-primary btn-sm">Spacing</a>
        <a href="#buttons" class="btn btn-outline-primary btn-sm">Butoane</a>
        <a href="#badges" class="btn btn-outline-primary btn-sm">Badges</a>
        <a href="#cards" class="btn btn-outline-primary btn-sm">Cards</a>
        <a href="#forms" class="btn btn-outline-primary btn-sm">Formulare</a>
        <a href="#alerts" class="btn btn-outline-primary btn-sm">Alerte</a>
        <a href="#icons" class="btn btn-outline-primary btn-sm">Iconițe</a>
    </div>
</nav>

<!-- Colors Section -->
<section id="colors" class="style-section">
    <h2><i class="fas fa-swatchbook me-2"></i>Paletă Culori</h2>
    
    <h5 class="mb-3">Brand Colors</h5>
    <div class="color-grid mb-4">
        <div class="color-swatch">
            <div class="color-box" style="background: #1a5f3c;">Primary</div>
            <div class="color-info">
                <strong>Primary</strong>
                <code>#1a5f3c</code>
                <code>--md-primary</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #2d8a4f;">Primary Light</div>
            <div class="color-info">
                <strong>Primary Light</strong>
                <code>#2d8a4f</code>
                <code>--md-primary-light</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #0d4a2d;">Primary Dark</div>
            <div class="color-info">
                <strong>Primary Dark</strong>
                <code>#0d4a2d</code>
                <code>--md-primary-dark</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #f0b90b; color: #333;">Accent</div>
            <div class="color-info">
                <strong>Accent</strong>
                <code>#f0b90b</code>
                <code>--md-accent</code>
            </div>
        </div>
    </div>
    
    <h5 class="mb-3">Semantic Colors</h5>
    <div class="color-grid mb-4">
        <div class="color-swatch">
            <div class="color-box" style="background: #28a745;">Success</div>
            <div class="color-info">
                <strong>Success</strong>
                <code>#28a745</code>
                <code>--md-success</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #ffc107; color: #333;">Warning</div>
            <div class="color-info">
                <strong>Warning</strong>
                <code>#ffc107</code>
                <code>--md-warning</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #dc3545;">Danger</div>
            <div class="color-info">
                <strong>Danger</strong>
                <code>#dc3545</code>
                <code>--md-danger</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #17a2b8;">Info</div>
            <div class="color-info">
                <strong>Info</strong>
                <code>#17a2b8</code>
                <code>--md-info</code>
            </div>
        </div>
    </div>
    
    <h5 class="mb-3">Neutrals</h5>
    <div class="color-grid">
        <div class="color-swatch">
            <div class="color-box" style="background: #212529;">Text</div>
            <div class="color-info">
                <strong>Text</strong>
                <code>#212529</code>
                <code>--md-text</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #6c757d;">Text Muted</div>
            <div class="color-info">
                <strong>Text Muted</strong>
                <code>#6c757d</code>
                <code>--md-text-muted</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #dee2e6; color: #333;">Border</div>
            <div class="color-info">
                <strong>Border</strong>
                <code>#dee2e6</code>
                <code>--md-border</code>
            </div>
        </div>
        <div class="color-swatch">
            <div class="color-box" style="background: #f8f9fa; color: #333;">Background Alt</div>
            <div class="color-info">
                <strong>Background Alt</strong>
                <code>#f8f9fa</code>
                <code>--md-bg-alt</code>
            </div>
        </div>
    </div>
</section>

<!-- Typography Section -->
<section id="typography" class="style-section">
    <h2><i class="fas fa-font me-2"></i>Tipografie</h2>
    
    <h5 class="mb-3">Font Family</h5>
    <div class="typography-sample">
        <p style="font-family: Poppins, sans-serif;"><strong>Poppins</strong> - Font principal (headings + body)</p>
        <code>--md-font-heading / --md-font-body: 'Poppins', sans-serif</code>
    </div>
    
    <h5 class="mb-3">Font Sizes</h5>
    <div class="typography-sample">
        <p style="font-size: 0.75rem;">Text XS (12px) - <code>--md-text-xs</code></p>
        <p style="font-size: 0.875rem;">Text SM (14px) - <code>--md-text-sm</code></p>
        <p style="font-size: 1rem;">Text Base (16px) - <code>--md-text-base</code></p>
        <p style="font-size: 1.125rem;">Text LG (18px) - <code>--md-text-lg</code></p>
        <p style="font-size: 1.25rem;">Text XL (20px) - <code>--md-text-xl</code></p>
        <p style="font-size: 1.5rem;">Text 2XL (24px) - <code>--md-text-2xl</code></p>
        <p style="font-size: 1.875rem;">Text 3XL (30px) - <code>--md-text-3xl</code></p>
        <p style="font-size: 2.25rem;">Text 4XL (36px) - <code>--md-text-4xl</code></p>
    </div>
    
    <h5 class="mb-3">Font Weights</h5>
    <div class="typography-sample">
        <p style="font-weight: 300;">Light (300) - <code>--md-font-light</code></p>
        <p style="font-weight: 400;">Normal (400) - <code>--md-font-normal</code></p>
        <p style="font-weight: 500;">Medium (500) - <code>--md-font-medium</code></p>
        <p style="font-weight: 600;">Semibold (600) - <code>--md-font-semibold</code></p>
        <p style="font-weight: 700;">Bold (700) - <code>--md-font-bold</code></p>
    </div>
    
    <h5 class="mb-3">Headings</h5>
    <div class="typography-sample">
        <h1>Heading 1 - Titlu principal</h1>
        <h2>Heading 2 - Secțiune</h2>
        <h3>Heading 3 - Subsecțiune</h3>
        <h4>Heading 4 - Titlu card</h4>
        <h5>Heading 5 - Label</h5>
        <h6>Heading 6 - Small heading</h6>
    </div>
</section>

<!-- Spacing Section -->
<section id="spacing" class="style-section">
    <h2><i class="fas fa-arrows-alt me-2"></i>Spacing</h2>
    
    <div class="spacing-demo">
        <div>
            <div class="spacing-box" style="width: 4px; height: 4px;"></div>
            <small class="d-block mt-1 text-center">1<br>(4px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 8px; height: 8px;"></div>
            <small class="d-block mt-1 text-center">2<br>(8px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 12px; height: 12px;"></div>
            <small class="d-block mt-1 text-center">3<br>(12px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 16px; height: 16px;"></div>
            <small class="d-block mt-1 text-center">4<br>(16px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 24px; height: 24px;"></div>
            <small class="d-block mt-1 text-center">6<br>(24px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 32px; height: 32px;"></div>
            <small class="d-block mt-1 text-center">8<br>(32px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 48px; height: 48px;"></div>
            <small class="d-block mt-1 text-center">12<br>(48px)</small>
        </div>
        <div>
            <div class="spacing-box" style="width: 64px; height: 64px;"></div>
            <small class="d-block mt-1 text-center">16<br>(64px)</small>
        </div>
    </div>
    
    <div class="mt-4">
        <code>--md-space-{n}</code> unde n = 1, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24
    </div>
</section>

<!-- Buttons Section -->
<section id="buttons" class="style-section">
    <h2><i class="fas fa-hand-pointer me-2"></i>Butoane</h2>
    
    <h5 class="mb-3">Primary Buttons</h5>
    <div class="component-demo">
        <button class="btn btn-primary me-2">Primary</button>
        <button class="btn btn-secondary me-2">Secondary</button>
        <button class="btn btn-success me-2">Success</button>
        <button class="btn btn-warning me-2">Warning</button>
        <button class="btn btn-danger me-2">Danger</button>
        <button class="btn btn-info me-2">Info</button>
    </div>
    
    <h5 class="mb-3">Outline Buttons</h5>
    <div class="component-demo">
        <button class="btn btn-outline-primary me-2">Primary</button>
        <button class="btn btn-outline-secondary me-2">Secondary</button>
        <button class="btn btn-outline-success me-2">Success</button>
        <button class="btn btn-outline-warning me-2">Warning</button>
        <button class="btn btn-outline-danger me-2">Danger</button>
    </div>
    
    <h5 class="mb-3">Button Sizes</h5>
    <div class="component-demo">
        <button class="btn btn-primary btn-sm me-2">Small</button>
        <button class="btn btn-primary me-2">Normal</button>
        <button class="btn btn-primary btn-lg me-2">Large</button>
    </div>
    
    <h5 class="mb-3">Button with Icons</h5>
    <div class="component-demo">
        <button class="btn btn-primary me-2"><i class="fas fa-plus me-1"></i>Adaugă</button>
        <button class="btn btn-success me-2"><i class="fas fa-check me-1"></i>Salvează</button>
        <button class="btn btn-danger me-2"><i class="fas fa-trash me-1"></i>Șterge</button>
        <button class="btn btn-outline-secondary me-2"><i class="fas fa-download me-1"></i>Export</button>
    </div>
</section>

<!-- Badges Section -->
<section id="badges" class="style-section">
    <h2><i class="fas fa-tag me-2"></i>Badges</h2>
    
    <h5 class="mb-3">Status Badges</h5>
    <div class="component-demo">
        <span class="badge bg-primary me-2">Primary</span>
        <span class="badge bg-secondary me-2">Secondary</span>
        <span class="badge bg-success me-2">Success</span>
        <span class="badge bg-warning text-dark me-2">Warning</span>
        <span class="badge bg-danger me-2">Danger</span>
        <span class="badge bg-info me-2">Info</span>
    </div>
    
    <h5 class="mb-3">Pill Badges</h5>
    <div class="component-demo">
        <span class="badge rounded-pill bg-primary me-2">12</span>
        <span class="badge rounded-pill bg-success me-2">New</span>
        <span class="badge rounded-pill bg-danger me-2">Hot</span>
        <span class="badge rounded-pill bg-warning text-dark me-2">Pro</span>
    </div>
    
    <h5 class="mb-3">Category Badges</h5>
    <div class="component-demo">
        <a href="#" class="tag me-2">Liga 1</a>
        <a href="#" class="tag me-2">Champions League</a>
        <a href="#" class="tag me-2">Transferuri</a>
        <a href="#" class="tag me-2">Națională</a>
    </div>
</section>

<!-- Cards Section -->
<section id="cards" class="style-section">
    <h2><i class="fas fa-square me-2"></i>Cards</h2>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card card-article h-100">
                <div class="cover" style="background: linear-gradient(135deg, #1a5f3c, #2d8a4f);"></div>
                <div class="card-body">
                    <span class="tag mb-2">Liga 1</span>
                    <h5 class="card-title">Titlu Articol de Test</h5>
                    <p class="card-text text-muted small">Descriere scurtă a articolului cu maxim 2-3 rânduri de text...</p>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted"><i class="far fa-clock me-1"></i>Acum 2 ore</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-poll me-2"></i>Sondaj
                </div>
                <div class="card-body">
                    <h5 class="card-title">Cine va câștiga titlul?</h5>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>FCSB</span>
                            <span>45%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 45%"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>CFR Cluj</span>
                            <span>35%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: 35%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-body text-center">
                    <i class="fas fa-futbol fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Live Score</h5>
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <span class="fw-bold">FCSB</span>
                        <span class="badge bg-dark fs-5">2 - 1</span>
                        <span class="fw-bold">Rapid</span>
                    </div>
                    <small class="text-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i>LIVE 78'</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Forms Section -->
<section id="forms" class="style-section">
    <h2><i class="fas fa-edit me-2"></i>Formulare</h2>
    
    <div class="row">
        <div class="col-md-6">
            <h5 class="mb-3">Input Fields</h5>
            <div class="component-demo">
                <div class="mb-3">
                    <label class="form-label">Text Input</label>
                    <input type="text" class="form-control" placeholder="Introdu text...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="email@exemplu.ro">
                </div>
                <div class="mb-3">
                    <label class="form-label">Textarea</label>
                    <textarea class="form-control" rows="3" placeholder="Scrie mesajul..."></textarea>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <h5 class="mb-3">Select & Checkboxes</h5>
            <div class="component-demo">
                <div class="mb-3">
                    <label class="form-label">Select</label>
                    <select class="form-select">
                        <option>Alege opțiunea...</option>
                        <option>Opțiunea 1</option>
                        <option>Opțiunea 2</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="check1" checked>
                        <label class="form-check-label" for="check1">Checkbox selectat</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="check2">
                        <label class="form-check-label" for="check2">Checkbox neselectat</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="switch1" checked>
                        <label class="form-check-label" for="switch1">Toggle switch</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <h5 class="mb-3">Input States</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Valid</label>
            <input type="text" class="form-control is-valid" value="Input valid">
            <div class="valid-feedback">Arată bine!</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Invalid</label>
            <input type="text" class="form-control is-invalid" value="Input invalid">
            <div class="invalid-feedback">Câmp obligatoriu!</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Disabled</label>
            <input type="text" class="form-control" disabled value="Dezactivat">
        </div>
    </div>
</section>

<!-- Alerts Section -->
<section id="alerts" class="style-section">
    <h2><i class="fas fa-exclamation-circle me-2"></i>Alerte</h2>
    
    <div class="component-demo">
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Succes!</strong> Operațiunea a fost finalizată cu succes.
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Info:</strong> Aceasta este o notificare informativă.
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenție!</strong> Verificați datele introduse.
        </div>
        
        <div class="alert alert-danger">
            <i class="fas fa-times-circle me-2"></i>
            <strong>Eroare!</strong> A apărut o problemă la procesare.
        </div>
        
        <div class="alert alert-primary alert-dismissible fade show">
            <i class="fas fa-bell me-2"></i>
            Alertă care poate fi închisă
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</section>

<!-- Icons Section -->
<section id="icons" class="style-section">
    <h2><i class="fas fa-icons me-2"></i>Iconițe Comune</h2>
    <p class="text-muted mb-3">Folosim Font Awesome 6. Vezi toate iconițele la <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com</a></p>
    
    <h5 class="mb-3">Acțiuni</h5>
    <div class="icon-grid mb-4">
        <div class="icon-item"><i class="fas fa-plus"></i><code>fa-plus</code></div>
        <div class="icon-item"><i class="fas fa-edit"></i><code>fa-edit</code></div>
        <div class="icon-item"><i class="fas fa-trash"></i><code>fa-trash</code></div>
        <div class="icon-item"><i class="fas fa-save"></i><code>fa-save</code></div>
        <div class="icon-item"><i class="fas fa-download"></i><code>fa-download</code></div>
        <div class="icon-item"><i class="fas fa-upload"></i><code>fa-upload</code></div>
        <div class="icon-item"><i class="fas fa-search"></i><code>fa-search</code></div>
        <div class="icon-item"><i class="fas fa-filter"></i><code>fa-filter</code></div>
    </div>
    
    <h5 class="mb-3">Sport</h5>
    <div class="icon-grid mb-4">
        <div class="icon-item"><i class="fas fa-futbol"></i><code>fa-futbol</code></div>
        <div class="icon-item"><i class="fas fa-trophy"></i><code>fa-trophy</code></div>
        <div class="icon-item"><i class="fas fa-medal"></i><code>fa-medal</code></div>
        <div class="icon-item"><i class="fas fa-flag-checkered"></i><code>fa-flag...</code></div>
        <div class="icon-item"><i class="fas fa-stopwatch"></i><code>fa-stopwatch</code></div>
        <div class="icon-item"><i class="fas fa-star"></i><code>fa-star</code></div>
    </div>
    
    <h5 class="mb-3">Status</h5>
    <div class="icon-grid">
        <div class="icon-item text-success"><i class="fas fa-check-circle"></i><code>fa-check...</code></div>
        <div class="icon-item text-danger"><i class="fas fa-times-circle"></i><code>fa-times...</code></div>
        <div class="icon-item text-warning"><i class="fas fa-exclamation-triangle"></i><code>fa-excl...</code></div>
        <div class="icon-item text-info"><i class="fas fa-info-circle"></i><code>fa-info...</code></div>
        <div class="icon-item"><i class="fas fa-spinner fa-spin"></i><code>fa-spinner</code></div>
        <div class="icon-item"><i class="fas fa-circle-notch fa-spin"></i><code>fa-circle...</code></div>
    </div>
</section>

<!-- Image Guidelines -->
<section class="style-section">
    <h2><i class="fas fa-image me-2"></i>Ghid Imagini</h2>
    
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Tip</th>
                    <th>Dimensiuni</th>
                    <th>Format</th>
                    <th>Max Size</th>
                    <th>Aspect Ratio</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Featured Image</strong></td>
                    <td>1200 × 630 px</td>
                    <td>JPEG, WebP</td>
                    <td>200 KB</td>
                    <td>1.91:1 (OG)</td>
                </tr>
                <tr>
                    <td><strong>Thumbnail</strong></td>
                    <td>400 × 300 px</td>
                    <td>JPEG, WebP</td>
                    <td>50 KB</td>
                    <td>4:3</td>
                </tr>
                <tr>
                    <td><strong>Avatar</strong></td>
                    <td>200 × 200 px</td>
                    <td>PNG, WebP</td>
                    <td>30 KB</td>
                    <td>1:1</td>
                </tr>
                <tr>
                    <td><strong>Logo</strong></td>
                    <td>200 × 60 px</td>
                    <td>PNG, SVG</td>
                    <td>20 KB</td>
                    <td>~3:1</td>
                </tr>
                <tr>
                    <td><strong>Icon</strong></td>
                    <td>64 × 64 px</td>
                    <td>SVG, PNG</td>
                    <td>5 KB</td>
                    <td>1:1</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <h5 class="mt-4 mb-3">Convenții</h5>
    <ul>
        <li>Compresie: 80% quality pentru JPEG</li>
        <li>Toate imaginile trebuie să aibă <code>alt</code> text descriptiv</li>
        <li>Folosește lazy loading pentru imagini below fold</li>
        <li>Preferă WebP pentru browsere moderne</li>
    </ul>
</section>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
