# Ambozy Graphics Solutions Ltd — Website Project

> **Live URL:** [ambozygraphics.shop](http://ambozygraphics.shop)
> **Stack:** PHP · HTML5 · CSS3 · JavaScript · MySQL (CMS phase)
> **Deployment:** FTP

---

## 1. Company Profile

| Field | Detail |
|---|---|
| **Full Name** | Ambozy Graphics Solutions Ltd |
| **Incorporated** | 2010 |
| **Address** | Plot 43 Nasser / Nkrumah Road, Opposite Picfare, Kampala, Uganda |
| **P.O. Box** | 14521, Kampala – Uganda |
| **Office** | +256-392-839-447 |
| **Mobile** | +256-782-187-799 / +256-702-371-230 |
| **Email** | ambozygraphics@gmail.com · ambozygraphics@yahoo.com |
| **Info Email** | info@ambozygroup.com |

### Vision
> To maintain and be the acknowledged leader in designing, printing, branding and promotional services through consistent improvement of quality.

### Mission
> To provide exceptional designing, printing, branding and promotional services and to deliver individualized solutions to our clients in a way that adds value to all our stake holders.

### Core Values
1. Service
2. Premium Quality
3. Professionalism
4. Exceptional Value
5. Integrity
6. Creativity and Innovation
7. Teamwork

---

## 2. Brand Identity

### Logo
Primary logo file: `logo main.png`

Logo variants (from `logos.jpeg` / `logos.pdf`):
- Light background (black + orange)
- Dark/Black background (white + orange)
- Red background (white + orange)
- Monochrome (black only)

Tagline shown in full logo: **PRINTING | DESIGNING | CONTRACTORS | GENERAL SUPPLIES**

### Color System

| Role | Name | HEX | RGB | CMYK |
|---|---|---|---|---|
| **Primary** | Ambozy Orange | `#F15E24` | 241, 94, 36 | 0 / 78.52 / 98.05 / 0 |
| **Secondary** | Deep Black | `#010101` | 1, 1, 1 | 74.61 / 67.58 / 66.8 / 89.84 |
| **Neutral** | Pure White | `#FFFFFF` | 255, 255, 255 | — |
| **Accent** | Charcoal | `#1A1A1A` | 26, 26, 26 | — (UI use) |
| **Light BG** | Off-White | `#F8F5F2` | 248, 245, 242 | — (UI use) |

### Typography (recommended)
- **Display / Headings:** Montserrat ExtraBold / Black (matches logo weight)
- **Body:** Inter or Open Sans Regular / Light
- **Accent / Tagline:** Montserrat Medium, letter-spacing: 0.15em, uppercase

---

## 3. Services & Product Catalogue

### Core Service Pillars (logo tagline)
| # | Service |
|---|---|
| 1 | **Printing** |
| 2 | **Designing** |
| 3 | **Contractors** |
| 4 | **General Supplies** |

### Full Product & Service Categories

#### Branded Merchandise
- Round Neck T-shirts, Polo Shirts, Business Shirts (Short & Long Sleeve)
- Caps / Hats (Baseball, Bucket, Working)
- Aprons, Fleece, Overalls
- Bags (Drawstring, Backpack, Tote, Market, Fitness, Document, Shoulder)

#### Branded Giveaways & Promo Gifts
- Keyrings (Aluminium, PVC – custom shapes & sizes)
- Thermal Mugs, Ceramics, Coffee Cups
- Wall Clocks, Desk Clocks, Watches
- Promotional Pens & Pen Sets
- Customised USB Flash Disks (Wooden, Rubber, Business-card style)
- Mouse Pads, Customised Umbrellas
- Wristbands, Water Bottles & Flasks

#### Books & Magazines
- Magazines, Short Runs, Invitation Cards, Certificates
- Visiting Cards, Greeting Cards, Textbooks, Annual Reports, Newsletters, Presentations

#### Stationery
- Corporate & Computer Stationery, Letterheads, Envelopes, Customised Books

#### Marketing & Print
- Posters, Brochures, Leaflets, Catalogues, Company Profiles
- Banners, Direct Mailers, File Folders, Calendars, Diaries & Notebooks
- Car Branding, Lanyards & Badge Holders, Business Card Holders

#### Signs & Outdoor Advertising
- Neon Signs, Illuminated Signs, Pavement Signs, Light Boxes
- Wall Signs, Wall Painting, Pull-ups, L-Banners / Tear Drops
- Backdrops, Door Labels, Acrylic Signs, Table Stands

#### Point of Sale Materials
- Wobblers, Shelf Strips, Danglers, POS Displays

#### Packaging Solutions
- Product Labels, Inner Packaging, Luggage Tags, Shopping Bags
- Kraft Paper, Craft Packaging, Boxes, Paper Cups

#### Others
- Event Tickets, Postcards, Menus

> *"Only mention it and it will be availed"* — Ambozy's commitment to custom solutions.

### Key Clients (Reference Portfolio)
- Ministry of Trade, Industry and Co-operatives (Uganda)
- Ministry of Water and Environment (Uganda)
- Dooba Enterprises Ltd
- Young People and Adolescent Peer Support – YAPS (Health sector)

---

## 4. Project Phases

### Phase 1 — Landing Page (PHP) `[CURRENT]`

A stunning, professional marketing landing page. Single file PHP entry point served on `ambozygraphics.shop`.

**Sections / Structure:**

```
├── Header / Navbar
│   ├── Logo (logo main.png)
│   ├── Navigation links (smooth scroll)
│   └── CTA button: "Get a Quote"
│
├── Hero Section
│   ├── Full-screen or tall hero with bold headline
│   ├── Animated tagline (Printing | Designing | Contractors | General Supplies)
│   └── Dual CTA: "Our Services" + "Contact Us"
│
├── About Section
│   ├── Brief company story (est. 2010)
│   ├── Vision & Mission
│   └── Core Values grid (7 values)
│
├── Services Section
│   ├── Service cards with icons (4 core pillars)
│   └── Expandable full product categories
│
├── Portfolio / Gallery
│   └── Product showcase grid (pulled from profile imagery)
│
├── Clients Section
│   └── Client logos / trust badges
│
├── Testimonials (optional placeholder)
│
├── Contact Section
│   ├── Contact form (PHP mail handler)
│   ├── Map embed (Plot 43, Nasser Rd, Kampala)
│   └── All contact details
│
└── Footer
    ├── Logo + tagline
    ├── Quick links
    └── Social / contact info
```

**Technical spec:**
- PHP 7.4+ (no framework dependency)
- Vanilla CSS with CSS custom properties (brand tokens)
- Vanilla JS — smooth scroll, mobile menu, animation on scroll
- PHP contact form handler with basic validation
- Mobile-first, fully responsive
- Optimised images, fast load time

**File structure (Phase 1):**
```
public/
├── index.php
├── contact.php          # form handler
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
│       ├── logo-main.png
│       ├── logo-dark.png
│       └── gallery/
└── .htaccess
```

---

### Phase 2 — Client Management CMS `[UPCOMING]`

A private admin panel for Ambozy to receive, track and manage client enquiries and orders.

**Features planned:**
- Admin login (secure session-based auth)
- Client enquiry inbox (linked from contact form)
- Order/project tracker (status: New → In Progress → Completed)
- Client records (name, company, email, phone, service requested)
- Quote builder / PDF export
- Dashboard with stats (total clients, active projects, completed jobs)
- File upload (client brief / artwork files)

**Technical spec:**
- PHP + MySQL (PDO)
- Admin UI: clean, responsive dashboard (custom CSS or lightweight framework)
- Password hashing: `password_hash()` / `password_verify()`
- CSRF protection on all forms
- Input sanitisation / prepared statements throughout

**File structure (Phase 2 addition):**
```
admin/
├── index.php            # login
├── dashboard.php
├── clients.php
├── enquiries.php
├── orders.php
├── quote-builder.php
├── includes/
│   ├── auth.php
│   ├── db.php
│   └── functions.php
└── assets/
    ├── css/admin.css
    └── js/admin.js
db/
└── ambozy.sql           # schema
```

---

### Phase 3 — FTP Deployment `[UPCOMING]`

Deploy to `ambozygraphics.shop` via FTP.

- Upload `public/` contents to web root
- Set up `.htaccess` for clean URLs and security headers
- Configure PHP mail (SMTP or server sendmail) for contact form
- DB provisioning for Phase 2 CMS

---

## 5. Design Tokens (CSS Variables)

```css
:root {
  /* Brand Colors */
  --color-primary:     #F15E24;   /* Ambozy Orange */
  --color-primary-dark:#C94A12;   /* Orange hover/dark state */
  --color-black:       #010101;   /* Deep Black */
  --color-charcoal:    #1A1A1A;   /* Section backgrounds */
  --color-white:       #FFFFFF;
  --color-off-white:   #F8F5F2;   /* Light section BG */
  --color-grey:        #6B6B6B;   /* Body text muted */

  /* Typography */
  --font-display:      'Montserrat', sans-serif;
  --font-body:         'Inter', sans-serif;

  /* Spacing scale */
  --space-xs:  0.5rem;
  --space-sm:  1rem;
  --space-md:  2rem;
  --space-lg:  4rem;
  --space-xl:  8rem;

  /* Radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 16px;

  /* Shadows */
  --shadow-card: 0 4px 24px rgba(1,1,1,0.08);
  --shadow-lift: 0 8px 40px rgba(241,94,36,0.15);
}
```

---

## 6. Development Workflow

```
main                    → production-ready code
claude/review-ambozy-profile-ckwOc  → active development branch
```

1. All development happens on `claude/review-ambozy-profile-ckwOc`
2. Each phase merges to `main` before FTP deployment
3. Assets committed to repo; FTP push mirrors the `public/` directory

---

## 7. Asset Inventory

| File | Type | Purpose |
|---|---|---|
| `logo main.png` | PNG | Primary logo — light backgrounds |
| `Logo.png` | PNG | Full logo with service tagline |
| `logos.jpeg` | JPEG | All logo variants + brand colors reference |
| `logos.pdf` | PDF | Vector logo variants (print quality) |
| `Ambozy Profile.pdf` | PDF | Full company profile (41 pages) |

---

*Project initiated April 2026. Built with precision — just like Ambozy.*
