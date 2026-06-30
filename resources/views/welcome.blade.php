<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKILL BRIDGE — Bridge the gap between talent and opportunity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --dark-bg: #0A1628;
            --darker-bg: #050B14;
            --card-bg: #0F1E35;
            --cyan: #00D9FF;
            --cyan-glow: rgba(0, 217, 255, 0.3);
            --text-primary: #FFFFFF;
            --text-secondary: #8B9CB5;
            --border: rgba(139, 156, 181, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Navbar */
        .navbar-custom {
            background: rgba(10, 22, 40, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 1.2rem 0;
        }
        
        .logo-box {
            width: 32px;
            height: 32px;
            background: var(--cyan);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: var(--darker-bg);
            font-size: 1rem;
        }
        
        .navbar-brand { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.15rem; color: var(--text-primary); text-decoration: none; letter-spacing: -0.01em; }
        .navbar-nav .nav-link { color: var(--text-secondary); font-weight: 500; font-size: 0.95rem; padding: 0.5rem 1rem; transition: color 0.3s; }
        .navbar-nav .nav-link:hover { color: var(--text-primary); }
        
        .btn-cyan { background: var(--cyan); color: var(--darker-bg); border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 700; font-size: 0.95rem; transition: all 0.3s; letter-spacing: -0.01em; }
        .btn-cyan:hover { background: #00C4E6; color: var(--darker-bg); transform: translateY(-2px); box-shadow: 0 8px 20px var(--cyan-glow); }
        
        /* Hero */
        .hero { padding: 6rem 0 4rem; background: linear-gradient(180deg, var(--darker-bg) 0%, var(--dark-bg) 100%); position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 800px; height: 800px; background: radial-gradient(circle, var(--cyan-glow) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
        
        .hero-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.2); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; color: var(--cyan); margin-bottom: 2rem; font-family: 'Courier New', monospace; }
        
        .hero-title { font-size: 4.5rem; font-weight: 900; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -0.03em; }
        .text-talent { background: linear-gradient(135deg, #00D9FF 0%, #0098FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-opportunity { color: var(--cyan); }
        
        .hero-subtitle { font-size: 1.1rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 2.5rem; line-height: 1.7; }
        
        /* Stats */
        .stats-section { padding: 4rem 0; background: var(--dark-bg); }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0, 217, 255, 0.15); }
        .stat-icon { width: 60px; height: 60px; margin: 0 auto 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-icon.cyan { background: rgba(0, 217, 255, 0.1); color: var(--cyan); }
        .stat-icon.purple { background: rgba(147, 51, 234, 0.1); color: #9333EA; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.1); color: #22C55E; }
        .stat-icon.orange { background: rgba(249, 115, 22, 0.1); color: #F97316; }
        .stat-number { font-size: 3.5rem; font-weight: 900; color: var(--text-primary); margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .stat-label { font-size: 0.95rem; color: var(--text-secondary); font-weight: 500; }
        
        /* Features */
        .features-section { padding: 5rem 0; background: var(--darker-bg); }
        .section-badge { text-transform: uppercase; letter-spacing: 3px; font-size: 0.7rem; color: var(--cyan); font-weight: 700; margin-bottom: 1rem; }
        .section-title { font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem; letter-spacing: -0.02em; line-height: 1.1; }
        .section-subtitle { font-size: 1.1rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 3rem; }
        
        .feature-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; transition: all 0.3s; height: 100%; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0, 217, 255, 0.15); }
        .feature-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.5rem; }
        .feature-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 0.75rem; letter-spacing: -0.01em; }
        .feature-desc { font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; }
        
        /* CTA */
        .cta-section { padding: 5rem 0; background: linear-gradient(180deg, var(--dark-bg) 0%, var(--darker-bg) 100%); }
        
        /* Footer */
        footer { background: var(--darker-bg); border-top: 1px solid var(--border); padding: 3rem 0 1.5rem; }
        footer h6 { color: var(--text-primary); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
        footer a { color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        footer a:hover { color: var(--cyan); }
        .copyright { color: var(--text-secondary); font-size: 0.85rem; padding-top: 2rem; border-top: 1px solid var(--border); margin-top: 2rem; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <div class="logo-box">SB</div>
                <span>SKILL BRIDGE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" style="border-color: var(--border);">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#companies">For Companies</a></li>
                    <li class="nav-item"><a class="nav-link" href="#analytics">Analytics</a></li>
                </ul>
                <div class="d-flex gap-3 align-items-center">
                    @auth
                        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="nav-link">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link">Log In</a>
                        <a href="{{ route('register') }}" class="btn btn-cyan">Get Started →</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero text-center" style="margin-top: 76px;">
        <div class="container position-relative">
            <div class="hero-badge">
                <i class="bi bi-stars"></i>
                Now serving 847 students across 12 partner universities
            </div>
            <h1 class="hero-title">
                Bridge the gap between<br>
                <span class="text-talent">talent</span> and <span class="text-opportunity">opportunity.</span>
            </h1>
            <p class="hero-subtitle">
                SKILL BRIDGE is the competency-based student development and internship<br>
                placement platform that connects universities, students, and employers<br>
                through verified skill intelligence.
            </p>
            <div class="d-flex gap-3 justify-content-center align-items-center flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-cyan">Start Free →</a>
                <button class="btn btn-outline-light" style="border-color: var(--text-secondary); color: var(--text-primary);">
                    <i class="bi bi-play-circle me-2"></i>Watch Demo
                </button>
            </div>
            <p class="mt-3" style="font-size: 0.85rem; color: var(--text-secondary);">
                No credit card required • Free for students
            </p>
            
            <!-- Dashboard Preview Mockup -->
            <div class="dashboard-preview mt-5">
                <div style="max-width: 900px; margin: 0 auto;">
                    <div style="background: var(--card-bg); border-radius: 12px 12px 0 0; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);">
                        <!-- Browser Header -->
                        <div style="background: rgba(15, 30, 53, 0.8); padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border);">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #FF5F57;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #FEBC2E;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #28C840;"></div>
                            <div style="flex: 1; text-align: center; font-size: 0.75rem; color: var(--text-secondary); font-family: 'Courier New', monospace;">
                                app.skillbridge.edu.ph/dashboard
                            </div>
                        </div>
                        <!-- Dashboard Content -->
                        <div style="background: var(--darker-bg); padding: 2rem;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                        <div class="logo-box">SB</div>
                                        <span style="font-weight: 700; font-size: 1rem;">SKILL BRIDGE</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; text-align: center;">
                                        <div style="font-size: 0.75rem; color: var(--cyan); text-transform: uppercase; margin-bottom: 0.5rem;">COMPETENCY</div>
                                        <div style="font-size: 2rem; font-weight: 900; color: var(--text-primary);">78<span style="font-size: 1.25rem; color: var(--text-secondary);">/100</span></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; text-align: center;">
                                        <div style="font-size: 0.75rem; color: var(--cyan); text-transform: uppercase; margin-bottom: 0.5rem;">APPLICATIONS</div>
                                        <div style="font-size: 2rem; font-weight: 900; color: var(--text-primary);">5 <span style="font-size: 1rem; color: var(--text-secondary);">active</span></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; text-align: center;">
                                        <div style="font-size: 0.75rem; color: var(--cyan); text-transform: uppercase; margin-bottom: 0.5rem;">MATCHING</div>
                                        <div style="font-size: 2rem; font-weight: 900; color: var(--text-primary);">87%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="bi bi-mortarboard-fill"></i></div>
                        <div class="stat-number">847</div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="bi bi-building-fill"></i></div>
                        <div class="stat-number">38</div>
                        <div class="stat-label">Partner Companies</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-number">94%</div>
                        <div class="stat-label">Placement Rate</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-bullseye"></i></div>
                        <div class="stat-number">81%</div>
                        <div class="stat-label">Avg Match Score</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="features-section text-center" id="how-it-works">
        <div class="container">
            <div class="section-badge">PROCESS</div>
            <h2 class="section-title">How SkillBridge works.</h2>
            <div class="row g-4 mt-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div style="display: inline-block; padding: 0.4rem 0.8rem; background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.3); border-radius: 6px; font-size: 0.75rem; color: var(--cyan); margin-bottom: 1.5rem; font-weight: 600;">student</div>
                        <h3 class="feature-title">Build Your Profile</h3>
                        <p class="feature-desc">Students complete a structured competency assessment to establish a verified skill baseline.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div style="display: inline-block; padding: 0.4rem 0.8rem; background: rgba(147, 51, 234, 0.1); border: 1px solid rgba(147, 51, 234, 0.3); border-radius: 6px; font-size: 0.75rem; color: #9333EA; margin-bottom: 1.5rem; font-weight: 600;">student</div>
                        <h3 class="feature-title">Get Matched</h3>
                        <p class="feature-desc">NEXUS finds open internships by compatibility score and surfaces the best-fit opportunities.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div style="display: inline-block; padding: 0.4rem 0.8rem; background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.3); border-radius: 6px; font-size: 0.75rem; color: var(--cyan); margin-bottom: 1.5rem; font-weight: 600;">student</div>
                        <h3 class="feature-title">Apply & Track</h3>
                        <p class="feature-desc">Submit one-click applications and monitor every stage of the hiring process in real-time.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div style="display: inline-block; padding: 0.4rem 0.8rem; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 6px; font-size: 0.75rem; color: #22C55E; margin-bottom: 1.5rem; font-weight: 600;">company</div>
                        <h3 class="feature-title">Post & Discover</h3>
                        <p class="feature-desc">Companies list opportunities, browse competency-ranked applicants, and receive a pre-ranked pool of competency-matched applicants.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Matching -->
    <section class="stats-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <div class="section-badge">SMART MATCHING</div>
                    <h2 class="section-title text-start">Stop guessing.<br>Start matching.</h2>
                    <p style="font-size: 1.05rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.7;">
                        SKILL BRIDGE scores every student against every listing in real time. The higher the match, the higher the placement success — verified across 38 partner companies.
                    </p>
                    <div class="mb-3" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-check-circle-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                        <span style="color: var(--text-secondary);">Competency score verified by faculty assessors</span>
                    </div>
                    <div class="mb-3" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-check-circle-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                        <span style="color: var(--text-secondary);">Skills cross-referenced against live job requirements</span>
                    </div>
                    <div class="mb-4" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-check-circle-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                        <span style="color: var(--text-secondary);">Match updates automatically as students grow</span>
                    </div>
                    <a href="{{ route('register') }}" class="btn btn-cyan">Browse Opportunities →</a>
                </div>
                <div class="col-lg-7 mt-4 mt-lg-0">
                    <div class="row g-3">
                        <div class="col-12">
                            <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 50px; height: 50px; background: var(--cyan); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem; color: var(--darker-bg);">SL</div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Frontend Dev Intern</div>
                                        <div style="font-size: 0.9rem; color: var(--text-secondary);"><i class="bi bi-building me-1"></i> Synapse Labs <span class="mx-2">•</span> <i class="bi bi-geo-alt me-1"></i> Makati City</div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.2); padding: 0.5rem 1rem; border-radius: 8px;">
                                        <i class="bi bi-star-fill" style="color: var(--cyan); font-size: 0.9rem;"></i>
                                        <span style="font-weight: 700; font-size: 1.1rem; color: var(--cyan);">94%</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">match</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 50px; height: 50px; background: #9333EA; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem; color: white;">CB</div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Data Analytics Intern</div>
                                        <div style="font-size: 0.9rem; color: var(--text-secondary);"><i class="bi bi-building me-1"></i> CloudBridge Inc <span class="mx-2">•</span> <i class="bi bi-geo-alt me-1"></i> BGC, Taguig</div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(147, 51, 234, 0.1); border: 1px solid rgba(147, 51, 234, 0.2); padding: 0.5rem 1rem; border-radius: 8px;">
                                        <i class="bi bi-star-fill" style="color: #9333EA; font-size: 0.9rem;"></i>
                                        <span style="font-weight: 700; font-size: 1.1rem; color: #9333EA;">87%</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">match</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 50px; height: 50px; background: #22C55E; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem; color: white;">FE</div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">Full-Stack Intern</div>
                                        <div style="font-size: 0.9rem; color: var(--text-secondary);"><i class="bi bi-building me-1"></i> FirstEdge Solutions <span class="mx-2">•</span> <i class="bi bi-geo-alt me-1"></i> Ortigas, Pasig</div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); padding: 0.5rem 1rem; border-radius: 8px;">
                                        <i class="bi bi-star-fill" style="color: #22C55E; font-size: 0.9rem;"></i>
                                        <span style="font-weight: 700; font-size: 1.1rem; color: #22C55E;">81%</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">match</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <a href="{{ route('register') }}" style="color: var(--cyan); text-decoration: none; font-size: 0.95rem; font-weight: 600;">
                                View all 124 open positions →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Analytics Section -->
    <section class="features-section" id="analytics">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 order-lg-2">
                    <div class="section-badge">ADMIN ANALYTICS</div>
                    <h2 class="section-title text-start">Data that drives better<br>decisions.</h2>
                    <p style="font-size: 1.05rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.7;">
                        Program administrators get real-time dashboards on student placements, skill development trends, and industry alignment — with export-ready reports for accreditation.
                    </p>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-graph-up" style="color: var(--cyan); font-size: 1.25rem;"></i>
                                <span style="color: var(--text-secondary); font-size: 0.95rem;">Placement dashboards</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-lightning-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                                <span style="color: var(--text-secondary); font-size: 0.95rem;">Skill trend reports</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-exclamation-triangle-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                                <span style="color: var(--text-secondary); font-size: 0.95rem;">Curriculum gap alerts</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-award-fill" style="color: var(--cyan); font-size: 1.25rem;"></i>
                                <span style="color: var(--text-secondary); font-size: 0.95rem;">Accreditation exports</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 order-lg-1 mb-5 mb-lg-0">
                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
                        <div style="display: flex; align-items: center; justify-content: between; margin-bottom: 1.5rem;">
                            <div>
                                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem;">Monthly Placements</h4>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Jan - Jun 2025</div>
                            </div>
                            <div style="margin-left: auto; color: #22C55E; font-weight: 600; font-size: 0.9rem;"><i class="bi bi-arrow-up"></i> +14.6% YoY</div>
                        </div>
                        <canvas id="placementChart" style="max-height: 250px;"></canvas>
                        <div class="row g-3 mt-3">
                            <div class="col-6">
                                <div style="background: var(--darker-bg); border: 1px solid var(--border); border-radius: 8px; padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <i class="bi bi-percent" style="color: var(--cyan);"></i>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase;">Placement Rate</span>
                                    </div>
                                    <div style="font-size: 2rem; font-weight: 900; margin-bottom: 0.25rem;">94%</div>
                                    <div style="font-size: 0.85rem; color: #22C55E;">+23% vs last year</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background: var(--darker-bg); border: 1px solid var(--border); border-radius: 8px; padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <i class="bi bi-clock" style="color: var(--cyan);"></i>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase;">Avg. Time to Place</span>
                                    </div>
                                    <div style="font-size: 2rem; font-weight: 900; margin-bottom: 0.25rem;">18 days</div>
                                    <div style="font-size: 0.85rem; color: #22C55E;">9 days vs last year</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="stats-section">
        <div class="container">
            <div class="section-badge text-center">TESTIMONIALS</div>
            <h2 class="section-title text-center">Trusted by students, faculty, and employers.</h2>
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
                        <div style="color: var(--cyan); font-size: 1.25rem; margin-bottom: 1rem;">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.7; margin-bottom: 1.5rem;">
                            "SKILL BRIDGE cut our screening time by 60%. The competency scores are so accurate that the interns we hired through the platform outperformed all previous cohorts."
                        </p>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; background: var(--cyan); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--darker-bg);">MS</div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">Maria Santos</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">HR Director, Synapse Labs</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
                        <div style="color: var(--cyan); font-size: 1.25rem; margin-bottom: 1rem;">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.7; margin-bottom: 1.5rem;">
                            "I landed my internship at a Fortune 500 company three weeks after joining NEXUS. The skill gap analysis told me exactly what to work on first."
                        </p>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; background: #9333EA; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: white;">RL</div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">Rafael Lim</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">BS Computer Science, FEU Tech</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
                        <div style="color: var(--cyan); font-size: 1.25rem; margin-bottom: 1rem;">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.7; margin-bottom: 1.5rem;">
                            "Our placement rate jumped from 74% to 94% in one academic year. The admin analytics dashboard alone is worth the entire platform."
                        </p>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; background: #22C55E; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: white;">AR</div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">Dr. Anna Reyes</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Dean, College of Engineering</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section text-center" id="features">
        <div class="container">
            <div class="section-badge">PLATFORM FEATURES</div>
            <h2 class="section-title">Everything you need to go<br>from campus to career.</h2>
            <p class="section-subtitle">
                Built for universities, students, and employers — Skill Bridge handles<br>
                the full placement lifecycle in one coherent system.
            </p>
            <div class="row g-4 mt-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon cyan"><i class="bi bi-bullseye"></i></div>
                        <h3 class="feature-title">Competency Mapping</h3>
                        <p class="feature-desc">AI-powered skill gap analysis that maps each student's competencies to real industry requirements, generating a live readiness score.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon purple" style="background: rgba(147, 51, 234, 0.1); color: #9333EA;"><i class="bi bi-briefcase-fill"></i></div>
                        <h3 class="feature-title">Smart Internship Matching</h3>
                        <p class="feature-desc">Proprietary matching algorithm pairs students with partner companies based on verified skills, course alignment, and career goals.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon green" style="background: rgba(34, 197, 94, 0.1); color: #22C55E;"><i class="bi bi-building"></i></div>
                        <h3 class="feature-title">Company Dashboard</h3>
                        <p class="feature-desc">Employers post opportunities, browse competency-ranked applicants, and manage their entire recruiting pipeline from a single portal.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon orange" style="background: rgba(249, 115, 22, 0.1); color: #F97316;"><i class="bi bi-bar-chart-fill"></i></div>
                        <h3 class="feature-title">Admin Analytics</h3>
                        <p class="feature-desc">Institutional leaders get a real-time view of placement rates, skill trends, and program effectiveness across every cohort.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(236, 72, 153, 0.1); color: #EC4899;"><i class="bi bi-shield-fill-check"></i></div>
                        <h3 class="feature-title">Verified Credentials</h3>
                        <p class="feature-desc">Digital badges and skill certificates are issued on completion, giving students portable, employer-trusted proof of their abilities.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <h3 class="feature-title">Industry-Aligned Curriculum</h3>
                        <p class="feature-desc">Skill frameworks auto-sync with current industry demand, keeping competency targets relevant and defensible for accreditation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-center" id="companies">
        <div class="container">
            <div class="section-badge">GET STARTED TODAY</div>
            <h2 class="section-title">Ready to build the<br>future workforce?</h2>
            <p class="section-subtitle">
                Join 847 students and 38 companies already using NEXUS to<br>
                close the skills gap between university and industry.
            </p>
            <div class="d-flex gap-3 justify-content-center mt-4">
                <a href="{{ route('register') }}" class="btn btn-cyan">Register as Student →</a>
                <button class="btn btn-outline-light" style="border-color: var(--text-secondary); color: var(--text-primary);">Partner with Us</button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a class="navbar-brand mb-3 d-inline-flex" href="{{ route('home') }}">
                        <div class="logo-box">SB</div>
                        <span style="margin-left: 0.6rem;">SKILL BRIDGE</span>
                    </a>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; max-width: 300px; margin-top: 1rem;">
                        Competency-Based Student Development and Internship Placement System. Bridging talent and opportunity across the Philippines.
                    </p>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-4">
                    <h6>PLATFORM</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features">Features</a></li>
                        <li class="mb-2"><a href="#how-it-works">How It Works</a></li>
                        <li class="mb-2"><a href="#pricing">Pricing</a></li>
                        <li class="mb-2"><a href="#security">Security</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-4">
                    <h6>FOR USERS</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('register') }}">Students</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}">Companies</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}">Universities</a></li>
                        <li class="mb-2"><a href="{{ route('login') }}">Admin</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-4">
                    <h6>COMPANY</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#about">About SKILL BRIDGE</a></li>
                        <li class="mb-2"><a href="#contact">Contact</a></li>
                        <li class="mb-2"><a href="#privacy">Privacy</a></li>
                        <li class="mb-2"><a href="#terms">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright text-center">
                <p class="mb-0">© {{ date('Y') }}, Team NEXUS. All rights reserved. CDSP10 — Academic Year 2024-2025</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Placement Chart
        const ctx = document.getElementById('placementChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Placements',
                        data: [65, 78, 85, 92, 98, 105],
                        backgroundColor: '#00D9FF',
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0F1E35',
                            titleColor: '#FFFFFF',
                            bodyColor: '#8B9CB5',
                            borderColor: '#00D9FF',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(139, 156, 181, 0.1)' },
                            ticks: { color: '#8B9CB5' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#8B9CB5' }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
