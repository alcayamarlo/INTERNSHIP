# Figma Design Implementation Guide

## Design Specifications from Figma

### Color Palette
```css
--dark-bg: #0A1628;          /* Main dark navy background */
--darker-bg: #050B14;         /* Darker sections */
--card-bg: #0F1E35;           /* Card/Dashboard backgrounds */
--cyan-primary: #00D9FF;      /* Primary cyan accent */
--cyan-glow: rgba(0, 217, 255, 0.3);  /* Glow effects */
--text-primary: #FFFFFF;      /* White text */
--text-secondary: #8B9CB5;    /* Muted text */
--border-color: rgba(139, 156, 181, 0.1);  /* Subtle borders */
```

### Typography
- **Font Family**: Inter (from Google Fonts)
- **Hero Title**: 72px (4.5rem), Weight 900
- **Section Titles**: 48px (3rem), Weight 800
- **Body Text**: 16px (1rem), Weight 400-500
- **Small Text**: 14px (0.875rem)

### Key Design Elements

#### 1. Navigation Bar
- Dark background with blur effect
- Logo: Cyan square with "SB" text
- Navigation links: Features, How It Works, For Companies, Analytics, Pricing
- Right side: "Log In" link + "Get Started" cyan button
- Sticky on scroll

#### 2. Hero Section
- **Badge**: "Now serving 847 students across 12 partner universities" (cyan with code font)
- **Title**: "Bridge the gap between **talent** and **opportunity**."
  - "talent" has cyan gradient
  - "opportunity" is solid cyan
- **Subtitle**: Descriptive text in muted color
- **CTAs**: "Start Free" (cyan button) + "Watch Demo" (outline button with play icon)
- **Small text**: "No credit card required • Free for students"

#### 3. Dashboard Preview
- Browser mockup with three dots (red, yellow, green)
- Shows mini dashboard with:
  - Sidebar: Dashboard, Internships, Skills, Analytics
  - Stats cards: Competency 78/100, Applications 5 active, Matching 87%
  - Skill Graph: Hexagonal radar chart
  - Placement Trend: Bar chart (cyan bars)

#### 4. Statistics Section
- 4 cards in a row:
  - 847 Active Students (graduation cap icon)
  - 38 Partner Companies (building icon)
  - 94% Placement Rate (trending up icon)
  - 81% Avg Match Score (target icon)
- Icons in colored circles, large numbers, small labels

#### 5. Platform Features Section
- **Heading**: "PLATFORM FEATURES"
- **Title**: "Everything you need to go from campus to career."
- **Subtitle**: Descriptive text
- **6 Feature Cards** in 2 rows:
  1. Competency Mapping (target icon, cyan)
  2. Smart Internship Matching (briefcase icon, purple)
  3. Company Dashboard (building icon, green)
  4. Admin Analytics (chart icon, orange)
  5. Verified Credentials (shield icon, pink)
  6. Industry-Aligned Curriculum (document icon, blue)

#### 6. How It Works Section
- **Heading**: "PROCESS"
- **Title**: "How SkillBridge works."
- **4 Step Cards**:
  1. Build Your Profile (student badge)
  2. Get Matched (NEXUS badge)
  3. Apply & Track (student badge)
  4. Post & Discover (company badge)
- Each card has icon, title, description

#### 7. Smart Matching Section
- **Heading**: "SMART MATCHING"
- **Title**: "Stop guessing. Start matching."
- **Description** with bullet points (checkmarks in cyan)
- **Internship Cards** showing:
  - Colored badge (SL, CB, FE)
  - Position title
  - Company + Location
  - Match percentage (star + percent, e.g., "⭐ 94% match")
- "Browse Opportunities" button
- "View all 124 open positions" link

#### 8. Admin Analytics Section
- **Heading**: "ADMIN ANALYTICS"
- **Title**: "Data that drives better decisions."
- **Description**
- **Bar Chart**: Monthly Placements (Jan-Jun 2025, cyan bars, +14.6% YoY)
- **Metric Cards**:
  - Placement Rate: 94% (+23% vs last year)
  - Avg. Time to Place: 18 days (9 days vs last year)
- **Feature List**:
  - Placement dashboards
  - Skill trend reports
  - Curriculum gap alerts
  - Accreditation exports

#### 9. Testimonials Section
- **Heading**: "TESTIMONIALS"
- **Title**: "Trusted by students, faculty, and employers."
- **3 Testimonial Cards**:
  - 5 stars rating
  - Quote text
  - Avatar (colored circle with initials)
  - Name + Role

#### 10. CTA Section
- **Heading**: "GET STARTED TODAY"
- **Title**: "Ready to build the future workforce?"
- **Description**: "Join 847 students and 38 companies already using NEXUS..."
- **Buttons**: "Register as Student" (cyan) + "Partner with Us" (outline)

#### 11. Footer
- **Logo + Description**
- **3 Columns**:
  - PLATFORM: Features, How It Works, Pricing, Security
  - FOR USERS: Students, Companies, Universities, Admin
  - COMPANY: About SKILL BRIDGE, Contact, Privacy, Terms
- **Copyright**: "© 2025, Team NEXUS. All rights reserved. CDSP10 - Academic Year 2024-2025"

### Component Styles

#### Cards
```css
background: var(--card-bg);
border: 1px solid var(--border-color);
border-radius: 12px;
padding: 2rem;
transition: transform 0.3s, box-shadow 0.3s;
```

#### Hover Effects
```css
transform: translateY(-4px);
box-shadow: 0 12px 40px rgba(0, 217, 255, 0.15);
```

#### Buttons
```css
/* Primary Cyan */
background: var(--cyan-primary);
color: var(--darker-bg);
padding: 0.75rem 2rem;
border-radius: 8px;
font-weight: 600;
transition: all 0.3s;

/* Hover */
background: #00C4E6;
transform: translateY(-2px);
box-shadow: 0 8px 20px var(--cyan-glow);
```

## Implementation Steps

### Step 1: Update Color Variables
Replace the CSS variables in all layout files with the new dark theme colors.

### Step 2: Add Inter Font
Include Google Fonts Inter in all pages:
```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
```

### Step 3: Update Navigation
- Change to dark sticky navigation
- Add blur backdrop effect
- Use cyan button for "Get Started"

### Step 4: Recreate Hero Section
- Large typography with gradient text
- Add badge above title
- Dashboard preview mockup
- CTA buttons

### Step 5: Build Statistics Section
- Icon cards with large numbers
- Responsive grid (4 columns → 2 → 1)

### Step 6: Feature Cards
- Grid layout with icons
- Hover animations
- Colored icon backgrounds

### Step 7: Charts Integration
- Use Chart.js for bar charts
- Create hexagonal radar chart
- Cyan color scheme

### Step 8: Testimonials
- Card layout with avatars
- 5-star ratings
- Circular colored badges

### Step 9: Footer
- Multi-column layout
- Links organization
- Copyright text

## Quick Implementation

I'll now create the complete files with this design implemented.
