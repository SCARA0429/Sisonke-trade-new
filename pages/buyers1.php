<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$assetBase = htmlspecialchars(str_replace(' ', '%20', SISONKE_BASE_URL), ENT_QUOTES, 'UTF-8');
$cssVersion = filemtime(dirname(__DIR__) . '/assets/css/style.css') ?: time();
$currentLanguage = sisonke_current_language();
$languageOptions = sisonke_supported_languages();

$dbCampaigns = sisonke_fetch_campaigns($pdo, '', 3);
$campaigns = array_map(static function (array $campaign): array {
    return [
        'tag' => sisonke_t('campaign_committed', ['progress' => sisonke_campaign_progress($campaign)]),
        'title' => (string) $campaign['product_name'],
        'description' => sisonke_content_t($campaign['description']),
        'price' => sisonke_money($campaign['campaign_price']),
        'image' => sisonke_campaign_image_url($campaign),
        'href' => SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . (int) $campaign['campaign_id'],
    ];
}, $dbCampaigns);

$reasons = [
    [
        'title' => sisonke_t('home_reason_1_title'),
        'text' => sisonke_t('home_reason_1_text'),
    ],
    [
        'title' => sisonke_t('home_reason_2_title'),
        'text' => sisonke_t('home_reason_2_text'),
    ],
    [
        'title' => sisonke_t('home_reason_3_title'),
        'text' => sisonke_t('home_reason_3_text'),
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(sisonke_html_language(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(sisonke_t('home_page_title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assetBase ?>/assets/css/style.css?v=<?= $cssVersion ?>">
</head>
<body class="buyer-page">
    <header class="buyer-topbar" aria-label="Header top navigation">
        <a class="buyer-brand" href="<?= $assetBase ?>/pages/buyers1.php">SISONKE TRADE</a>
        <nav class="buyer-nav" aria-label="Primary navigation">
            <a class="is-active" href="<?= $assetBase ?>/pages/buyers1.php"><?= htmlspecialchars(sisonke_t('home_nav_home'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="<?= $assetBase ?>/pages/campaigns.php"><?= htmlspecialchars(sisonke_t('home_nav_shop'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="#deals"><?= htmlspecialchars(sisonke_t('home_nav_deals'), ENT_QUOTES, 'UTF-8') ?></a>
        </nav>
        <div class="buyer-actions">
            <div class="buyer-language-switcher" aria-label="<?= htmlspecialchars(sisonke_t('language_selector_label'), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($languageOptions as $code => $language): ?>
                    <a class="<?= $currentLanguage === $code ? 'is-active' : '' ?>"
                       href="<?= htmlspecialchars(sisonke_language_url($code), ENT_QUOTES, 'UTF-8') ?>"
                       lang="<?= htmlspecialchars($language['html'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($language['short'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <form class="buyer-search" action="<?= $assetBase ?>/pages/campaigns.php" method="get">
                <label class="visually-hidden" for="buyer-search-input"><?= htmlspecialchars(sisonke_t('home_search_goods'), ENT_QUOTES, 'UTF-8') ?></label>
                <input id="buyer-search-input" type="search" name="q" placeholder="<?= htmlspecialchars(sisonke_t('home_search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" aria-label="<?= htmlspecialchars(sisonke_t('marketplace_search_button'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M10.5 4a6.5 6.5 0 0 1 5.18 10.43l4.45 4.44-1.41 1.41-4.44-4.45A6.5 6.5 0 1 1 10.5 4Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" />
                    </svg>
                </button>
            </form>
            <a class="buyer-icon-link" href="<?= $assetBase ?>/pages/campaigns.php" aria-label="<?= htmlspecialchars(sisonke_t('home_cart'), ENT_QUOTES, 'UTF-8') ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M7.2 19.6a1.8 1.8 0 1 1-3.6 0 1.8 1.8 0 0 1 3.6 0Zm11.2 0a1.8 1.8 0 1 1-3.6 0 1.8 1.8 0 0 1 3.6 0ZM5.48 5l1.74 8.7h9.28l1.6-5.7H8.22l-.4-2H19v2H8.64l.8 3.7h5.54l.48-1.7H18l-1.28 4.7H5.58L3.84 6H2V4h3.07c.2 0 .37.08.41.28V5Z" />
                </svg>
            </a>
            <a class="buyer-icon-link" href="<?= $assetBase ?>/pages/dashboard.php" aria-label="<?= htmlspecialchars(sisonke_t('home_account'), ENT_QUOTES, 'UTF-8') ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M12 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Zm0-2a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm8 11h-2v-1.5c0-2.1-2.74-3.5-6-3.5s-6 1.4-6 3.5V21H4v-1.5c0-3.35 3.58-5.5 8-5.5s8 2.15 8 5.5V21Z" />
                </svg>
            </a>
        </div>
    </header>

    <main class="buyer-main">
        <section class="buyer-hero">
            <div class="buyer-hero-pattern" aria-hidden="true"></div>
            <div class="buyer-shell buyer-hero-inner">
                <p class="buyer-kicker"><?= htmlspecialchars(sisonke_t('home_kicker'), ENT_QUOTES, 'UTF-8') ?></p>
                <h1><?= htmlspecialchars(sisonke_t('home_buy_big'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="buyer-hero-lower">
                    <div>
                        <p class="buyer-subline"><?= htmlspecialchars(sisonke_t('home_save'), ENT_QUOTES, 'UTF-8') ?> <span><?= htmlspecialchars(sisonke_t('home_together'), ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p class="buyer-copy"><?= htmlspecialchars(sisonke_t('home_copy'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <a class="buyer-button" href="<?= $assetBase ?>/pages/register.php"><?= htmlspecialchars(sisonke_t('home_start_buying'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </section>

        <section class="buyer-how" id="how-it-works" aria-labelledby="how-it-works-title">
            <div class="buyer-shell">
                <div class="buyer-section-head">
                    <h2 id="how-it-works-title">How it works</h2>
                </div>
                <div class="buyer-how-grid">
                    <article class="buyer-how-step">
                        <span>01</span>
                        <h3>Join a campaign</h3>
                        <p>Choose a bulk deal with your community and reserve the quantity you need.</p>
                    </article>
                    <article class="buyer-how-step">
                        <span>02</span>
                        <h3>Pay into escrow</h3>
                        <p>Your payment is held securely while the group reaches the campaign target.</p>
                    </article>
                    <article class="buyer-how-step">
                        <span>03</span>
                        <h3>Collect together</h3>
                        <p>When the target is met, the seller fulfils the order and buyers confirm delivery.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="buyer-campaigns" id="deals">
            <div class="buyer-shell">
                <div class="buyer-section-head">
                    <h2><?= htmlspecialchars(sisonke_t('home_featured_campaigns'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <a href="<?= $assetBase ?>/pages/campaigns.php"><?= htmlspecialchars(sisonke_t('home_view_all'), ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">&rarr;</span></a>
                </div>
                <?php if ($campaigns === []): ?>
                    <div class="st-empty"><?= htmlspecialchars(sisonke_t('marketplace_no_campaigns'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                <div class="buyer-card-grid">
                    <?php foreach ($campaigns as $campaign): ?>
                        <article class="buyer-card">
                            <div class="buyer-card-media">
                                <span><?= htmlspecialchars($campaign['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                                <img src="<?= htmlspecialchars($campaign['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            </div>
                            <div class="buyer-card-body">
                                <h3><?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($campaign['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <a class="buyer-price-row" href="<?= sisonke_e(str_replace(' ', '%20', $campaign['href'])) ?>">
                                    <span>
                                        <small><?= htmlspecialchars(sisonke_t('home_from'), ENT_QUOTES, 'UTF-8') ?></small>
                                        <strong><?= htmlspecialchars($campaign['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="buyer-community">
            <div class="buyer-shell buyer-community-grid">
                <div class="buyer-community-copy">
                    <h2><?= htmlspecialchars(sisonke_t('home_why'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="buyer-reasons">
                        <?php foreach ($reasons as $index => $reason): ?>
                            <article class="buyer-reason">
                                <span><?= $index + 1 ?></span>
                                <div>
                                    <h3><?= htmlspecialchars($reason['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars($reason['text'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="buyer-mural" aria-hidden="true">
                    <div class="buyer-mural-tile buyer-mural-red">
                        <span>Sisonke</span>
                    </div>
                    <div class="buyer-mural-tile buyer-mural-photo"></div>
                    <div class="buyer-mural-tile buyer-mural-green"></div>
                    <div class="buyer-mural-tile buyer-mural-coral">
                        <span><?= htmlspecialchars(sisonke_t('home_mural_text'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="buyer-cta">
            <div class="buyer-shell">
                <h2><?= htmlspecialchars(sisonke_t('home_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(sisonke_t('home_cta_text'), ENT_QUOTES, 'UTF-8') ?></p>
                <form class="buyer-newsletter" action="<?= $assetBase ?>/pages/register.php" method="get">
                    <label class="visually-hidden" for="buyer-email"><?= htmlspecialchars(sisonke_t('home_email_placeholder'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input id="buyer-email" type="email" name="email" placeholder="<?= htmlspecialchars(sisonke_t('home_email_placeholder'), ENT_QUOTES, 'UTF-8') ?>" required>
                    <button type="submit"><?= htmlspecialchars(sisonke_t('home_join_now'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </section>
    </main>

    <footer class="buyer-footer">
        <div class="buyer-shell buyer-footer-top">
            <div>
                <h2>Sisonke Trade</h2>
                <p><?= htmlspecialchars(sisonke_t('home_footer_copy'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <nav aria-label="Footer quick links">
                <h3><?= htmlspecialchars(sisonke_t('home_quick_links'), ENT_QUOTES, 'UTF-8') ?></h3>
                <a href="#deals"><?= htmlspecialchars(sisonke_t('home_bulk_deals'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="#deals"><?= htmlspecialchars(sisonke_t('home_how_it_works'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= $assetBase ?>/seller/dashboard.php"><?= htmlspecialchars(sisonke_t('home_supplier_portal'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= $assetBase ?>/pages/register.php"><?= htmlspecialchars(sisonke_t('home_contact_us'), ENT_QUOTES, 'UTF-8') ?></a>
            </nav>
            <div>
                <h3><?= htmlspecialchars(sisonke_t('home_contact'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p>Johannesburg, Gauteng<br>support@sisonketrade.local</p>
                <div class="buyer-socials" aria-label="Social links">
                    <a href="#" aria-label="Facebook">f</a>
                    <a href="#" aria-label="Twitter">t</a>
                    <a href="#" aria-label="Instagram">i</a>
                </div>
            </div>
        </div>
        <div class="buyer-shell buyer-footer-bottom">
            <small>&copy; <?= date('Y') ?> Sisonke Trade. <?= htmlspecialchars(sisonke_t('home_all_rights'), ENT_QUOTES, 'UTF-8') ?></small>
            <small><?= htmlspecialchars(sisonke_t('home_built_for_community'), ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    </footer>
</body>
</html>
