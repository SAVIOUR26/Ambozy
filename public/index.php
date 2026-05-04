<?php
$msg = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_token']) && $_POST['form_token'] === 'contact') {
    $name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $service = htmlspecialchars(trim($_POST['service'] ?? ''), ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

    if (!$name || !$email || !$message) {
        $msg = 'Please fill in all required fields.';
        $msg_type = 'error';
    } else {
        $to      = 'info@ambozygroup.com';
        $subject = "New Enquiry from $name" . ($service ? " — $service" : '');
        $body    = "Name: $name\nEmail: $email\nService: $service\n\nMessage:\n$message";
        $headers = "From: noreply@ambozygraphics.shop\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
        if (mail($to, $subject, $body, $headers)) {
            $msg = "Thank you, $name! Your message has been sent. We'll be in touch shortly.";
            $msg_type = 'success';
        } else {
            $msg = 'Sorry, there was a problem sending your message. Please email us directly at info@ambozygroup.com.';
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Ambozy Graphics Solutions Ltd — Premium printing, designing, branding and promotional services in Kampala, Uganda. Est. 2010.">
  <meta name="keywords" content="graphic design, printing, branding, promotional, Kampala, Uganda, Ambozy">
  <meta property="og:title" content="Ambozy Graphics Solutions Ltd">
  <meta property="og:description" content="Premium printing, designing, branding and promotional services in Uganda since 2010.">
  <meta property="og:type" content="website">
  <title>Ambozy Graphics Solutions Ltd — Printing · Designing · Branding · Uganda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ============================================================
     NAVBAR
============================================================ -->
<nav id="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <div class="nav-inner">
      <a href="#hero" class="nav-logo" aria-label="Ambozy Graphics — Home">
        <img src="assets/images/logo-white.png" alt="Ambozy Graphics Solutions Ltd" width="160" height="46">
      </a>
      <ul class="nav-links" role="list">
        <li><a href="#about">About</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#clients">Clients</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
      <a href="#contact" class="btn btn-primary nav-cta">Get a Quote</a>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="nav-mobile" id="navMobile" role="menu">
  <a href="#about" role="menuitem">About</a>
  <a href="#services" role="menuitem">Services</a>
  <a href="#products" role="menuitem">Products</a>
  <a href="#clients" role="menuitem">Clients</a>
  <a href="#contact" role="menuitem">Contact</a>
  <a href="#contact" class="btn btn-primary">Get a Quote</a>
</div>

<!-- ============================================================
     HERO
============================================================ -->
<section id="hero" aria-label="Hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="hero-shape hero-shape-1"></div>
  <div class="hero-shape hero-shape-2"></div>

  <div class="container">
    <div class="hero-content">
      <p class="hero-eyebrow">Est. 2010 · Kampala, Uganda</p>
      <h1 class="hero-title">
        We Make Your<br>
        Brand <span class="highlight">Unforgettable</span>
      </h1>
      <div class="hero-services" aria-label="Core services">
        <span>Printing</span>
        <span>Designing</span>
        <span>Contractors</span>
        <span>General Supplies</span>
      </div>
      <p class="hero-desc">
        Ambozy Graphics Solutions Ltd delivers premium branding, print and promotional solutions to businesses across Uganda — crafted with precision, delivered with integrity.
      </p>
      <div class="hero-actions">
        <a href="#services" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          Our Services
        </a>
        <a href="#contact" class="btn btn-outline">Contact Us</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-num">15+</div>
          <div class="hero-stat-label">Years in Business</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-num">500+</div>
          <div class="hero-stat-label">Projects Delivered</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-num">4</div>
          <div class="hero-stat-label">Core Service Pillars</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-num">100%</div>
          <div class="hero-stat-label">Custom Solutions</div>
        </div>
      </div>
    </div>
  </div>

  <div class="hero-scroll" aria-hidden="true">
    <span>Scroll</span>
    <div class="scroll-line"></div>
  </div>
</section>

<!-- ============================================================
     SERVICES MARQUEE STRIP
============================================================ -->
<div class="services-strip" aria-hidden="true">
  <div class="marquee-track">
    <?php
    $items = ['Branded Merchandise','Custom Signage','Offset Printing','Event Branding','Corporate Stationery','Promotional Gifts','Outdoor Advertising','Packaging Solutions','Vehicle Branding','Digital Printing','Neon Signs','Exhibition Displays'];
    $repeated = array_merge($items, $items);
    foreach ($repeated as $item): ?>
    <span class="marquee-item">
      <?= $item ?>
      <span class="marquee-dot"></span>
    </span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ============================================================
     ABOUT
============================================================ -->
<section id="about" aria-labelledby="about-heading">
  <div class="container">
    <div class="about-grid">

      <div class="about-text reveal">
        <span class="section-label">About Us</span>
        <h2 id="about-heading">A Legacy of <em>Creative Excellence</em></h2>
        <p>Founded in 2010, Ambozy Graphics Solutions Ltd has grown into one of Kampala's most trusted graphic design, printing, branding and promotional companies. From a single office on Nasser Road to serving government ministries and leading enterprises, our journey is built on quality and integrity.</p>
        <p>We combine cutting-edge design with premium production to deliver branded materials that truly represent our clients — because your brand is your most powerful asset.</p>

        <div class="about-pillars">
          <div class="about-pillar reveal reveal-delay-1">
            <div class="pillar-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <div class="pillar-title">Premium Quality</div>
            <p class="pillar-text">Every product meets the highest standards — from concept to final delivery.</p>
          </div>
          <div class="about-pillar reveal reveal-delay-2">
            <div class="pillar-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div class="pillar-title">Fast Turnaround</div>
            <p class="pillar-text">We understand deadlines. Our efficient workflows ensure timely delivery every time.</p>
          </div>
          <div class="about-pillar reveal reveal-delay-3">
            <div class="pillar-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="pillar-title">Client-Centered</div>
            <p class="pillar-text">Custom solutions tailored to your specific needs, goals and budget.</p>
          </div>
          <div class="about-pillar reveal reveal-delay-4">
            <div class="pillar-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="pillar-title">Integrity</div>
            <p class="pillar-text">Transparent pricing, honest timelines, and trustworthy partnerships since day one.</p>
          </div>
        </div>
      </div>

      <div class="about-values reveal reveal-delay-2">
        <div class="value-card">
          <div class="value-card-tag">Our Vision</div>
          <h3>Acknowledged Leaders in Design & Branding</h3>
          <p>To maintain and be the acknowledged leader in designing, printing, branding and promotional services through consistent improvement of quality.</p>
        </div>
        <div class="value-card">
          <div class="value-card-tag">Our Mission</div>
          <h3>Delivering Individualized Solutions</h3>
          <p>To provide exceptional designing, printing, branding and promotional services and to deliver individualized solutions to our clients in a way that adds value to all our stakeholders.</p>
        </div>
        <div class="core-values-list">
          <h4>Core Values</h4>
          <ul>
            <li>Service</li>
            <li>Premium Quality</li>
            <li>Professionalism</li>
            <li>Exceptional Value</li>
            <li>Integrity</li>
            <li>Creativity &amp; Innovation</li>
            <li>Teamwork</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     SERVICES — 4 CORE PILLARS
============================================================ -->
<section id="services" aria-labelledby="services-heading">
  <div class="container">
    <span class="section-label reveal">What We Do</span>
    <h2 class="services-heading reveal reveal-delay-1" id="services-heading">
      Four Pillars of <em>Exceptional</em> Service
    </h2>
    <div class="services-grid">

      <div class="service-card reveal">
        <div class="service-card-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="service-card-num">01</div>
        <h3>Printing</h3>
        <p>High-quality offset and digital printing for brochures, posters, business cards, books, banners, stationery and every printed material your business needs.</p>
        <div class="service-card-arrow">
          Explore
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </div>

      <div class="service-card reveal reveal-delay-1">
        <div class="service-card-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
        </div>
        <div class="service-card-num">02</div>
        <h3>Designing</h3>
        <p>Creative graphic design services — brand identity, logos, marketing materials, packaging, digital assets and comprehensive visual communication strategies.</p>
        <div class="service-card-arrow">
          Explore
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </div>

      <div class="service-card reveal reveal-delay-2">
        <div class="service-card-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="service-card-num">03</div>
        <h3>Contractors</h3>
        <p>End-to-end project contracting for large-scale signage installation, vehicle branding, outdoor advertising, event setups and full-scale brand activations.</p>
        <div class="service-card-arrow">
          Explore
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </div>

      <div class="service-card reveal reveal-delay-3">
        <div class="service-card-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="service-card-num">04</div>
        <h3>General Supplies</h3>
        <p>A comprehensive catalogue of branded merchandise, promotional gifts, corporate stationery, packaging materials and custom supplies — all under one roof.</p>
        <div class="service-card-arrow">
          Explore
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     PRODUCTS
============================================================ -->
<section id="products" aria-labelledby="products-heading">
  <div class="container">
    <div class="products-intro reveal">
      <div>
        <span class="section-label">Product Catalogue</span>
        <h2 id="products-heading">Everything Your Brand <em>Needs</em></h2>
      </div>
      <p>From promotional gifts to large-format signage — if you can imagine it, Ambozy can produce it.</p>
    </div>

    <div class="cat-tabs" role="tablist" aria-label="Product categories">
      <button class="cat-tab active" role="tab" aria-selected="true" data-cat="all">All</button>
      <button class="cat-tab" role="tab" aria-selected="false" data-cat="merchandise">Branded Merchandise</button>
      <button class="cat-tab" role="tab" aria-selected="false" data-cat="promo">Promo &amp; Gifts</button>
      <button class="cat-tab" role="tab" aria-selected="false" data-cat="print">Print &amp; Books</button>
      <button class="cat-tab" role="tab" aria-selected="false" data-cat="signs">Signs &amp; Outdoor</button>
      <button class="cat-tab" role="tab" aria-selected="false" data-cat="packaging">Packaging</button>
    </div>

    <div class="products-grid" id="productsGrid">
      <?php
      $products = [
        ['cat'=>'merchandise','icon'=>'shirt',   'name'=>'T-Shirts &amp; Polo Shirts',    'label'=>'Branded Merchandise'],
        ['cat'=>'merchandise','icon'=>'hat',     'name'=>'Caps &amp; Hats',               'label'=>'Branded Merchandise'],
        ['cat'=>'merchandise','icon'=>'bag',     'name'=>'Tote &amp; Backpack Bags',      'label'=>'Branded Merchandise'],
        ['cat'=>'merchandise','icon'=>'apron',   'name'=>'Aprons &amp; Overalls',         'label'=>'Branded Merchandise'],
        ['cat'=>'promo',      'icon'=>'mug',     'name'=>'Mugs &amp; Ceramics',           'label'=>'Promo &amp; Gifts'],
        ['cat'=>'promo',      'icon'=>'usb',     'name'=>'Custom USB Flash Disks',        'label'=>'Promo &amp; Gifts'],
        ['cat'=>'promo',      'icon'=>'pen',     'name'=>'Promotional Pens',              'label'=>'Promo &amp; Gifts'],
        ['cat'=>'promo',      'icon'=>'clock',   'name'=>'Wall &amp; Desk Clocks',        'label'=>'Promo &amp; Gifts'],
        ['cat'=>'promo',      'icon'=>'bottle',  'name'=>'Water Bottles &amp; Flasks',    'label'=>'Promo &amp; Gifts'],
        ['cat'=>'promo',      'icon'=>'mousepad','name'=>'Mouse Pads &amp; Wristbands',   'label'=>'Promo &amp; Gifts'],
        ['cat'=>'print',      'icon'=>'card',    'name'=>'Business Cards',                'label'=>'Print &amp; Books'],
        ['cat'=>'print',      'icon'=>'brochure','name'=>'Brochures &amp; Leaflets',      'label'=>'Print &amp; Books'],
        ['cat'=>'print',      'icon'=>'poster',  'name'=>'Posters &amp; Banners',         'label'=>'Print &amp; Books'],
        ['cat'=>'print',      'icon'=>'book',    'name'=>'Magazines &amp; Annual Reports','label'=>'Print &amp; Books'],
        ['cat'=>'print',      'icon'=>'cert',    'name'=>'Certificates &amp; Invitations','label'=>'Print &amp; Books'],
        ['cat'=>'signs',      'icon'=>'neon',    'name'=>'Neon &amp; Illuminated Signs',  'label'=>'Signs &amp; Outdoor'],
        ['cat'=>'signs',      'icon'=>'pullup',  'name'=>'Pull-ups &amp; L-Banners',      'label'=>'Signs &amp; Outdoor'],
        ['cat'=>'signs',      'icon'=>'acrylic', 'name'=>'Acrylic &amp; Wall Signs',      'label'=>'Signs &amp; Outdoor'],
        ['cat'=>'signs',      'icon'=>'vehicle', 'name'=>'Vehicle Branding',              'label'=>'Signs &amp; Outdoor'],
        ['cat'=>'packaging',  'icon'=>'label',   'name'=>'Product Labels',                'label'=>'Packaging'],
        ['cat'=>'packaging',  'icon'=>'box',     'name'=>'Boxes &amp; Paper Cups',        'label'=>'Packaging'],
        ['cat'=>'packaging',  'icon'=>'shopbag', 'name'=>'Shopping &amp; Kraft Bags',     'label'=>'Packaging'],
      ];
      foreach ($products as $p): ?>
      <div class="product-card reveal" data-cat="<?= $p['cat'] ?>">
        <div class="product-thumb">
          <svg class="product-thumb-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <?php if ($p['icon']==='shirt'):   ?><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/><?php
            elseif($p['icon']==='hat'):      ?><path d="M9 11l3-3 3 3"/><path d="M12 3a9 9 0 0 0-9 9v1h18v-1a9 9 0 0 0-9-9z"/><rect x="3" y="16" width="18" height="2" rx="1"/><?php
            elseif($p['icon']==='bag'):      ?><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/><?php
            elseif($p['icon']==='apron'):    ?><rect x="8" y="2" width="8" height="4" rx="2"/><path d="M8 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-3"/><?php
            elseif($p['icon']==='mug'):      ?><path d="M17 8h1a4 4 0 0 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/><?php
            elseif($p['icon']==='usb'):      ?><rect x="7" y="2" width="10" height="8" rx="1"/><path d="M9 10v4M15 10v4M12 10v6M9 14h6"/><rect x="5" y="18" width="14" height="4" rx="2"/><?php
            elseif($p['icon']==='pen'):      ?><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/><path d="M20 5.27V3a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v2.27a1 1 0 0 0 .55.89L12 10l7.45-3.84A1 1 0 0 0 20 5.27z"/><path d="M4 7v7a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V7"/><?php
            elseif($p['icon']==='clock'):    ?><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><?php
            elseif($p['icon']==='bottle'):   ?><line x1="8" y1="2" x2="8" y2="4"/><line x1="16" y1="2" x2="16" y2="4"/><rect x="5" y="4" width="14" height="16" rx="3"/><line x1="5" y1="10" x2="19" y2="10"/><?php
            elseif($p['icon']==='mousepad'): ?><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><?php
            elseif($p['icon']==='card'):     ?><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/><?php
            elseif($p['icon']==='brochure'): ?><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/><?php
            elseif($p['icon']==='poster'):   ?><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/><?php
            elseif($p['icon']==='book'):     ?><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><?php
            elseif($p['icon']==='cert'):     ?><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/><?php
            elseif($p['icon']==='neon'):     ?><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/><?php
            elseif($p['icon']==='pullup'):   ?><line x1="12" y1="22" x2="12" y2="11"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><rect x="6" y="2" width="12" height="9" rx="1"/><?php
            elseif($p['icon']==='acrylic'):  ?><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"/><line x1="12" y1="22" x2="12" y2="15.5"/><polyline points="22 8.5 12 15.5 2 8.5"/><?php
            elseif($p['icon']==='vehicle'):  ?><rect x="1" y="3" width="15" height="13" rx="1"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/><?php
            elseif($p['icon']==='label'):    ?><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/><?php
            elseif($p['icon']==='box'):      ?><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/><?php
            elseif($p['icon']==='shopbag'):  ?><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/><?php
            endif; ?>
          </svg>
        </div>
        <div class="product-card-body">
          <div class="product-card-cat"><?= $p['label'] ?></div>
          <div class="product-card-name"><?= $p['name'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     CLIENTS
============================================================ -->
<section id="clients" aria-labelledby="clients-heading">
  <div class="container">
    <span class="section-label reveal">Trusted By</span>
    <h2 class="clients-heading reveal reveal-delay-1" id="clients-heading">Partners Who Trust Our Work</h2>
    <div class="clients-grid">
      <?php
      $clients = [
        ['name'=>'Ministry of Trade, Industry &amp; Co-operatives','sub'=>'Government of Uganda'],
        ['name'=>'Ministry of Water &amp; Environment',            'sub'=>'Government of Uganda'],
        ['name'=>'Dooba Enterprises Ltd',                          'sub'=>'Private Sector'],
        ['name'=>'YAPS — Young People &amp; Adolescent Peer Support','sub'=>'Health Sector'],
      ];
      foreach ($clients as $i => $c): ?>
      <div class="client-badge reveal reveal-delay-<?= $i+1 ?>">
        <div class="client-badge-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="client-badge-name">
          <?= $c['name'] ?>
          <br><small style="opacity:.55;font-weight:400"><?= $c['sub'] ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     WHY AMBOZY
============================================================ -->
<section id="why" aria-labelledby="why-heading">
  <div class="container">
    <div class="why-grid">

      <div class="why-visual reveal">
        <div class="why-badge-main">
          <div class="big-num">15+</div>
          <div class="big-label">Years of Trusted Expertise</div>
          <p>Since 2010, Ambozy has been the go-to partner for brands that demand quality, creativity and reliability in every project.</p>
        </div>
        <div class="why-floating-card why-card-1">
          <div class="icon-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="card-text">
            <strong>500+ Projects</strong>
            <span>Delivered on time</span>
          </div>
        </div>
        <div class="why-floating-card why-card-2">
          <div class="icon-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          </div>
          <div class="card-text">
            <strong>5-Star Service</strong>
            <span>Client satisfaction</span>
          </div>
        </div>
      </div>

      <div class="why-points">
        <span class="section-label reveal">Why Choose Us</span>
        <h2 class="reveal reveal-delay-1" id="why-heading" style="font-family:var(--font-display);font-size:clamp(1.8rem,3.5vw,2.5rem);font-weight:900;line-height:1.1;letter-spacing:-.02em;color:var(--black);margin-bottom:1.5rem">
          The Ambozy <em style="font-style:normal;color:var(--orange)">Difference</em>
        </h2>
        <?php
        $points = [
          ['icon'=>'award',  'title'=>'Industry-Proven Quality','body'=>'Over 15 years serving government agencies and private enterprises with premium-grade production that consistently exceeds expectations.'],
          ['icon'=>'zap',    'title'=>'One-Stop Creative Hub',  'body'=>'Design, print, brand and supply — everything under one roof. No juggling multiple vendors; one partner handles it all.'],
          ['icon'=>'target', 'title'=>'Custom-Fit Solutions',   'body'=>'We don\'t do off-the-shelf. Every project is crafted specifically to your brand guidelines, timeline and budget.'],
          ['icon'=>'shield', 'title'=>'Integrity-First Approach','body'=>'Transparent pricing, honest delivery estimates and clear communication every step of the way.'],
        ];
        foreach ($points as $i => $pt): ?>
        <div class="why-point reveal reveal-delay-<?= $i+1 ?>">
          <div class="why-point-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <?php if($pt['icon']==='award'):  ?><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/><?php
              elseif($pt['icon']==='zap'):    ?><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/><?php
              elseif($pt['icon']==='target'): ?><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/><?php
              elseif($pt['icon']==='shield'): ?><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><?php
              endif; ?>
            </svg>
          </div>
          <div class="why-point-body">
            <h4><?= $pt['title'] ?></h4>
            <p><?= $pt['body'] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     CONTACT
============================================================ -->
<section id="contact" aria-labelledby="contact-heading">
  <div class="container">
    <span class="section-label reveal">Get In Touch</span>
    <h2 class="contact-heading reveal reveal-delay-1" id="contact-heading">
      Let's Build Something <em>Great</em>
    </h2>
    <p class="contact-subheading reveal reveal-delay-2">
      Ready to elevate your brand? Fill out the form and our team will get back to you within 24 hours.
    </p>

    <div class="contact-grid">
      <div class="contact-details reveal">
        <div class="contact-item">
          <div class="contact-item-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <div class="contact-item-label">Address</div>
            <div class="contact-item-value">Plot 43 Nasser / Nkrumah Road,<br>Opposite Picfare, Kampala, Uganda<br>P.O. Box 14521, Kampala</div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.44 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.18a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <div class="contact-item-label">Phone</div>
            <div class="contact-item-value">
              <a href="tel:+256392839447">+256-392-839-447</a><br>
              <a href="tel:+256782187799">+256-782-187-799</a><br>
              <a href="tel:+256702371230">+256-702-371-230</a>
            </div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <div class="contact-item-label">Email</div>
            <div class="contact-item-value">
              <a href="mailto:info@ambozygroup.com">info@ambozygroup.com</a><br>
              <a href="mailto:ambozygraphics@gmail.com">ambozygraphics@gmail.com</a>
            </div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div>
            <div class="contact-item-label">Working Hours</div>
            <div class="contact-item-value">Monday – Friday: 8:00 AM – 6:00 PM<br>Saturday: 9:00 AM – 3:00 PM</div>
          </div>
        </div>
      </div>

      <div class="reveal reveal-delay-2">
        <?php if ($msg): ?>
        <div class="form-msg <?= $msg_type ?>" role="alert"><?= $msg ?></div>
        <?php endif; ?>
        <form class="contact-form" method="POST" action="#contact" id="contactForm" novalidate>
          <input type="hidden" name="form_token" value="contact">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name <span aria-hidden="true">*</span></label>
              <input type="text" id="name" name="name" placeholder="Your name" required autocomplete="name">
            </div>
            <div class="form-group">
              <label for="email">Email Address <span aria-hidden="true">*</span></label>
              <input type="email" id="email" name="email" placeholder="you@company.com" required autocomplete="email">
            </div>
          </div>
          <div class="form-group">
            <label for="company">Company / Organisation</label>
            <input type="text" id="company" name="company" placeholder="Your organisation" autocomplete="organization">
          </div>
          <div class="form-group">
            <label for="service">Service Required</label>
            <select id="service" name="service">
              <option value="" disabled selected>Select a service…</option>
              <option>Printing</option>
              <option>Graphic Design</option>
              <option>Branded Merchandise</option>
              <option>Signage &amp; Outdoor</option>
              <option>Promotional Gifts</option>
              <option>Packaging</option>
              <option>Contractors / Installation</option>
              <option>General Supplies</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="message">Message <span aria-hidden="true">*</span></label>
            <textarea id="message" name="message" placeholder="Tell us about your project, quantity, timeline…" required></textarea>
          </div>
          <div id="formMsg" class="form-msg" role="alert" aria-live="polite"></div>
          <button type="submit" class="btn btn-primary" style="align-self:flex-start">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Message
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer>
  <div class="container">
    <div class="footer-grid">

      <div class="footer-brand">
        <img src="assets/images/logo-white.png" alt="Ambozy Graphics Solutions Ltd" width="160" height="50">
        <p>Premium printing, designing, branding and promotional services in Kampala, Uganda. Crafted with precision since 2010.</p>
        <div class="footer-social">
          <a href="mailto:ambozygraphics@gmail.com" aria-label="Email us">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </a>
          <a href="tel:+256782187799" aria-label="Call us">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.44 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.18a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </a>
        </div>
      </div>

      <div class="footer-col">
        <h5>Services</h5>
        <ul>
          <li><a href="#services">Printing</a></li>
          <li><a href="#services">Designing</a></li>
          <li><a href="#services">Contractors</a></li>
          <li><a href="#services">General Supplies</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Products</h5>
        <ul>
          <li><a href="#products">Branded Merchandise</a></li>
          <li><a href="#products">Promo &amp; Gifts</a></li>
          <li><a href="#products">Signs &amp; Outdoor</a></li>
          <li><a href="#products">Packaging</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Company</h5>
        <ul>
          <li><a href="#about">About Us</a></li>
          <li><a href="#clients">Our Clients</a></li>
          <li><a href="#why">Why Ambozy</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <span class="orange">Ambozy Graphics Solutions Ltd</span>. All rights reserved.</p>
      <p>Powered by <a href="https://thirdsan.com" target="_blank" rel="noopener noreferrer" style="color:var(--orange);transition:opacity .2s" onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">Thirdsan</a> — Building the Next Gen Digital Africa.</p>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
