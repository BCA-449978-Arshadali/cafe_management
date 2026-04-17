<?php
// ============================================================
//  DROPPERS CAFÉ — CENTRAL MODULES FILE
//  📌 Sirf is ek file mein kuch bhi add karo
//     → Customer navbar mein auto dikhega
//     → Admin sidebar mein auto dikhega
//     → Feature flags bhi yahan se control hoga
//
//  Koi naya page add karna ho?
//  Bas neeche ek array entry likho — baaki sab automatic! ✅
// ============================================================


// ============================================================
//  SECTION 1: FEATURE FLAGS
//  true  = feature ON  |  false = feature OFF
//  (config.php ke purane flags ko replace karta hai)
// ============================================================
$FEATURES = [
    'ONLINE_PAYMENT'    => true,
    'TABLE_BOOKING'     => true,
    'ORDER_TRACKING'    => true,
    'FEEDBACK'          => true,
    'EMAIL_BILL'        => true,
    'WHATSAPP_ORDER'    => true,
    'LOYALTY_POINTS'    => false,     // ✅ Loyalty Points live!
    'OFFERS_PAGE'       => false,     // ✅ Offers page live!
    'REVIEWS'           => false,   // 🔮 Future: item reviews
    'WALLET'            => false,   // 🔮 Future: digital wallet
    'REFERRAL'          => false,   // 🔮 Future: referral program
    'DARK_MODE'         => false,   // 🔮 Future: dark mode toggle
    'LIVE_CHAT'         => false,   // 🔮 Future: customer support chat
    'PROMO_ANIMATION'   => true,    // ✅ Promo code apply hone pe popup + confetti dikhao
];

// Helper function — feature ON hai ya nahi check karo
// Usage: if(module_feature('LOYALTY_POINTS')) { ... }
if(!function_exists('module_feature')){
    function module_feature(string $name): bool {
        global $FEATURES;
        return !empty($FEATURES[strtoupper($name)]);
    }
}


// ============================================================
//  SECTION 2: CUSTOMER NAVBAR LINKS
//
//  Naya page customer navbar mein add karna ho?
//  Bas ek entry yahan likhdo — automatically dikhega!
//
//  Fields:
//    label       → Jo text navbar mein dikhega
//    icon        → Emoji ya Font Awesome class ("fa fa-star")
//    file        → PHP filename (customer/ folder mein)
//    feature     → Feature flag se link (null = hamesha dikhega)
//    login_only  → true = sirf logged-in users dikhega
//    position    → 'main' (top navbar) | 'mobile_only' | 'hidden'
// ============================================================
$CUSTOMER_NAV = [

    // ── Hamesha dikhne wale links ──────────────────────────
    [
        'label'      => 'Home',
        'icon'       => '🏠',
        'file'       => 'index.php',
        'feature'    => null,           // koi feature flag nahi
        'login_only' => false,
        'position'   => 'main',
    ],
    [
        'label'      => 'Menu',
        'icon'       => '🍽️',
        'file'       => 'menu.php',
        'feature'    => null,
        'login_only' => false,
        'position'   => 'main',
    ],
    [
        'label'      => 'Book Table',
        'icon'       => '🪑',
        'file'       => 'book_table.php',
        'feature'    => 'TABLE_BOOKING',    // sirf tab dikhega jab feature ON ho
        'login_only' => false,
        'position'   => 'main',
    ],
    [
        'label'      => 'Track Order',
        'icon'       => '📍',
        'file'       => 'track_order.php',
        'feature'    => 'ORDER_TRACKING',
        'login_only' => true,
        'position'   => 'hidden',   // 🔴 Hidden — wapas dikhana ho toh 'main' karo
    ],
    [
        'label'      => 'Feedback',
        'icon'       => '⭐',
        'file'       => 'feedback.php',
        'feature'    => 'FEEDBACK',
        'login_only' => false,
        'position'   => 'main',
    ],
    [
        'label'      => 'About',
        'icon'       => 'ℹ️',
        'file'       => 'about.php',
        'feature'    => null,
        'login_only' => false,
        'position'   => 'hidden',   // 🔴 Hidden — wapas dikhana ho toh 'main' karo
    ],
    [
        'label'      => 'Offers',
        'icon'       => '🎁',
        'file'       => 'offers.php',
        'feature'    => 'OFFERS_PAGE',      // ✅ true hai → navbar mein dikhega
        'login_only' => false,
        'position'   => 'main',
    ],
    [
        'label'      => 'My Points',
        'icon'       => '🏆',
        'file'       => 'loyalty.php',
        'feature'    => 'LOYALTY_POINTS',   // false hai → nahi dikhega
        'login_only' => true,
        'position'   => 'main',
    ],
    [
        'label'      => 'Wallet',
        'icon'       => '👛',
        'file'       => 'wallet.php',
        'feature'    => 'WALLET',           // false hai → nahi dikhega
        'login_only' => true,
        'position'   => 'main',
    ],

    // ============================================================
    // 🆕 NAYA PAGE KAISE ADD KAREIN? — Iska example dekho:
    // ============================================================
    //
    // [
    //     'label'      => 'My Orders',           ← Navbar mein jo dikhega
    //     'icon'       => '📦',                  ← Emoji
    //     'file'       => 'my_orders.php',       ← customer/ folder mein file naam
    //     'feature'    => null,                  ← null = hamesha dikhega
    //     'login_only' => true,                  ← sirf login users
    //     'position'   => 'main',                ← 'main' rakhdo
    // ],
    //
    // Bas itna likho — baaki sab automatic! ✅
    // ============================================================
];


// ============================================================
//  SECTION 3: ADMIN SIDEBAR LINKS
//
//  Naya admin page banana ho?
//  Bas ek entry yahan likhdo — sidebar mein auto aayega!
//
//  Fields:
//    label        → Sidebar mein jo text dikhega
//    icon         → Font Awesome class (fa fa-xxx)
//    file         → PHP filename (admin/ folder mein)
//    section      → Section heading (null = pichla section chalega)
//    badge_query  → SQL query jo badge count deta hai (null = koi badge nahi)
//    feature      → Feature flag se link (null = hamesha dikhega)
// ============================================================
$ADMIN_NAV = [

    // ── MAIN ──────────────────────────────────────────────
    [
        'section'      => 'Main',
        'label'        => 'Dashboard',
        'icon'         => 'fa fa-gauge-high',
        'file'         => 'dashboard.php',
        'badge_query'  => null,
        'feature'      => null,
    ],

    // ── MENU ──────────────────────────────────────────────
    [
        'section'      => 'Menu',
        'label'        => 'Add Item',
        'icon'         => 'fa fa-circle-plus',
        'file'         => 'add_item.php',
        'badge_query'  => null,
        'feature'      => null,
    ],
    [
        'section'      => null,         // section nahi, pichla chalega
        'label'        => 'View Menu',
        'icon'         => 'fa fa-utensils',
        'file'         => 'view_items.php',
        'badge_query'  => null,
        'feature'      => null,
    ],

    // ── COMBOS ────────────────────────────────────────────
    [
        'section'      => 'Combos',
        'label'        => 'Add Combo',
        'icon'         => 'fa fa-circle-plus',
        'file'         => 'add_combo.php',
        'badge_query'  => null,
        'feature'      => null,
    ],
    [
        'section'      => null,
        'label'        => 'View Combos',
        'icon'         => 'fa fa-layer-group',
        'file'         => 'view_combos.php',
        'badge_query'  => null,
        'feature'      => null,
    ],

    // ── OPERATIONS ────────────────────────────────────────
    [
        'section'      => 'Operations',
        'label'        => 'Orders',
        'icon'         => 'fa fa-bag-shopping',
        'file'         => 'view_orders.php',
        // Badge: pending orders count automatically dikhega
        'badge_query'  => "SELECT COUNT(*) as c FROM orders WHERE status='Pending'",
        'feature'      => null,
    ],
    [
        'section'      => null,
        'label'        => 'Table Bookings',
        'icon'         => 'fa fa-chair',
        'file'         => 'view_bookings.php',
        'badge_query'  => "SELECT COUNT(*) as c FROM bookings WHERE status='Pending'",
        'feature'      => 'TABLE_BOOKING',
    ],
    [
        'section'      => null,
        'label'        => 'Feedback',
        'icon'         => 'fa fa-star',
        'file'         => 'view_feedback.php',
        'badge_query'  => null,
        'feature'      => 'FEEDBACK',
    ],
    [
        'section'      => null,
        'label'        => 'Reports',
        'icon'         => 'fa fa-chart-line',
        'file'         => 'reports.php',
        'badge_query'  => null,
        'feature'      => null,
    ],

    // ── OFFERS ────────────────────────────────────────────
    [
        'section'      => 'Offers',        'label'        => 'Loyalty Points',
        'icon'         => 'fa fa-trophy',
        'file'         => 'loyalty.php',
        'badge_query'  => null,
        'feature'      => 'LOYALTY_POINTS',
    ],

    // ── FUTURE PAGES (feature OFF hai toh hidden) ─────────
    [
        'section'      => null,
        'label'        => 'Add Offer',
        'icon'         => 'fa fa-circle-plus',
        'file'         => 'add_offer.php',
        'badge_query'  => null,
        'feature'      => 'OFFERS_PAGE',
    ],
    [
        'section'      => null,
        'label'        => 'View Offers',
        'icon'         => 'fa fa-tags',
        'file'         => 'view_offers.php',
        'badge_query'  => null,
        'feature'      => 'OFFERS_PAGE',
    ],
    [
        'section'      => null,
        'label'        => 'Wallet Manage',
        'icon'         => 'fa fa-wallet',
        'file'         => 'wallet_admin.php',
        'badge_query'  => null,
        'feature'      => 'WALLET',         // false → hidden
    ],

    // ============================================================
    // 🆕 NAYA ADMIN PAGE KAISE ADD KAREIN? — Example:
    // ============================================================
    //
    // [
    //     'section'      => 'Reports',           ← Section heading (null = pichla)
    //     'label'        => 'Customer List',     ← Sidebar mein jo dikhega
    //     'icon'         => 'fa fa-users',       ← Font Awesome icon
    //     'file'         => 'customers.php',     ← admin/ folder mein file naam
    //     'badge_query'  => null,                ← ya SQL query for badge
    //     'feature'      => null,                ← null = hamesha dikhega
    // ],
    //
    // Bas itna likho — sidebar mein auto aayega! ✅
    // ============================================================
];


// ============================================================
//  SECTION 4: WEBSITE SETTINGS
//  Cafe ka naam, contact details, social links — sab yahan se
// ============================================================
$SITE = [
    'name'          => 'Droppers Café',
    'tagline'       => 'Every Sip Tells a Story',
    'phone'         => '+91-XXXXXXXXXX',
    'email'         => 'hello@dropperscafe.com',
    'address'       => 'Hazaribagh, Jharkhand',
    'whatsapp'      => '+91-XXXXXXXXXX',
    'instagram'     => 'https://instagram.com/dropperscafe',
    'facebook'      => '',
    'currency'      => '₹',
    'gst_percent'   => 5,           // GST % — 0 = GST nahi lagega
    'free_delivery_above' => 299,   // Is amount se upar free delivery
    'min_order'     => 99,          // Minimum order amount
];


// ============================================================
//  INTERNAL HELPER FUNCTIONS — Inhe use karo files mein
//  (Neeche paste ki gayi functions ko change mat karo)
// ============================================================

/**
 * Customer navbar generate karo
 * Usage: navbar.php mein <?php render_customer_nav(); ?>
 */
function render_customer_nav(): void {
    global $CUSTOMER_NAV, $FEATURES;
    $current = basename($_SERVER['PHP_SELF']);
    $logged_in = isset($_SESSION['customer_id']);
    $base = defined('BASE_URL') ? BASE_URL : '';

    foreach($CUSTOMER_NAV as $item){
        // Feature check
        if($item['feature'] !== null && empty($FEATURES[$item['feature']])) continue;
        // Login check
        if($item['login_only'] && !$logged_in) continue;
        // Hidden check
        if($item['position'] === 'hidden') continue;

        $active = ($current === $item['file']) ? 'active' : '';
        $url    = "{$base}/customer/{$item['file']}";
        echo "<a href=\"{$url}\" class=\"{$active}\">{$item['icon']} {$item['label']}</a>\n";
    }
}

/**
 * Admin sidebar generate karo
 * Usage: admin/includes/sidebar.php mein <?php render_admin_nav($conn); ?>
 */
function render_admin_nav($conn): void {
    global $ADMIN_NAV, $FEATURES;
    $current = basename($_SERVER['PHP_SELF']);
    $last_section = '';

    foreach($ADMIN_NAV as $item){
        // Feature check
        if($item['feature'] !== null && empty($FEATURES[$item['feature']])) continue;

        // Section heading
        if($item['section'] !== null && $item['section'] !== $last_section){
            echo "<div class=\"sidebar-section\">{$item['section']}</div>\n";
            $last_section = $item['section'];
        }

        // Badge count
        $badge = '';
        if(!empty($item['badge_query'])){
            $r = mysqli_fetch_assoc(mysqli_query($conn, $item['badge_query']));
            $count = intval($r['c'] ?? 0);
            if($count > 0) $badge = "<span class=\"nav-badge\">{$count}</span>";
        }

        $active = ($current === $item['file']) ? 'active' : '';
        echo "<a href=\"{$item['file']}\" class=\"{$active}\">"
           . "<i class=\"{$item['icon']}\"></i> {$item['label']}{$badge}"
           . "</a>\n";
    }
}