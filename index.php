<?php
// Page purpose: Public landing page for Clothify with the store introduction, featured products, and entry points.
session_start();
require_once __DIR__ . '/ProductDAO.php';

$isLoggedIn = isset($_SESSION['user_id']);

$products = getAllProducts();
$featuredProducts = [];
foreach ($products as $product) {
    if (!empty($product['image_url'])) {
        $featuredProducts[] = $product;
    }
    if (count($featuredProducts) >= 4) {
        break;
    }
}

// Guests are sent through login before they can browse the shop; after signing in they return to the catalog.
$shopHref = $isLoggedIn ? 'shop.php' : 'login.php?next=shop.php';
$primaryHref = $shopHref;
$primaryLabel = $isLoggedIn ? 'Shop the collection' : 'Shop now';
$secondaryHref = $isLoggedIn ? 'dashboard.php' : 'register.php';
$secondaryLabel = $isLoggedIn ? 'Go to dashboard' : 'Create account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clothify - Style that feels like you</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-body">
    <header class="landing-nav">
        <div class="landing-nav-inner">
            <a class="landing-brand" href="index.php">Clothify<span>STYLE THAT FEELS LIKE YOU</span></a>
            <nav class="landing-nav-links" aria-label="Landing page navigation">
                <a href="#collection">Collection</a>
                <a href="#story">Our story</a>
                <a href="#why">Why Clothify</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a class="landing-nav-cta" href="<?php echo htmlspecialchars($shopHref, ENT_QUOTES, 'UTF-8'); ?>">Shop now</a>
                <?php else: ?>
                    <a href="login.php">Sign in</a>
                    <a class="landing-nav-cta" href="<?php echo htmlspecialchars($shopHref, ENT_QUOTES, 'UTF-8'); ?>">Shop now</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-hero" aria-labelledby="landing-hero-heading">
            <div class="landing-hero-copy">
                <span class="landing-eyebrow">CLOTHIFY &middot; YOUR GO-TO FASHION STORE</span>
                <h1 id="landing-hero-heading">Style that feels <em>like you.</em></h1>
                <p>Thoughtfully selected pieces for every day, from relaxed layers to occasion-ready looks. Explore the collection and make getting dressed the best part of your day.</p>
                <div class="landing-hero-actions">
                    <a class="btn" href="<?php echo htmlspecialchars($primaryHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($primaryLabel); ?> <span aria-hidden="true">&rarr;</span></a>
                    <a class="btn btn-outline" href="<?php echo htmlspecialchars($secondaryHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($secondaryLabel); ?></a>
                </div>
                <ul class="landing-hero-points">
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg> Men&rsquo;s &amp; women&rsquo;s collections</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg> Live size availability</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg> Easy order tracking</li>
                </ul>
            </div>
            <div class="landing-hero-visual" aria-label="Clothify lookbook">
                <div class="landing-hero-main">
                    <img src="assets/images/oliviaregister.jpg" alt="Clothify lookbook: a model in a pink outfit on a swing" fetchpriority="high">
                </div>
                <div class="landing-hero-small">
                    <img src="assets/images/floatingclothesforgot.jpg" alt="A collection of everyday clothing floating in the sky" loading="lazy">
                </div>
                <div class="landing-hero-badge">
                    <strong>New season</strong>
                    <span>Thoughtfully selected pieces</span>
                </div>
            </div>
        </section>

        <div class="landing-highlights" aria-label="Store highlights">
            <div class="landing-highlights-inner">
                <span>Curated collections</span>
                <span>Size-aware stock</span>
                <span>Secure checkout</span>
                <span>Order tracking</span>
            </div>
        </div>

        <section class="landing-section" id="collection" aria-labelledby="collection-heading">
            <div class="landing-section-heading">
                <div>
                    <span class="landing-eyebrow">THE COLLECTION</span>
                    <h2 id="collection-heading">Find your next favorite</h2>
                </div>
                <p>Pieces chosen for easy styling, comfortable fits, and the little details that make an outfit feel like yours.</p>
                <a href="<?php echo htmlspecialchars($shopHref, ENT_QUOTES, 'UTF-8'); ?>">View all pieces &rarr;</a>
            </div>

            <div class="landing-product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php
                    $fallbackImage = strtoupper((string)$product['category']) === 'WOMEN'
                        ? 'assets/images/products/azure-day-dress.jpg'
                        : 'assets/images/products/aspen-check-flannel-shirt.jpg';
                    ?>
                    <a class="landing-product-card" href="<?php echo htmlspecialchars($shopHref, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="landing-product-media">
                            <img src="<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImage, ENT_QUOTES, 'UTF-8'); ?>';">
                        </div>
                        <div class="landing-product-meta">
                            <span class="landing-product-category"><?php echo htmlspecialchars(ucfirst(strtolower((string)$product['category']))); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <span class="price">PHP <?php echo number_format((float)$product['price'], 2); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (count($featuredProducts) < 4): ?>
                    <a class="landing-product-cta" href="<?php echo htmlspecialchars($shopHref, ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?php echo count($featuredProducts) === 0 ? 'The collection is getting a refresh.' : 'See every piece in the store.'; ?></span>
                        <small><?php echo $isLoggedIn ? 'Browse the full collection' : 'Sign in to browse the full collection'; ?></small>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section class="landing-story" id="story" aria-labelledby="story-heading">
            <div class="landing-story-image">
                <img src="assets/images/sgacard-story.jpg" alt="Clothify street-style lookbook featuring a statement jacket" width="1200" height="997" loading="lazy">
            </div>
            <div class="landing-story-copy">
                <span class="landing-eyebrow">OUR STORY</span>
                <h2 id="story-heading">Everyday clothing, considered.</h2>
                <p>Clothify brings together a focused edit of men&rsquo;s and women&rsquo;s clothing with a simple idea: getting dressed should feel easy. Browse by category, check the sizes that are actually available, and build a cart without the guesswork.</p>
                <a class="btn" href="<?php echo htmlspecialchars($primaryHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($primaryLabel); ?> <span aria-hidden="true">&rarr;</span></a>
                <div class="landing-story-stats">
                    <div><strong>Men &amp; Women</strong><span>Curated collections</span></div>
                    <div><strong>XS &ndash; 2XL</strong><span>Size-aware stock</span></div>
                    <div><strong>Live</strong><span>Order tracking</span></div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-why" id="why" aria-labelledby="why-heading">
            <div class="landing-section-heading">
                <div>
                    <span class="landing-eyebrow">WHY CLOTHIFY</span>
                    <h2 id="why-heading">A complete store, end to end</h2>
                </div>
                <p>More than a catalog: Clothify connects the storefront, your cart, and order history with the tools behind the scenes.</p>
            </div>

            <div class="landing-feature-grid">
                <article class="landing-feature-card">
                    <span class="landing-feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l2 3 2-1v11h8V9l2 1 2-3-4-3a4 4 0 0 1-8 0Z"/></svg></span>
                    <h3>Curated storefront</h3>
                    <p>Browse men&rsquo;s and women&rsquo;s pieces with search, category, and price filters that make finding a fit simple.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/><circle cx="18" cy="17" r="2"/></svg></span>
                    <h3>Size-aware stock</h3>
                    <p>Choose from XS through 2XL and see live availability for each size before adding an item to your cart.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l2 11h10l2-8H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg></span>
                    <h3>Cart &amp; checkout</h3>
                    <p>Review item previews, sizes, and quantities, then place an order and keep track of it from your account.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg></span>
                    <h3>Orders &amp; reports</h3>
                    <p>Customers can follow their order history, while admins manage products, orders, and sales reports in one place.</p>
                </article>
            </div>
        </section>

        <section class="landing-cta" aria-labelledby="landing-cta-heading">
            <h2 id="landing-cta-heading">Ready to find your next favorite?</h2>
            <p>Step into the collection and discover pieces that fit your everyday, your way.</p>
            <a class="btn" href="<?php echo htmlspecialchars($primaryHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($primaryLabel); ?> <span aria-hidden="true">&rarr;</span></a>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-footer-inner">
            <div>
                <a class="landing-footer-brand" href="index.php">Clothify</a>
                <p>Clothing Store Management System &middot; PHP &amp; MySQL</p>
            </div>
            <nav class="landing-footer-links" aria-label="Footer navigation">
                <a href="#collection">Collection</a>
                <a href="#story">Our story</a>
                <a href="#why">Why Clothify</a>
                <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'login.php'; ?>"><?php echo $isLoggedIn ? 'Dashboard' : 'Sign in'; ?></a>
            </nav>
        </div>
    </footer>
</body>
</html>
