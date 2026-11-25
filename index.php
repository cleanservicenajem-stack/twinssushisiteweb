<?php
/* Twins Sushi — page publique (La Carte · Twins Sushi), générée à partir de la base Supabase */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
date_default_timezone_set('Africa/Casablanca');

/* Base injoignable : mieux vaut les coordonnées du restaurant qu'une erreur PHP. */
try {
    $S = settings();
    $M = menu();
} catch (Throwable $e) {
    http_response_code(503);
    header('Retry-After: 120');
    require __DIR__ . '/inc/indisponible.php';
    exit;
}
$v = fn(string $f) => $f . '?v=' . (@filemtime(__DIR__ . '/' . $f) ?: 1);
require __DIR__ . '/inc/icons.php';

$today = date('Y-m-d');
$showRibbon = !empty($S['bandeau_texte']) && (empty($S['bandeau_fin']) || $today <= $S['bandeau_fin']);

/* ---------- Outils d'affichage ---------- */
/* Pastille : deux seulement (charte). Les anciennes valeurs de la base ne s'affichent plus. */
function bd(array $i): string { return $i['badge'] === 'vege' ? 'veggie' : ($i['badge'] === 'epice' ? 'epice' : ''); }
function badge_html(array $i): string {
    $b = bd($i);
    return $b === '' ? '' : '<span class="badge ' . $b . '">' . ($b === 'epice' ? 'Épicé' : 'Veggie') . '</span>';
}
function pcs_text(array $i): string {
    $p = !empty($i['showPcs']) ? (int)$i['pcs'] : 0;
    return $p > 0 ? $p . ($p > 1 ? ' pièces' : ' pièce') : '';
}
/* Vignette 420 px générée à côté de chaque photo du site (assets/menu/s/) ; rien pour les photos envoyées depuis l'admin. */
function thumb(string $img): string {
    return preg_match('#^assets/menu/([A-Za-z0-9_-]+\.webp)$#', $img, $m) ? 'assets/menu/s/' . $m[1] : '';
}
function img_tag(string $img, string $alt, string $sizes): string {
    $t = thumb($img);
    $src = $t !== '' ? $t : $img;
    $set = $t !== '' ? ' srcset="' . h($t) . ' 420w, ' . h($img) . ' 760w" sizes="' . $sizes . '"' : '';
    return '<img src="' . h($src) . '"' . $set . ' alt="' . h($alt) . '" loading="lazy" decoding="async">';
}
/* Boisson comprise : Menus et Formules (1 boisson au choix), Formule Pizza (2), d'après la carte. */
function drinks(array $i, string $cid): int {
    if ($i['name'] === 'Formule Pizza') return 2;
    return in_array($cid, ['menus', 'formules'], true) ? 1 : 0;
}
function drink_html(int $n): string {
    return $n ? '<span class="drink"><img src="assets/beaute/boisson-rond.webp" alt="" width="34" height="42" loading="lazy">+ ' . $n . ' boisson' . ($n > 1 ? 's' : '') . '</span>' : '';
}
function min_price(array $i): ?int {
    if (!empty($i['opts'])) return (int)min(array_column($i['opts'], 'p'));
    return $i['price'] === null || $i['price'] === '' ? null : (int)$i['price'];
}
function card_html(array $i, array $c, array $ICON): string {
    $has = $i['img'] !== '';
    $avail = !empty($i['available']);
    $big = $has && $c['id'] === 'box';
    $cls = 'p' . ($has ? '' : ' txt') . ($big ? ' big' : '') . ($avail ? '' : ' off');
    $meta = pcs_text($i);
    if ($i['pers'] !== '') $meta = $meta !== '' ? $meta . ' · ' . $i['pers'] : $i['pers'];
    $mp = min_price($i);
    $addable = $avail && $mp !== null;
    $tag = $avail ? badge_html($i) : '<span class="badge off">Indisponible</span>';
    $o = '<article class="' . $cls . '" data-code="' . h($i['code']) . '" data-badge="' . bd($i) . '" data-search="' . h($i['name'] . ' ' . $i['desc'] . ' ' . $c['title']) . '">';
    if ($has) $o .= '<div class="ph">' . $tag . img_tag($i['img'], $i['name'], $big ? '(max-width: 640px) 90vw, 560px' : '(max-width: 640px) 90vw, 300px') . '</div>';
    else $o .= $tag;
    $o .= '<div class="txt-in"><h4>' . h($i['name']) . '</h4>';
    if ($meta !== '') $o .= '<p class="pcs">' . h($meta) . '</p>';
    if ($i['desc'] !== '') $o .= '<p class="d">' . h($i['desc']) . '</p>';
    if (!empty($i['opts'])) {
        $o .= '<label class="opt"><span class="sr">Choix</span><select class="optsel" aria-label="Choix pour ' . h($i['name']) . '">';
        foreach ($i['opts'] as $k => $op) $o .= '<option value="' . $k . '">' . h($op['l']) . ' · ' . (int)$op['p'] . '</option>';
        $o .= '</select></label>';
    }
    $o .= '</div>' . drink_html(drinks($i, $c['id'])) . '<div class="foot"><span class="price">' . ($mp === null ? '<span class="tbd">Prix à venir</span>' : h(price_label($i))) . '</span>';
    $o .= '<button class="add" type="button" aria-label="Ajouter ' . h($i['name']) . ' à ma commande"' . ($addable ? '' : ' disabled') . '>' . $ICON['plus'] . '</button></div></article>';
    return $o;
}

/* ---------- Catégories et plats visibles ---------- */
$cats = [];
$nb = 0;
$minP = null;
foreach ($M['categories'] as $c) {
    if (!$c['visible']) continue;
    $items = array_values(array_filter($c['items'], fn($i) => $i['visible']));
    if (!$items) continue;
    $c['items'] = $items;
    $cats[] = $c;
    $nb += count($items);
    if ($c['id'] !== 'boissons') {
        foreach ($items as $i) {
            $p = min_price($i);
            if ($p !== null && ($minP === null || $p < $minP)) $minP = $p;
        }
    }
}
$nbCats = count($cats);
$byName = [];
foreach ($cats as $c) foreach ($c['items'] as $i) $byName[$i['name']] = $i + ['cat' => $c['title'], 'cid' => $c['id']];

/* Sélections de l'accueil : signatures et rolls de la box à composer (à valider par Houssam). */
$sigNames = ['Twins Deluxe', 'Dragon Roll', 'Crunchy Crevette', 'Okinawa Anguille'];
/* Photos de biais du shooting (assets/beaute/) ; sans photo de biais : plateau vu de dessus + loupe. */
$sigBeau = ['Dragon Roll' => 'dragon-naturel', 'Crunchy Crevette' => 'crunchy-naturel', 'Okinawa Anguille' => 'okinawa-naturel'];   /* photos naturelles, recette unique (25 nov. 2026) */
$sigLoupe = [];   /* loupes retirées (petits recadrages agrandis), Houssam 25 nov. 2026 */
/* Box à composer : le client choisit librement ses 4 rolls dans ces rubriques (Houssam, 22 novembre 2026). */
$boxCats = ['street-rolls', 'spring', 'signature-rolls', 'california', 'crousty', 'aromaki', 'maki'];
$sigs = [];
foreach ($sigNames as $n) if (isset($byName[$n]) && $byName[$n]['img'] !== '') $sigs[] = $byName[$n];
$boxItems = [];
$boxTabs = [];
foreach ($cats as $c) {
    if (!in_array($c['id'], $boxCats, true)) continue;
    foreach ($c['items'] as $i) {
        if ($i['price'] === null || !$i['available'] || !empty($i['opts'])) continue;
        $boxItems[] = $i + ['cid' => $c['id']];
        $boxTabs[$c['id']] = $c['title'];
    }
}
$imgOf = fn(string $n): string => isset($byName[$n]) ? (string)$byName[$n]['img'] : '';
$heroSlides = ['04_box', '08_california', '15_chirashi-poke', '07_signature-rolls', '11_maki', '03_crispy-rice'];

/* Données pour le panier (le HTML de la carte est déjà rendu ci-dessus par le serveur). */
$js = [];
foreach ($cats as $k => $c) {
    $js[] = ['id' => $c['id'], 'title' => $c['title'], 'num' => $k + 1, 'items' => array_map(fn($i) => [
        'code' => $i['code'], 'name' => $i['name'], 'price' => $i['price'], 'desc' => $i['desc'], 'badge' => bd($i),
        'pcs' => !empty($i['showPcs']) ? (int)$i['pcs'] : 0, 'photo' => $i['img'], 'opts' => $i['opts'], 'pers' => $i['pers'], 'drink' => drinks($i, $c['id']),
    ], $c['items'])];
}

/* Données structurées Google (Restaurant + carte) */
$daymap = ['0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday'];
$ld = [
    '@context' => 'https://schema.org', '@type' => 'Restaurant', 'name' => 'Twins Sushi',
    'url' => $S['url'], 'image' => $S['url'] . '/assets/img/og-twins-sushi.jpg', 'logo' => $S['url'] . '/assets/img/icon-512.png',
    'telephone' => $S['tel'], 'email' => $S['email'], 'servesCuisine' => ['Sushi', 'Japonaise', 'Asiatique'],
    'currenciesAccepted' => 'MAD', 'acceptsReservations' => false,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $S['rue'], 'addressLocality' => $S['ville'],
                  'postalCode' => $S['cp'], 'addressRegion' => 'Casablanca-Settat', 'addressCountry' => 'MA'],
    'openingHoursSpecification' => array_map(fn($hh) => ['@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => array_map(fn($d) => $daymap[trim($d)] ?? '', explode(',', $hh['days'])),
        'opens' => $hh['open'], 'closes' => $hh['close']], $S['horaires']),
    'sameAs' => array_values(array_filter([$S['instagram'] ?? '', $S['tiktok'] ?? '', $S['facebook'] ?? ''])),
    'hasMenu' => ['@type' => 'Menu', 'name' => 'La carte Twins Sushi', 'inLanguage' => 'fr', 'hasMenuSection' => array_map(fn($c) => [
        '@type' => 'MenuSection', 'name' => $c['title'], 'hasMenuItem' => array_map(function ($i) {
            $o = ['@type' => 'MenuItem', 'name' => $i['name'], 'description' => $i['desc']];
            $p = min_price($i);
            if ($p !== null) $o['offers'] = ['@type' => 'Offer', 'price' => (string)$p, 'priceCurrency' => 'MAD'];
            if ($i['badge'] === 'vege') $o['suitableForDiet'] = 'https://schema.org/VegetarianDiet';
            return $o;
        }, $c['items'])], $cats)],
];
$all = [];
foreach ($cats as $c) foreach ($c['items'] as $i) { $p = min_price($i); if ($p !== null) $all[] = $p; }
if ($all) $ld['priceRange'] = min($all) . ' – ' . max($all) . ' MAD';

$maps = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Twins Sushi ' . $S['rue'] . ' ' . $S['ville']);
$waOrder = 'https://wa.me/' . $S['wa'] . '?text=' . rawurlencode('Bonjour Twins Sushi, je souhaite passer une commande.');
$desc = "Twins Sushi, sushis et cuisine asiatique à Maârif, Casablanca. Rolls Tamago, Pink et Neige, makis, california, chirashi, poke, bentos et box. Livraison et à emporter, 7j/7.";
$avis = 'https://g.page/r/Cem7oJGrSNgFEAE/review';
$social = [];
foreach (['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook'] as $k => $lbl)
    if (!empty($S[$k])) $social[] = '<a href="' . h($S[$k]) . '" target="_blank" rel="noopener">' . $lbl . '</a>';
/* Une plateforme sans lien ne s'affiche pas. */
function app_link(string $label, string $url): string {
    return $url === '' ? '' : '<a class="btn btn-ligne" href="' . h($url) . '" target="_blank" rel="noopener">' . $label . '</a>';
}
$kanji = '寿司ご注文お品書き店舗情報' . implode('', array_map(fn($c) => $c['jp'], $cats));
$fontJp = 'https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@500;800&text=' . rawurlencode($kanji) . '&display=swap';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=120');
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Twins Sushi · Sushis à Maârif, Casablanca · Livraison et à emporter</title>
<meta name="description" content="<?= h($desc) ?>">
<link rel="canonical" href="<?= h($S['url']) ?>/">
<meta name="theme-color" content="#182130">
<meta name="color-scheme" content="dark light">
<script>try{var t=localStorage.getItem("twins-theme-v2");if(t==="alt"||t==="main")document.documentElement.setAttribute("data-theme",t)}catch(e){}</script>
<link rel="icon" type="image/png" href="favicon.png"><link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<meta property="og:type" content="restaurant"><meta property="og:locale" content="fr_MA"><meta property="og:site_name" content="Twins Sushi">
<meta property="og:title" content="Twins Sushi · Sushis à Maârif, Casablanca"><meta property="og:description" content="<?= h($desc) ?>">
<meta property="og:url" content="<?= h($S['url']) ?>/"><meta property="og:image" content="<?= h($S['url']) ?>/assets/img/og-twins-sushi.jpg"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="<?= h($S['url']) ?>/assets/img/og-twins-sushi.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= h($fontJp) ?>">
<link rel="stylesheet" href="<?= $v('assets/site.css') ?>">
<link rel="preload" as="image" href="assets/beaute/okinawa-naturel.webp" fetchpriority="high">
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<a class="skip" href="#carte">Aller à la carte</a>
<?php if ($showRibbon): ?><div class="ribbon" role="note"><?= h($S['bandeau_texte']) ?></div><?php endif; ?>
<?php if (!empty($S['annonce'])): ?><div class="annonce" role="note"><?= h($S['annonce']) ?></div><?php endif; ?>
<header class="bar"><div class="wrap">
<a class="brand" href="#top" aria-label="Twins Sushi, haut de page"><img class="logo-d" src="assets/img/logo-creme-240.webp" alt="Twins Sushi" width="66" height="60"><img class="logo-l" src="assets/img/logo-96.webp" alt="Twins Sushi" width="66" height="60"></a>
<nav class="nav" aria-label="Navigation principale"><a href="#carte">La carte</a><a href="#commander">Commander</a><a href="#infos">Infos pratiques</a></nav>
<button class="theme" type="button" aria-label="Changer de thème (sombre / clair)" title="Sombre / clair"><?= $ICON['moon'] ?></button>
<button class="cart-btn" type="button" data-open-cart aria-label="Voir ma commande"><?= $ICON['bag'] ?><span class="lbl">Ma commande</span><span class="n cart-count">0</span></button>
</div></header>

<main id="top">
<section class="hero3" aria-label="Twins Sushi">
<img class="h3-photo h3-cadre" src="assets/beaute/okinawa-naturel.webp" alt="Okinawa Anguille : saumon, cheese, anguille" width="1200" height="1089" fetchpriority="high">
<div class="h3-kanji" aria-hidden="true" lang="ja">双子</div>
<div class="wrap h3-in"><div class="h3-txt">
<span class="kicker">Sushis &amp; cuisine asiatique · <?= h($S['quartier']) ?>, <?= h($S['ville']) ?></span>
<h1 class="display">« Jamais l’un<br><em>sans l’autre</em> »</h1>
<p class="sub">Makis, california, rolls Tamago, Pink et Neige, chirashi, poke et bentos préparés chaque jour dans notre cuisine du <?= h($S['rue']) ?>. Livraison et à emporter, 7j/7.</p>
<div class="ctas"><a class="btn btn-plein" href="#carte">Voir la carte</a><a class="btn btn-ligne" href="<?= h($waOrder) ?>" target="_blank" rel="noopener"><?= $ICON['wa'] ?>Commander sur WhatsApp</a></div>
<div class="h2-meta"><span class="openst" hidden></span><a class="dl-link" href="assets/carte/carte-twins-sushi.pdf" download="Carte_Twins_Sushi.pdf" type="application/pdf">Télécharger la carte (PDF, 5 Mo)</a></div>
</div></div>
<?php if (isset($byName['Okinawa Anguille'])): ?><a class="h3-tag" href="#signature-rolls"><b>Okinawa Anguille</b><span><?= h($byName['Okinawa Anguille']['desc']) ?> · <?= h(price_label($byName['Okinawa Anguille'])) ?></span></a><?php endif; ?>
<a class="h2-scroll" href="#rouler"><span>Découvrir</span><i></i></a>
</section>

<section class="stats wrap"><div><b data-to="<?= $nb ?>"><?= $nb ?></b><span>plats à la carte</span></div><div><b data-to="<?= $nbCats ?>"><?= $nbCats ?></b><span>rubriques</span></div><div><b>7<small>j/7</small></b><span>livraison et à emporter</span></div><div><b><small>dès </small><?= (int)$minP ?></b><span>pour commencer</span></div></section>

<section class="sec rouler" id="rouler"><div class="kj-bg" aria-hidden="true" lang="ja">双子</div><div class="wrap">
<div class="sec-head"><h2>Trois façons de rouler</h2><span class="jp" lang="ja">双子</span><span class="rule"></span></div>
<p class="lead">Twins Street Rolls · 6 pièces.</p>
<div class="trio">
<?php foreach ([['Pink', 'roulé dans une fine tranche de blanc de dinde fumé', 'pink'], ['Tamago', 'roulé dans une omelette japonaise', 'tamago'], ['Neige', 'maki inversé, le riz à l’extérieur', 'neige']] as $k => [$tn, $td, $tf]): ?>
<a class="trio-c rv" href="#street-rolls" data-famille="<?= h($tn) ?>" aria-label="Voir les rolls <?= h($tn) ?>"><div class="trio-ph"><img src="assets/beaute/<?= $tf ?>-naturel.webp" alt="Roll <?= h($tn) ?>" width="800" height="800" loading="lazy"></div>
<span class="kicker">0<?= $k + 1 ?></span><h3><?= h($tn) ?></h3><p><?= h($td) ?></p><span class="trio-go">Voir les <?= h($tn) ?> →</span></a>
<?php endforeach; ?>
</div>
<div class="trio-cta"><a class="btn btn-plein" href="#street-rolls">Voir les Twins Street Rolls</a></div>
</div></section>

<?php $bx = []; foreach ($cats as $c) if ($c['id'] === 'box') foreach ($c['items'] as $i) if (!empty($i['showPcs'])) $bx[] = (int)$i['pcs']; ?>
<section class="band" aria-label="Les box à partager"><img src="assets/beaute/box-compo.webp" alt="Twins Deluxe, Premium Box et Party Box" width="1640" height="920" loading="lazy">
<div class="wrap band-in rv"><span class="kicker">À partager</span><h2 class="display">Les Box</h2>
<?php if ($bx): ?><p>De <?= min($bx) ?> à <?= max($bx) ?> pièces, le détail pièce par pièce.</p><?php endif; ?>
<a class="btn btn-plein" href="#box">Voir les box</a></div></section>

<section class="galerie" aria-label="La carte en images">
<?php $gal = array_values(array_filter($cats, fn($c) => $c['banner'] !== '')); foreach ([$gal, array_reverse($gal)] as $gi => $row): ?>
<div class="track<?= $gi ? ' rev' : '' ?>"><?php foreach ([$row, $row] as $dup => $rr) foreach ($rr as $c): ?><a class="gtile" href="#<?= h($c['id']) ?>" style="background-image:url(<?= h($c['banner']) ?>)"<?= $dup ? ' aria-hidden="true" tabindex="-1"' : '' ?>><span><?= h($c['title']) ?></span></a><?php endforeach; ?></div>
<?php endforeach; ?>
</section>

<?php if ($sigs): ?>
<section class="sec sigs2"><div class="wrap"><div class="sec-head"><h2>Nos signatures</h2><span class="rule"></span></div>
<?php foreach ($sigs as $k => $x): $meta = trim(pcs_text($x) . ($x['pers'] !== '' ? ' · ' . $x['pers'] : ''), ' ·'); ?>
<article class="sig2<?= $k % 2 ? ' rev' : '' ?> rv"><?php if (isset($sigBeau[$x['name']])): ?><div class="sig2-ph beau"><img src="assets/beaute/<?= $sigBeau[$x['name']] ?>.webp" alt="<?= h($x['name']) ?>" loading="lazy"></div>
<?php else: ?><div class="sig2-ph"><span class="halo"></span><img class="par" data-speed="0.05" src="<?= h($x['img']) ?>" alt="<?= h($x['name']) ?>" loading="lazy"><?php if (isset($sigLoupe[$x['name']])): ?><img class="loupe" src="assets/beaute/<?= $sigLoupe[$x['name']] ?>.webp" alt="" loading="lazy"><?php endif; ?></div><?php endif; ?>
<div class="sig2-txt"><span class="num">0<?= $k + 1 ?></span><span class="kicker"><?= h($x['cat']) ?></span><h3><?= h($x['name']) ?></h3><?php if ($meta !== ''): ?><p class="meta"><?= h($meta) ?></p><?php endif; ?><p><?= h($x['desc']) ?></p>
<div class="pr"><span class="price"><?= h(price_label($x)) ?></span><button class="btn btn-plein" type="button" data-qv="<?= h($x['code']) ?>">Ajouter à ma commande</button><a class="lnk" href="#<?= h($x['cid']) ?>">Voir sur la carte</a></div></div></article>
<?php endforeach; ?>
</div></section>
<?php endif; ?>

<?php if (count($boxItems) >= 4): ?>
<section class="sec box" id="composer"><div class="wrap"><div class="sec-head"><h2>Composez votre box</h2><span class="rule"></span></div>
<p class="lead">Choisissez vos 4 rolls parmi toute la carte, on prépare la box. Le prix, c’est simplement celui de chaque roll : pas de supplément.</p>
<div class="boxc"><div class="picks-wrap"><div class="bx-tabs" role="tablist" aria-label="Familles de rolls"><?php $first = true; foreach ($boxTabs as $tid => $tt): ?><button type="button" class="bx-tab" role="tab" data-bx="<?= h($tid) ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= h($tt) ?></button><?php $first = false; endforeach; ?></div><div class="picks">
<?php foreach ($boxItems as $x): ?><button class="pick" type="button" data-code="<?= h($x['code']) ?>" data-cat="<?= h($x['cid']) ?>" aria-disabled="false"><span class="q">×0</span><span class="minus" role="button" aria-label="Retirer un <?= h($x['name']) ?>" tabindex="0">−</span><?php if ($x['img'] !== ''): ?><img src="<?= h(thumb($x['img']) ?: $x['img']) ?>" alt="" loading="lazy"><?php endif; ?><b><?= h($x['name']) ?></b><i><?= (int)$x['price'] ?></i></button><?php endforeach; ?>
</div></div>
<div class="bento"><h3>Ma box</h3><div class="cap"><span id="box-count">0/4</span> rolls choisis · <button type="button" id="box-clear" class="lnk">tout retirer</button></div>
<div class="slots"><div class="slot"></div><div class="slot"></div><div class="slot"></div><div class="slot"></div></div>
<div class="tot"><span>Total</span><b id="box-total">0 DH</b></div><a class="btn btn-plein" id="box-add" href="#panier" aria-disabled="true">Ajouter à ma commande</a>
<p class="note">Touchez un roll dans la box pour le retirer. Les rolls s’ajoutent ensuite à votre commande, envoyée sur WhatsApp.</p><p class="box-msg" id="box-msg" role="status" aria-live="polite"></p></div></div>
</div></section>
<?php endif; ?>

<section class="sec" id="commander"><div class="wrap"><div class="sec-head"><h2>Commandez</h2><span class="jp" lang="ja">ご注文</span><span class="rule"></span></div>
<div class="order-grid"><div class="o-card o-rouge"><h3>Livré chez vous</h3><p>Commandez sur WhatsApp, au prix de la carte, sans commission.</p>
<div class="row"><a class="btn btn-plein" href="<?= h($waOrder) ?>" target="_blank" rel="noopener"><?= $ICON['wa'] ?>WhatsApp</a><a class="btn btn-ligne" href="tel:<?= h($S['tel']) ?>"><?= $ICON['tel'] ?><?= h($S['tel_affiche']) ?></a>
<?php foreach (['Glovo' => 'glovo', 'Yassir' => 'yassir', 'Kool' => 'kool'] as $nom => $cle) echo app_link($nom, (string)($S[$cle] ?? '')); ?></div></div>
<div class="o-card o-creme"><h3>À emporter</h3><p class="addr"><?= h($S['rue']) ?> · <?= h($S['quartier']) ?> · <?= h($S['ville']) ?></p>
<p>À récupérer directement à la cuisine, au même prix. Prévenez-nous sur WhatsApp ou par téléphone, c’est prêt à votre arrivée.</p></div></div>
<p class="tip"><b>Astuce</b> Composez votre commande avec le bouton + sur la carte : on reçoit la liste complète sur WhatsApp, prête à préparer.</p>
</div></section>

<section class="sec sec-carte" id="carte" aria-labelledby="h-carte"><div class="wrap"><div class="sec-head"><h2 id="h-carte">La carte</h2><span class="jp" lang="ja">お品書き</span><span class="rule"></span><a class="btn btn-ligne dl-btn" href="assets/carte/carte-twins-sushi.pdf" download="Carte_Twins_Sushi.pdf" type="application/pdf">Carte en PDF</a></div>
<div class="menu-tools"><div class="tools-row"><label class="search" for="q"><?= $ICON['search'] ?><span class="sr">Rechercher un plat</span><input id="q" type="search" placeholder="Saumon, crevette, avocat…" autocomplete="off" enterkeyhint="search"></label>
<span class="qcount" id="q-count" aria-live="polite"></span><div class="filters"><button class="fchip vege" type="button" data-f="veggie" aria-pressed="false"><i></i>Veggie</button><button class="fchip epice" type="button" data-f="epice" aria-pressed="false"><i></i>Épicé</button></div></div>
<nav class="cats" aria-label="Rubriques de la carte"><?php foreach ($cats as $c): ?><a href="#<?= h($c['id']) ?>"><?= h($c['title']) ?></a><?php endforeach; ?></nav></div></div>
<div class="wrap">
<?php foreach ($cats as $k => $c): ?>
<section class="cat" id="<?= h($c['id']) ?>" aria-labelledby="h-<?= h($c['id']) ?>">
<header class="cat-head"<?= $c['banner'] !== '' ? ' style="--bn:url(/' . h(ltrim($c['banner'], '/')) . ')"' : '' ?>><div class="cat-in"><span class="num"><?= str_pad((string)($k + 1), 2, '0', STR_PAD_LEFT) ?></span><h3 id="h-<?= h($c['id']) ?>"><?= h($c['title']) ?></h3><?php if ($c['jp'] !== ''): ?><span class="kj" lang="ja" aria-hidden="true"><?= h($c['jp']) ?></span><?php endif; ?><?php if ($c['note'] !== ''): ?><p class="note"><?= h($c['note']) ?></p><?php endif; ?></div></header>
<div class="grid">
<?php foreach ($c['items'] as $i) echo card_html($i, $c, $ICON), "\n"; ?>
</div></section>
<?php endforeach; ?>
<p class="empty" hidden>Aucun plat ne correspond à votre recherche.</p>
</div></section>

<section class="sec infos" id="infos"><div class="wrap"><div class="sec-head"><h2>Infos pratiques</h2><span class="jp" lang="ja">店舗情報</span><span class="rule"></span></div>
<div class="info-grid">
<div class="info"><h3>Adresse</h3><p><?= h($S['rue']) ?><br><?= h($S['quartier']) ?>, <?= h($S['cp']) ?> <?= h($S['ville']) ?></p><p><a href="<?= h($maps) ?>" target="_blank" rel="noopener">Itinéraire Google Maps</a></p></div>
<div class="info"><h3>Horaires</h3><p><span class="openst" hidden></span></p><table class="hours"><tbody><?php foreach ($S['horaires'] as $hh): ?><tr data-days="<?= h($hh['days']) ?>"><td><?= h($hh['label']) ?></td><td><?= h($hh['text']) ?></td></tr><?php endforeach; ?></tbody></table><p class="small">Livraison et à emporter, 7j/7.</p></div>
<div class="info"><h3>Contact</h3>
<?php if (preg_replace('/\D/', '', (string)$S['tel']) === preg_replace('/\D/', '', (string)$S['wa'])): /* un seul numéro (Houssam, 25 nov. 2026) */ ?>
<p>Téléphone et WhatsApp : <a href="tel:<?= h($S['tel']) ?>"><?= h($S['tel_affiche']) ?></a> · <a href="https://wa.me/<?= h($S['wa']) ?>" target="_blank" rel="noopener">écrire sur WhatsApp</a>
<?php else: ?>
<p>Téléphone : <a href="tel:<?= h($S['tel']) ?>"><?= h($S['tel_affiche']) ?></a><br>WhatsApp : <a href="https://wa.me/<?= h($S['wa']) ?>" target="_blank" rel="noopener"><?= h($S['wa_affiche']) ?></a>
<?php endif; ?><?php if (!empty($S['email'])): ?><br>E-mail : <a href="mailto:<?= h($S['email']) ?>"><?= h($S['email']) ?></a><?php endif; ?></p>
<p><?= implode(' · ', array_merge($social, ['<a href="' . h($avis) . '" target="_blank" rel="noopener">Laisser un avis Google</a>'])) ?></p>
<p class="small">Allergies ou intolérances ? Précisez-le à la commande. Allergènes sur demande : poisson, crustacés, gluten, sésame, lait, œuf, soja.</p></div>
</div>
<div class="map" data-src="https://www.google.com/maps?q=<?= rawurlencode($S['rue'] . ' ' . $S['ville']) ?>&amp;output=embed"><button type="button" class="btn btn-ligne map-load"><?= $ICON['pin'] ?>Afficher le plan</button></div>
</div></section>
</main>

<footer class="foot2"><div class="wrap">
<img class="f-logo" src="assets/img/logo-creme-240.webp" alt="Twins Sushi" width="120" height="110" loading="lazy">
<p class="f-slogan">« Jamais l’un sans l’autre »</p>
<div class="links"><a href="#carte">La carte</a><a href="#commander">Commander</a><a href="#infos">Infos pratiques</a><a href="assets/carte/carte-twins-sushi.pdf" download="Carte_Twins_Sushi.pdf">Carte en PDF</a><?= implode('', $social) ?></div>
<p class="f-small"><?= h($S['rue']) ?> · <?= h($S['quartier']) ?> · <?= h($S['ville']) ?> · <a href="<?= h($waOrder) ?>" target="_blank" rel="noopener">WhatsApp <?= h($S['wa_affiche']) ?></a></p>
<p class="f-small">Prix en dirhams TTC. © <?= date('Y') ?> Twins Sushi</p>
</div></footer>

<div class="mbar"><a class="btn btn-wa" href="<?= h($waOrder) ?>" target="_blank" rel="noopener"><?= $ICON['wa'] ?>Commander</a><button class="cart-btn" type="button" data-open-cart aria-label="Voir ma commande"><?= $ICON['bag'] ?><span class="n cart-count">0</span></button></div>
<script>window.TWINS={hours:<?= json_encode(array_map(fn($hh) => ['days' => $hh['days'], 'open' => $hh['open'], 'close' => $hh['close']], $S['horaires']), JSON_HEX_TAG) ?>,wa:<?= json_encode($S['wa']) ?>,waShown:<?= json_encode($S['wa_affiche']) ?>,rue:<?= json_encode($S['rue']) ?>,ville:<?= json_encode($S['ville']) ?>};window.TWINS_CARTE=<?= json_encode($js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<script src="<?= $v('assets/site.js') ?>" defer></script>
</body>
</html>
