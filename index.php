<?php
require_once 'config/session.php';

// Redirect if logged in - PRESERVED EXACTLY
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

// Set AdminLTE page variables
$page_title = 'Aura Luxe Resort - Luxury Cottage Reservation';
$is_landing_page = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
    
    <!-- Slick Carousel CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    
    <style>
        :root {
            /* Resort Color Palette */
            --primary-turquoise: #40E0D0;
            --primary-turquoise-dark: #36c9b9;
            --secondary-aqua: #00BFFF;
            --background-cream: #FFF5E1;
            --accent-coral: #FF6F61;
            --accent-coral-dark: #ff5a4d;
            --accent-yellow: #FFD700;
            --text-dark: #333333;
            --text-light: #666666;
            --white: #ffffff;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        /* Global Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: var(--white);
            overflow-x: hidden;
        }
        
        .resort-section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            color: var(--text-dark);
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2:after {
            content: '';
            position: absolute;
            width: 70px;
            height: 4px;
            background: var(--primary-turquoise);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .section-title p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Navigation Header */
        .resort-header {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 20px var(--shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 15px 0;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-turquoise) !important;
        }
        
        .navbar-brand i {
            color: var(--accent-yellow);
            margin-right: 8px;
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            padding: 10px 20px !important;
            transition: all 0.3s ease;
            border-radius: 4px;
            margin: 0 5px;
        }
        
        .nav-link:hover {
            background-color: var(--background-cream);
            color: var(--primary-turquoise) !important;
        }
        
        .nav-link.active {
            background-color: var(--primary-turquoise);
            color: var(--white) !important;
        }
        
        .nav-link.active:hover {
            background-color: var(--primary-turquoise-dark);
        }
        
        /* Auth Buttons in Navbar */
        .nav-auth-btn {
            padding: 8px 20px !important;
            border-radius: 30px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .btn-login-nav {
            background-color: transparent;
            border: 2px solid var(--primary-turquoise);
            color: var(--primary-turquoise) !important;
        }
        
        .btn-login-nav:hover {
            background-color: var(--primary-turquoise);
            color: var(--white) !important;
        }
        
        .btn-register-nav {
            background-color: var(--accent-coral);
            border: 2px solid var(--accent-coral);
            color: var(--white) !important;
        }
        
        .btn-register-nav:hover {
            background-color: var(--accent-coral-dark);
            border-color: var(--accent-coral-dark);
            color: var(--white) !important;
        }
        
        /* Hero Section */
        .hero-section {
            height: 100vh;
            position: relative;
            overflow: hidden;
            margin-top: 70px;
        }
        
        .hero-slider {
            height: 100%;
        }
        
        .hero-slide {
            height: 100vh;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .hero-slide:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.4));
        }
        
        .hero-content {
            position: absolute;
            top: 50%;
            left: 10%;
            transform: translateY(-50%);
            color: var(--white);
            z-index: 2;
            width: 90%;
            max-width: 600px;
            text-align: left;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        /* Hero CTA Buttons - HIGHLIGHTED */
        .hero-cta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .btn-hero-primary {
            background-color: var(--primary-turquoise);
            border: 2px solid var(--primary-turquoise);
            color: var(--white);
            padding: 15px 35px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(64, 224, 208, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: pulse 2s infinite;
        }
        
        .btn-hero-primary:hover {
            background-color: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(64, 224, 208, 0.6);
        }
        
        .btn-hero-secondary {
            background-color: var(--accent-coral);
            border: 2px solid var(--accent-coral);
            color: var(--white);
            padding: 15px 35px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 111, 97, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-hero-secondary:hover {
            background-color: var(--accent-coral-dark);
            border-color: var(--accent-coral-dark);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(255, 111, 97, 0.6);
        }
        
        /* Hero Features */
        .hero-features {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 40px;
        }
        
        .hero-feature {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .hero-feature i {
            font-size: 2rem;
            color: var(--accent-yellow);
        }
        
        .hero-feature span {
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Rooms Section */
        .rooms-section {
            background-color: var(--background-cream);
        }
        
        .room-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--shadow);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .room-img {
            height: 250px;
            background-size: cover;
            background-position: center;
        }
        
        .room-content {
            padding: 25px;
        }
        
        .room-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .room-price {
            color: var(--accent-coral);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .room-price span {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 400;
        }
        
        .room-features {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .room-features li {
            margin-bottom: 8px;
            color: var(--text-light);
        }
        
        .room-features i {
            color: var(--accent-yellow);
            margin-right: 8px;
        }
        
        /* View Details Button */
        .btn-view-details {
            background-color: transparent;
            border: 2px solid var(--primary-turquoise);
            color: var(--primary-turquoise);
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-view-details:hover {
            background-color: var(--primary-turquoise);
            color: var(--white);
        }
        
        /* Call to Action Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-turquoise) 0%, var(--secondary-aqua) 100%);
            color: var(--white);
            padding: 100px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }
        
        .btn-cta-primary {
            background-color: var(--white);
            color: var(--primary-turquoise);
            border: 2px solid var(--white);
            padding: 15px 40px;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-cta-primary:hover {
            background-color: transparent;
            color: var(--white);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(255, 255, 255, 0.4);
        }
        
        /* Packages Section */
        .package-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow);
            transition: all 0.3s ease;
            text-align: center;
            height: 100%;
            border: 2px solid transparent;
            position: relative;
        }
        
        .package-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-turquoise);
            box-shadow: 0 10px 25px rgba(64, 224, 208, 0.2);
        }
        
        .package-badge {
            background: var(--accent-coral);
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .package-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--white);
            font-size: 2rem;
        }
        
        .package-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        .package-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-turquoise);
            margin-bottom: 20px;
        }
        
        /* Testimonials */
        .testimonials-section {
            background-color: var(--white);
        }
        
        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .testimonial-card {
            background: var(--background-cream);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin: 0 15px;
            box-shadow: 0 5px 20px var(--shadow);
        }
        
        .testimonial-text {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 25px;
            font-style: italic;
            line-height: 1.6;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: var(--primary-turquoise);
            margin-bottom: 5px;
        }
        
        .testimonial-rating {
            color: var(--accent-yellow);
            margin-bottom: 10px;
        }
        
        /* About Section */
        .about-section {
            background-color: var(--background-cream);
        }
        
        .about-content {
            display: flex;
            align-items: center;
            gap: 50px;
        }
        
        .about-text {
            flex: 1;
        }
        
        .about-image {
            flex: 1;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px var(--shadow);
        }
        
        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .about-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-turquoise);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
        }
        
        /* Footer */
        .resort-footer {
            background: linear-gradient(135deg, #1a2a3a, #2c3e50);
            color: var(--white);
            padding: 70px 0 20px;
        }
        
        .footer-widget h4 {
            color: var(--white);
            font-size: 1.3rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-widget h4:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-turquoise);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-turquoise);
            padding-left: 5px;
        }
        
        .contact-info li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .contact-info i {
            color: var(--primary-turquoise);
            margin-right: 10px;
            width: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--primary-turquoise);
            transform: translateY(-3px);
        }
        
        .newsletter-form {
            display: flex;
            margin-top: 20px;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 5px 0 0 5px;
            outline: none;
        }
        
        .newsletter-form button {
            background: var(--accent-coral);
            color: var(--white);
            border: none;
            padding: 0 20px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background: var(--accent-coral-dark);
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        /* Animations */
        @keyframes pulse {
            0% {
                box-shadow: 0 8px 25px rgba(64, 224, 208, 0.4);
            }
            50% {
                box-shadow: 0 8px 30px rgba(64, 224, 208, 0.7);
            }
            100% {
                box-shadow: 0 8px 25px rgba(64, 224, 208, 0.4);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content {
                left: 5%;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .about-content {
                flex-direction: column;
            }
            
            .about-features {
                grid-template-columns: 1fr;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .cta-section h2 {
                font-size: 2.2rem;
            }
            
            .nav-auth-btn {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .btn-hero-primary,
            .btn-hero-secondary {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .hero-features {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg resort-header">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-umbrella-beach"></i>Aura Luxe Resort
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#rooms">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#packages">Packages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a href="auth/login.php" class="nav-link btn-login-nav nav-auth-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="auth/register.php" class="nav-link btn-register-nav nav-auth-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Slider -->
    <section class="hero-section">
        <div class="hero-slider">
            <!-- Slide 1 - Login Focus -->
            <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="hero-content">
                    <h1>Welcome to Aura Luxe</h1>
                    <p>Access your account to manage bookings, view reservations, and enjoy exclusive member benefits</p>
                    
                    <div class="hero-cta">
                        <a href="auth/login.php" class="btn btn-hero-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Account
                        </a>
                        <a href="auth/register.php" class="btn btn-hero-secondary">
                            <i class="fas fa-user-plus"></i> Create New Account
                        </a>
                    </div>
                    
                    <div class="hero-features">
                        <div class="hero-feature">
                            <i class="fas fa-key"></i>
                            <span>Secure Account Access</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-calendar-check"></i>
                            <span>Manage Your Bookings</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-star"></i>
                            <span>Exclusive Member Benefits</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 - Registration Focus -->
            <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="hero-content">
                    <h1>Join Our Luxury Community</h1>
                    <p>Register now to unlock special offers, early booking privileges, and personalized vacation planning</p>
                    
                    <div class="hero-cta">
                        <a href="auth/register.php" class="btn btn-hero-primary">
                            <i class="fas fa-user-plus"></i> Create Free Account
                        </a>
                        <a href="#rooms" class="btn btn-hero-secondary">
                            <i class="fas fa-eye"></i> Browse Rooms First
                        </a>
                    </div>
                    
                    <div class="hero-features">
                        <div class="hero-feature">
                            <i class="fas fa-gift"></i>
                            <span>Welcome Bonus: 10% Off First Stay</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-clock"></i>
                            <span>Early Access to Sales</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-headset"></i>
                            <span>Priority Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms" class="resort-section rooms-section">
        <div class="container">
            <div class="section-title">
                <h2>Our Luxury Cottages</h2>
                <p>Experience comfort and elegance in our carefully designed accommodations</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="room-card">
                        <div class="room-img" style="background-image: url('https://images.unsplash.com/photo-1613977257363-707ba9348227?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');"></div>
                        <div class="room-content">
                            <h3 class="room-title">Ocean View Suite</h3>
                            <div class="room-price">$299 <span>/ night</span></div>
                            <ul class="room-features">
                                <li><i class="fas fa-check"></i> Panoramic ocean view</li>
                                <li><i class="fas fa-check"></i> King size bed</li>
                                <li><i class="fas fa-check"></i> Private balcony</li>
                                <li><i class="fas fa-check"></i> Jacuzzi</li>
                            </ul>
                            <button class="btn btn-view-details" data-toggle="modal" data-target="#loginModal">
                                <i class="fas fa-lock"></i> Login to Book
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="room-card">
                        <div class="room-img" style="background-image: url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');"></div>
                        <div class="room-content">
                            <h3 class="room-title">Beachfront Villa</h3>
                            <div class="room-price">$399 <span>/ night</span></div>
                            <ul class="room-features">
                                <li><i class="fas fa-check"></i> Direct beach access</li>
                                <li><i class="fas fa-check"></i> Two bedrooms</li>
                                <li><i class="fas fa-check"></i> Private pool</li>
                                <li><i class="fas fa-check"></i> Kitchenette</li>
                            </ul>
                            <button class="btn btn-view-details" data-toggle="modal" data-target="#loginModal">
                                <i class="fas fa-lock"></i> Login to Book
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="room-card">
                        <div class="room-img" style="background-image: url('https://images.unsplash.com/photo-1615873968403-89e068629265?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');"></div>
                        <div class="room-content">
                            <h3 class="room-title">Garden Cottage</h3>
                            <div class="room-price">$249 <span>/ night</span></div>
                            <ul class="room-features">
                                <li><i class="fas fa-check"></i> Garden view</li>
                                <li><i class="fas fa-check"></i> Queen size bed</li>
                                <li><i class="fas fa-check"></i> Private patio</li>
                                <li><i class="fas fa-check"></i> Fireplace</li>
                            </ul>
                            <button class="btn btn-view-details" data-toggle="modal" data-target="#loginModal">
                                <i class="fas fa-lock"></i> Login to Book
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Experience Luxury?</h2>
            <p>Join thousands of satisfied guests who've discovered the perfect getaway at Aura Luxe Resort</p>
            <div class="mt-4">
                <a href="auth/register.php" class="btn btn-cta-primary">
                    <i class="fas fa-user-plus"></i> Create Your Free Account
                </a>
            </div>
            <p class="mt-4" style="font-size: 1.1rem; opacity: 0.8;">
                Already have an account? 
                <a href="auth/login.php" style="color: var(--white); text-decoration: underline; font-weight: 600;">Login here</a>
            </p>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="resort-section">
        <div class="container">
            <div class="section-title">
                <h2>Special Packages</h2>
                <p>Discover our exclusive offers for an unforgettable experience</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="package-card position-relative">
                        <div class="package-badge">Most Popular</div>
                        <div class="package-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="package-title">Romantic Getaway</h3>
                        <div class="package-price">$899</div>
                        <ul class="room-features text-left">
                            <li><i class="fas fa-check text-success"></i> 3 Nights in Ocean View Suite</li>
                            <li><i class="fas fa-check text-success"></i> Couples Spa Treatment</li>
                            <li><i class="fas fa-check text-success"></i> Romantic Dinner on Beach</li>
                            <li><i class="fas fa-check text-success"></i> Champagne & Chocolate</li>
                        </ul>
                        <button class="btn btn-view-details mt-3" data-toggle="modal" data-target="#loginModal">
                            <i class="fas fa-lock"></i> Login to Book Package
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="package-card position-relative">
                        <div class="package-badge">Family Favorite</div>
                        <div class="package-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="package-title">Family Adventure</h3>
                        <div class="package-price">$1,299</div>
                        <ul class="room-features text-left">
                            <li><i class="fas fa-check text-success"></i> 5 Nights in Beachfront Villa</li>
                            <li><i class="fas fa-check text-success"></i> Kids Activities Daily</li>
                            <li><i class="fas fa-check text-success"></i> Family Breakfast Buffet</li>
                            <li><i class="fas fa-check text-success"></i> Water Sports Equipment</li>
                        </ul>
                        <button class="btn btn-view-details mt-3" data-toggle="modal" data-target="#loginModal">
                            <i class="fas fa-lock"></i> Login to Book Package
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="package-card position-relative">
                        <div class="package-badge">Best Value</div>
                        <div class="package-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <h3 class="package-title">Weekend Escape</h3>
                        <div class="package-price">$499</div>
                        <ul class="room-features text-left">
                            <li><i class="fas fa-check text-success"></i> 2 Nights in Garden Cottage</li>
                            <li><i class="fas fa-check text-success"></i> Welcome Drink & Fruit Basket</li>
                            <li><i class="fas fa-check text-success"></i> Daily Breakfast</li>
                            <li><i class="fas fa-check text-success"></i> Late Checkout (4 PM)</li>
                        </ul>
                        <button class="btn btn-view-details mt-3" data-toggle="modal" data-target="#loginModal">
                            <i class="fas fa-lock"></i> Login to Book Package
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="resort-section about-section">
        <div class="container">
            <div class="section-title">
                <h2>About Aura Luxe Resort</h2>
                <p>Discover luxury, comfort, and unforgettable experiences</p>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <h3 class="mb-4" style="color: var(--primary-turquoise);">Your Paradise Awaits</h3>
                    <p class="mb-4">Nestled along a pristine coastline, Aura Luxe Resort offers a perfect blend of luxury and natural beauty. Our resort features exclusive cottages designed for ultimate comfort and privacy.</p>
                    <p class="mb-4">With over 15 years of excellence in hospitality, we pride ourselves on providing exceptional service and creating memorable experiences for every guest.</p>
                    
                    <div class="about-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-umbrella-beach"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Private Beach Access</h5>
                                <p class="text-muted mb-0">Exclusive shoreline for guests</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Luxury Spa</h5>
                                <p class="text-muted mb-0">Premium wellness treatments</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Fine Dining</h5>
                                <p class="text-muted mb-0">Award-winning restaurants</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-swimmer"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Infinity Pools</h5>
                                <p class="text-muted mb-0">Stunning ocean-view pools</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Resort Overview">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="resort-footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-5">
                    <div class="footer-widget">
                        <h4>Aura Luxe Resort</h4>
                        <p class="mt-4" style="color: #bdc3c7;">Experience luxury, comfort, and unforgettable moments at our premier beachfront resort.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-tripadvisor"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-5">
                    <div class="footer-widget">
                        <h4>Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="#">Home</a></li>
                            <li><a href="#rooms">Rooms</a></li>
                            <li><a href="#packages">Packages</a></li>
                            <li><a href="#about">About Us</a></li>
                            <li><a href="auth/login.php">Login</a></li>
                            <li><a href="auth/register.php">Register</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-5">
                    <div class="footer-widget">
                        <h4>Contact Info</h4>
                        <ul class="contact-info">
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>123 Beachfront Avenue<br>Coastal City, CC 12345</span>
                            </li>
                            <li>
                                <i class="fas fa-phone"></i>
                                <span>+1 (555) 123-4567</span>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <span>reservations@auraluxeresort.com</span>
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                <span>24/7 Reservation Support</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-5">
                    <div class="footer-widget">
                        <h4>Newsletter</h4>
                        <p style="color: #bdc3c7;">Subscribe to get special offers and updates</p>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Your email" required>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <p class="mt-3" style="font-size: 0.85rem; color: #95a5a6;">
                            <i class="fas fa-shield-alt mr-1" style="color: var(--primary-turquoise);"></i>
                            We respect your privacy
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Aura Luxe Resort. All rights reserved. | 
                    <a href="#" style="color: var(--primary-turquoise); text-decoration: none;">Privacy Policy</a> | 
                    <a href="#" style="color: var(--primary-turquoise); text-decoration: none;">Terms of Service</a>
                </p>
                <p class="mt-2">
                    <i class="fas fa-leaf mr-1" style="color: var(--primary-turquoise);"></i>
                    Sustainable Tourism Certified | 
                    <i class="fas fa-award mr-1 ml-3" style="color: var(--accent-yellow);"></i>
                    Luxury Travel Award Winner 2024
                </p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--primary-turquoise); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-lock mr-2"></i>Account Required
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-user-lock" style="font-size: 4rem; color: var(--primary-turquoise);"></i>
                    </div>
                    <h4 class="mb-3">Please Sign In</h4>
                    <p class="mb-4">To book rooms or packages, you need to have an account. Sign in or create a free account to continue.</p>
                    
                    <div class="d-flex flex-column gap-3">
                        <a href="auth/login.php" class="btn btn-hero-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Existing Account
                        </a>
                        <a href="auth/register.php" class="btn btn-hero-secondary">
                            <i class="fas fa-user-plus"></i> Create New Account
                        </a>
                    </div>
                    
                    <p class="mt-4 text-muted">
                        <small>Accounts are free and only take a minute to create</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="adminlte/dist/js/adminlte.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    
    <script>
        $(document).ready(function(){
            // Hero Slider
            $('.hero-slider').slick({
                dots: true,
                infinite: true,
                speed: 1000,
                fade: true,
                cssEase: 'linear',
                autoplay: true,
                autoplaySpeed: 6000,
                arrows: true,
                prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
                nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-right"></i></button>',
                pauseOnHover: false
            });
            
            // Smooth scroll for navigation links
            $('a[href^="#"]').on('click', function(e) {
                if ($(this).attr('href').startsWith('auth/')) return;
                
                e.preventDefault();
                var target = $(this.getAttribute('href'));
                if(target.length) {
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 70
                    }, 1000);
                }
            });
            
            // Navbar background on scroll
            $(window).scroll(function() {
                if($(window).scrollTop() > 50) {
                    $('.resort-header').css({
                        'background-color': 'rgba(255, 255, 255, 0.95)',
                        'backdrop-filter': 'blur(10px)'
                    });
                } else {
                    $('.resort-header').css({
                        'background-color': 'rgba(255, 255, 255, 0.95)',
                        'backdrop-filter': 'blur(10px)'
                    });
                }
            });
            
            // Newsletter form submission
            $('.newsletter-form').submit(function(e) {
                e.preventDefault();
                var email = $(this).find('input[type="email"]').val();
                if(email) {
                    $(this).html('<div class="alert alert-success" style="background: var(--primary-turquoise); color: white; border: none; padding: 15px; border-radius: 5px;">Thank you for subscribing!</div>');
                }
            });
            
            // Initialize scroll effect
            $(window).trigger('scroll');
            
            // Add hover effect to view details buttons
            $('.btn-view-details').hover(
                function() {
                    $(this).css({
                        'background-color': var('--primary-turquoise'),
                        'color': 'white'
                    });
                },
                function() {
                    $(this).css({
                        'background-color': 'transparent',
                        'color': var('--primary-turquoise')
                    });
                }
            );
        });
    </script>
</body>
</html>