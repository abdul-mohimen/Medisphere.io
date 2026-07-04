<?php use App\Core\Auth; ?>
<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>" dir="<?= is_rtl() ? "rtl" : "ltr" ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? config('app.name')) ?> | <?= e(config('app.name')) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? "Premium healthcare management platform with patient, doctor, hospital, and admin portals.") ?>">
    <?php if (!empty($canonicalUrl)): ?><link rel="canonical" href="<?= e($canonicalUrl) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= e($title ?? config('app.name')) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? "Premium healthcare management platform with patient, doctor, hospital, and admin portals.") ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($canonicalUrl)): ?><meta property="og:url" content="<?= e($canonicalUrl) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="current-user-id" content="<?= App\Core\Auth::id() ?? '' ?>">
    <script>
        (function () {
            var key = 'medisphereSplashSeen';
            var seen = false;
            try {
                seen = window.sessionStorage && window.sessionStorage.getItem(key) === '1';
            } catch (error) {
                seen = false;
            }
            document.documentElement.classList.add(seen ? 'splash-seen' : 'splash-first-visit');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
    <link href="<?= app_url('assets/css/app.css') ?>?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
<?php
$splashVideos = [
    [
        'src' => app_url('uploads/media/videos/hospital-operations-smarter-beds-inventory-voiced.mp4'),
        'poster' => 'https://images.pexels.com/videos/6130037/pictures/preview-0.jpg',
    ],
    [
        'src' => app_url('uploads/media/videos/strong-telemedicine-visit-voiced.mp4'),
        'poster' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1600&q=88',
    ],
    [
        'src' => app_url('uploads/media/videos/care-transitions-after-hospital-visit-voiced.mp4'),
        'poster' => 'https://images.pexels.com/videos/6130021/pictures/preview-0.jpg',
    ],
];
?>
<div class="premium-splash" id="premiumSplash" data-duration="5000" role="status" aria-live="polite" aria-label="<?= e(config('app.name')) ?> is preparing your healthcare network">
    <div class="splash-video-stage" aria-hidden="true">
        <?php foreach ($splashVideos as $index => $video): ?>
            <video class="splash-bg-video <?= $index === 0 ? 'is-active' : '' ?>" autoplay muted loop playsinline preload="<?= $index === 0 ? 'auto' : 'metadata' ?>" poster="<?= e($video['poster']) ?>" data-splash-video>
                <source src="<?= e($video['src']) ?>" type="video/mp4">
            </video>
        <?php endforeach; ?>
    </div>
    <div class="splash-video-overlay" aria-hidden="true"></div>
    <div class="splash-brand-lockup">
        <span class="splash-mark" aria-hidden="true"><i class="fa-solid fa-heart-pulse"></i></span>
        <div class="splash-brand-copy">
            <div class="splash-brand-name"><?= e(config('app.name')) ?></div>
            <p>Intelligent care, secure records, and global clinical coordination in one premium network.</p>
        </div>
    </div>
</div>
<noscript><style>.premium-splash{display:none!important}</style></noscript>
<div class="ambient-bg" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>
</div>
<?php
$currentRoute = $_GET['route'] ?? 'home';
$currentRouteClass = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $currentRoute)), '-');
$currentRouteClass = $currentRouteClass !== '' ? $currentRouteClass : 'home';
$currentReturnParams = $_GET;
$currentReturnParams['route'] = $currentRoute;
$guestAuthReturnTo = app_url('index.php?' . http_build_query($currentReturnParams));
if (in_array($currentRoute, ['auth', 'login', 'register'], true)) {
    $guestAuthReturnTo = route_url('dashboard');
}
$headerNotifications = [];
$headerUnreadCount = 0;
if (Auth::check()) {
    $headerNotificationModel = new App\Models\Notification();
    $headerNotifications = $headerNotificationModel->recentForUser(Auth::id(), 6);
    $headerUnreadCount = $headerNotificationModel->unreadCount(Auth::id());
}
$publicNav = [
    ['route' => 'home', 'label' => 'Home'],
    ['route' => 'find-healthcare', 'label' => 'Find Care'],
    ['route' => 'map', 'label' => 'Map'],
    ['route' => 'blog', 'label' => 'Articles'],
    ['route' => 'guidelines', 'label' => 'Help'],
    ['route' => 'faq', 'label' => 'FAQ'],
];
$showChatbot = !in_array($currentRoute, ['auth', 'login', 'register', 'reset-password', 'setup-admin'], true);
?>
<div class="app-shell <?= Auth::check() ? 'app-authenticated' : 'app-public' ?>">
    <?php if (Auth::check()): ?>
        <aside class="sidebar glass-panel" id="sidebar">
            <div class="sidebar-brand d-flex align-items-center justify-content-between mb-4">
                <a href="<?= route_url('dashboard') ?>" class="brand-link text-decoration-none">
                    <span class="logo-badge"><i class="fa-solid fa-heart-pulse"></i></span>
                    <span>MediSphere</span>
                </a>
                <button class="btn btn-sm sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <nav class="sidebar-nav">
                <?php
                $menu = [
                    ['route' => 'dashboard', 'icon' => 'fa-gauge-high', 'label' => __('common.home')],
                    ['route' => 'profile', 'icon' => 'fa-user-doctor', 'label' => __('common.profile')],
                    ['route' => 'appointments', 'icon' => 'fa-calendar-check', 'label' => __('common.appointments')],
                    ['route' => 'payments', 'icon' => 'fa-credit-card', 'label' => __('common.payments')],
                    ['route' => 'reports', 'icon' => 'fa-file-waveform', 'label' => __('common.reports')],
                    ['route' => 'record-consents', 'icon' => 'fa-user-shield', 'label' => 'Record Consents'],
                    ['route' => 'compliance-center', 'icon' => 'fa-scale-balanced', 'label' => 'Compliance Center'],
                    ['route' => 'clinical-records', 'icon' => 'fa-file-prescription', 'label' => 'Clinical Records'],
                    ['route' => 'notifications', 'icon' => 'fa-bell', 'label' => __('common.notifications')],
                    ['route' => 'messages', 'icon' => 'fa-comments', 'label' => __('common.messages')],
                    ['route' => 'consultations', 'icon' => 'fa-video', 'label' => __('common.consultations')],
                    ['route' => 'scanner', 'icon' => 'fa-microscope', 'label' => __('common.scanner')],
                    ['route' => 'find-healthcare', 'icon' => 'fa-stethoscope', 'label' => __('common.find_healthcare')],
                    ['route' => 'map', 'icon' => 'fa-map-location-dot', 'label' => __('common.map')],
                    ['route' => 'guidelines', 'icon' => 'fa-book-medical', 'label' => __('common.guidelines')],
                    ['route' => 'faq', 'icon' => 'fa-circle-question', 'label' => __('common.faq')],
                    ['route' => 'admin/verifications', 'icon' => 'fa-shield-heart', 'label' => __('common.verification')],
                    ['route' => 'admin/communications', 'icon' => 'fa-envelope-open-text', 'label' => __('common.templates')],
                    ['route' => 'admin/content', 'icon' => 'fa-newspaper', 'label' => 'Content Management'],
                    ['route' => 'admin/compliance', 'icon' => 'fa-shield-halved', 'label' => 'Compliance Operations'],
                    ['route' => 'hospital-management', 'icon' => 'fa-building-circle-check', 'label' => 'Hospital Management'],
                ];
                foreach ($menu as $item):
                    if (in_array($item['route'], ['admin/verifications', 'admin/communications', 'admin/content', 'admin/compliance'], true) && Auth::type() !== 'admin') {
                        continue;
                    }
                    if ($item['route'] === 'hospital-management' && Auth::type() !== 'hospital') {
                        continue;
                    }
                    if ($item['route'] === 'scanner' && Auth::type() !== 'patient') {
                        continue;
                    }
                    if ($item['route'] === 'payments' && Auth::type() === 'hospital') {
                        continue;
                    }
                    if ($item['route'] === 'record-consents' && Auth::type() !== 'patient') {
                        continue;
                    }
                    if ($item['route'] === 'compliance-center' && !in_array(Auth::type(), ['patient', 'doctor', 'hospital', 'admin'], true)) {
                        continue;
                    }
                    if ($item['route'] === 'clinical-records' && !in_array(Auth::type(), ['patient', 'doctor'], true)) {
                        continue;
                    }
                    if ($item['route'] === 'consultations' && !in_array(Auth::type(), ['patient', 'doctor', 'hospital'], true)) {
                        continue;
                    }
                ?>
                    <a class="sidebar-link <?= ($currentRoute === $item['route'] || str_starts_with($currentRoute, $item['route'] . "/")) ? 'active' : '' ?>" href="<?= route_url($item['route']) ?>">
                        <i class="fa-solid <?= $item['icon'] ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-account-block">
                <div class="sidebar-user-card">
                    <div class="avatar-sm"><?= strtoupper(substr(Auth::user()['email'] ?? 'U', 0, 1)) ?></div>
                    <div class="sidebar-user-meta">
                        <strong><?= e(Auth::user()['email'] ?? 'User') ?></strong>
                        <span><?= e(ucfirst(Auth::type() ?? 'account')) ?></span>
                    </div>
                </div>
                <a class="sidebar-link sidebar-action-link text-danger" href="<?= route_url('logout') ?>" data-logout-link>
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?= e(__('common.logout')) ?></span>
                </a>
            </div>
        </aside>
    <?php else: ?>
        <aside class="sidebar glass-panel guest-sidebar" id="sidebar">
            <div class="sidebar-brand d-flex align-items-center justify-content-between mb-4">
                <a href="<?= route_url('home') ?>" class="brand-link text-decoration-none">
                    <span class="logo-badge"><i class="fa-solid fa-heart-pulse"></i></span>
                    <span>MediSphere</span>
                </a>
                <button class="btn btn-sm sidebar-close" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($publicNav as $navItem): ?>
                    <a class="sidebar-link <?= ($currentRoute === $navItem['route'] || str_starts_with($currentRoute, $navItem['route'] . "/")) ? 'active' : '' ?>" href="<?= route_url($navItem['route']) ?>">
                        <i class="fa-solid <?php
                            switch($navItem['route']) {
                                case 'home': echo 'fa-house'; break;
                                case 'find-healthcare': echo 'fa-map-location-dot'; break;
                                case 'map': echo 'fa-map'; break;
                                case 'blog': echo 'fa-newspaper'; break;
                                case 'guidelines': echo 'fa-book-medical'; break;
                                case 'faq': echo 'fa-circle-question'; break;
                                default: echo 'fa-link';
                            }
                        ?>"></i>
                        <span><?= e($navItem['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-account-block guest-account-block">
                <div class="guest-card">
                    <div class="guest-avatar"><i class="fa-solid fa-user-shield"></i></div>
                    <h6 class="mb-1 text-white">Guest Mode</h6>
                    <p class="text-muted x-small mb-3">Sign in to book consultations, schedule appointments, and run AI health scans.</p>
                    <a href="<?= route_url('login', ['return_to' => $guestAuthReturnTo]) ?>" class="btn btn-primary btn-sm w-100 guest-cta-btn">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="<?= route_url('register', ['return_to' => $guestAuthReturnTo]) ?>" class="btn btn-outline-light btn-sm w-100 mt-2 guest-register-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Create Account</span>
                    </a>
                </div>
            </div>
        </aside>
    <?php endif; ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <div class="main-area main-route-<?= e($currentRouteClass) ?>">
        <header class="topbar glass-panel">
            <div class="topbar-inner">
                <div class="topbar-left">
                    <button class="btn icon-btn" id="sidebarToggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="sidebar">
                        <div class="hamburger-btn-inner" id="hamburgerIcon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </button>
                    <a href="<?= route_url(Auth::check() ? 'dashboard' : 'home') ?>" class="brand-link topbar-brand text-decoration-none">
                        <span class="logo-badge"><i class="fa-solid fa-heart-pulse"></i></span>
                        <span class="brand-name">MediSphere</span>
                    </a>
                </div>

                <form method="GET" action="<?= route_url('api/search-suggestions') ?>" class="topbar-search" role="search" autocomplete="off" data-smart-search-form data-smart-search-url="<?= route_url('api/search-suggestions') ?>">
                    <input type="hidden" name="route" value="api/search-suggestions">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="globalSmartSearchInput" type="search" name="q" class="topbar-search-input" placeholder="<?= e(__('common.search_placeholder')) ?>" value="" aria-label="<?= e(__('common.search_placeholder')) ?>" aria-autocomplete="list" aria-expanded="false" aria-controls="globalSmartSearchDropdown" data-smart-search-input>
                    <div id="globalSmartSearchDropdown" class="smart-search-dropdown" role="listbox" hidden data-smart-search-dropdown></div>
                </form>

                <div class="topbar-actions">
                    <form method="POST" action="<?= route_url('language/set') ?>" class="language-switcher-form notranslate" data-no-page-translate translate="no">
                        <?= csrf_field() ?>
                        <select name="locale" class="language-select" onchange="this.form.submit()" aria-label="<?= e(__('common.language')) ?>">
                            <?php foreach (['en' => 'English'] + supported_locales() as $localeCode => $localeName): ?>
                                <option value="<?= e($localeCode) ?>" <?= current_locale() === $localeCode ? 'selected' : '' ?>><?= e($localeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <button class="btn icon-btn" id="themeToggle" type="button" title="Toggle theme" aria-label="Toggle theme"><i class="fa-solid fa-moon"></i></button>
                    <?php if (Auth::check()): ?>
                        <div class="dropdown">
                            <button class="btn icon-btn position-relative" type="button" data-bs-toggle="dropdown" aria-label="Notifications">
                                <i class="fa-regular fa-bell"></i>
                                <span class="notification-dot <?= $headerUnreadCount > 0 ? '' : 'd-none' ?>" id="notificationDot"></span>
                                <span class="notification-badge <?= $headerUnreadCount > 0 ? '' : 'd-none' ?>" id="notificationBadgeCount"><?= (int) $headerUnreadCount ?></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-menu p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><?= e(__('layout.notifications_title')) ?></h6>
                                    <a href="<?= route_url('notifications') ?>" class="small"><?= e(__('common.view_all')) ?></a>
                                </div>
                                <div id="notificationDropdownList">
                                    <?php foreach ($headerNotifications as $notification): ?>
                                        <a class="notification-item-link <?= empty($notification['is_read']) ? 'unread' : '' ?>" href="<?= e($notification['action_url'] ?: route_url('notifications')) ?>">
                                            <div class="notification-item-title"><?= e($notification['title']) ?></div>
                                            <div class="notification-item"><?= e($notification['message']) ?></div>
                                            <div class="notification-time"><?= e($notification['created_at']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if (!$headerNotifications): ?>
                                        <div class="notification-item" id="notificationEmptyState"><?= e(__('layout.no_notifications')) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (Auth::check()): ?>
                        <div class="dropdown">
                            <button class="btn profile-trigger profile-trigger-avatar-only d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-label="Account menu">
                                <div class="avatar-sm"><?= strtoupper(substr(Auth::user()['email'] ?? 'U', 0, 1)) ?></div>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                                <div class="dropdown-header profile-dropdown-header">
                                    <div class="fw-semibold"><?= e(Auth::user()['email'] ?? 'User') ?></div>
                                    <div class="small text-muted text-capitalize"><?= e(Auth::type()) ?> account</div>
                                </div>
                                <a class="dropdown-item" href="<?= route_url('profile') ?>"><i class="fa-solid fa-user-doctor me-2"></i><?= e(__('common.profile')) ?></a>
                                <a class="dropdown-item" href="<?= route_url('dashboard') ?>"><i class="fa-solid fa-gauge-high me-2"></i><?= e(__('common.dashboard')) ?></a>
                                <a class="dropdown-item" href="<?= route_url('notifications') ?>"><i class="fa-solid fa-bell me-2"></i><?= e(__('common.notifications')) ?></a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="<?= route_url('logout') ?>" data-logout-link><i class="fa-solid fa-right-from-bracket me-2"></i><?= e(__('common.logout')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="content-area page-route-<?= e($currentRouteClass) ?> <?= in_array($currentRoute, ['map'], true) ? 'content-area-fluid' : '' ?>">
            <?php if (!empty($breadcrumbs)): ?><nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb breadcrumb-premium"><?php foreach ($breadcrumbs as $crumb): ?><li class="breadcrumb-item <?= empty($crumb['url']) ? 'active' : "" ?>"><?php if (!empty($crumb['url'])): ?><a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a><?php else: ?><?= e($crumb['label']) ?><?php endif; ?></li><?php endforeach; ?></ol></nav><?php endif; ?>
            <?php if ($flash = flash('success')): ?>
                <div class="alert alert-success shadow-sm"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($flash = flash('error')): ?>
                <div class="alert alert-danger shadow-sm"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>

        <footer class="footer">
            <div class="footer-inner">
                <p class="footer-copyright">&copy; 2026 MediSphere. All rights reserved.</p>
                <nav class="footer-legal-links" aria-label="Legal">
                    <a href="<?= route_url('policy', ['slug' => 'privacy-policy']) ?>">Privacy Policy</a>
                    <a href="<?= route_url('policy', ['slug' => 'terms-of-service']) ?>">Terms &amp; Conditions</a>
                </nav>
            </div>
        </footer>
    </div>
</div>

<?php if ($showChatbot): ?>
<button class="chatbot-fab" id="chatbotToggle" type="button" aria-label="Open health assistant" aria-controls="chatbotPanel" aria-expanded="false">
    <span class="chatbot-logo chatbot-logo-fab" aria-hidden="true">
        <span class="chatbot-logo-ring"></span>
        <span class="chatbot-logo-core"><i class="fa-solid fa-heart-pulse"></i></span>
    </span>
</button>
<div class="chatbot-panel glass-panel" id="chatbotPanel" role="dialog" aria-labelledby="chatbotTitle" aria-live="polite" aria-busy="false">
    <div class="chatbot-header">
        <div class="chatbot-header-main">
            <div class="chatbot-header-avatar" aria-hidden="true">
                <span class="chatbot-logo">
                    <span class="chatbot-logo-ring"></span>
                    <span class="chatbot-logo-core"><i class="fa-solid fa-heart-pulse"></i></span>
                </span>
            </div>
            <div>
                <strong id="chatbotTitle"><?= e(__('layout.assistant_title')) ?></strong>
                <div class="text-muted small">Smart care guidance with fast access</div>
            </div>
        </div>
        <div class="chatbot-header-actions">
            <button class="btn btn-sm icon-btn" id="chatbotClear" type="button" aria-label="Clear assistant chat"><i class="fa-solid fa-rotate-left"></i></button>
            <button class="btn btn-sm icon-btn" id="chatbotClose" type="button" aria-label="Minimize assistant"><i class="fa-solid fa-minus"></i></button>
        </div>
    </div>
    <div class="chatbot-status">
        <span><i class="fa-solid fa-circle"></i> Online</span>
        <span>Project aware</span>
        <span>3 smart picks</span>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chatbot-turn assistant">
            <div class="chatbot-avatar" aria-hidden="true">
                <span class="chatbot-logo chatbot-logo-message">
                    <span class="chatbot-logo-ring"></span>
                    <span class="chatbot-logo-core"><i class="fa-solid fa-heart-pulse"></i></span>
                </span>
            </div>
            <div class="bot-msg">
                <div class="chatbot-message-meta">MediSphere Assistant</div>
                <div class="chatbot-message-text">Hi, I can help you reach the right care step quickly. Ask about booking, doctors, reports, AI scanner, emergency map, billing, login, or privacy.</div>
            </div>
        </div>
    </div>
    <div class="chatbot-quick-replies" id="chatbotSuggestions">
        <button class="btn btn-light btn-sm quick-reply" type="button" data-question="How do I book an appointment?"><?= e(__('layout.quick_book')) ?></button>
        <button class="btn btn-light btn-sm quick-reply" type="button" data-question="Find doctors near me">Find doctors</button>
        <button class="btn btn-light btn-sm quick-reply" type="button" data-question="Show emergency hospitals on map">Emergency map</button>
    </div>
    <form class="chatbot-composer" id="chatbotForm" autocomplete="off">
        <input type="text" id="chatbotInput" class="form-control" maxlength="700" placeholder="Ask for care help, reports, maps..." aria-label="Ask MediSphere assistant">
        <button class="btn btn-primary" id="chatbotSend" type="submit" aria-label="Send question"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($subscriptionModal)): ?>
    <?php require __DIR__ . '/../partials/subscription_modal.php'; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tesseract.js/5.1.0/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>window.MEDISPHERE = { baseUrl: '<?= app_url() ?>', csrf: '<?= e(App\Core\CSRF::token()) ?>', aiModelUrl: '<?= e(config('services.ai_model_url')) ?>', aiDemoMode: <?= config('features.ai_demo_mode') ? 'true' : 'false' ?>, googleMapsKey: '<?= e(config('services.google_maps_key')) ?>', locale: '<?= e(current_locale()) ?>', defaultLocale: '<?= e(config('app.default_locale', 'en')) ?>', isRtl: <?= is_rtl() ? 'true' : 'false' ?>, pageTranslatorUrl: '<?= route_url('api/translate-page') ?>', chatbotAnswers: { book: '<?= e(__('chatbot.book_answer')) ?>', reports: '<?= e(__('chatbot.reports_answer')) ?>', emergency: '<?= e(__('chatbot.emergency_answer')) ?>' } };</script>
<?php if (!empty($schemaData)): ?><script type="application/ld+json"><?= json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script><?php endif; ?>
<script src="<?= app_url('assets/js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
