<?php
$tmdbApiKey = getenv('TMDB_API_KEY') ?: '4e44d9029b1270a757cddc766a1bcb63';
$siteUrl = 'https://reel.gt.tc/';
$shareId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$shareId = ($shareId && $shareId > 0) ? $shareId : null;
$title = 'Reel';
$description = 'Discover movies and TV shows on Reel.';
$image='https://github.com/user-attachments/assets/dd77a8ae-79c1-4e67-9cfa-03f7be95b11f';
$canonical = $siteUrl;

function tmdb_request($path, $apiKey) {
    $url = 'https://api.themoviedb.org/3' . $path . '?api_key=' . rawurlencode($apiKey) . '&language=en-US';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) return null;
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\n",
            ]
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) return null;
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($shareId) {
    $media = tmdb_request('/movie/' . rawurlencode((string)$shareId), $tmdbApiKey);
    if (!$media || empty($media['id'])) {
        $media = tmdb_request('/tv/' . rawurlencode((string)$shareId), $tmdbApiKey);
    }
    if ($media && !empty($media['id'])) {
        $title = trim($media['title'] ?? $media['name'] ?? 'Reel');
        $description = trim(preg_replace('/\s+/', ' ', $media['overview'] ?? ''));
        if ($description === '') $description = 'Discover ' . $title . ' on Reel.';
        $imagePath = $media['backdrop_path'] ?? $media['poster_path'] ?? null;
        if ($imagePath) $image = 'https://image.tmdb.org/t/p/w1280' . $imagePath;
        $canonical = $siteUrl . '?id=' . rawurlencode((string)$media['id']);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#050505">
<title><?= h($title) ?><?= $title !== 'Reel' ? ' · Reel' : '' ?></title>
<meta name="description" content="<?= h($description) ?>">
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta property="og:image" content="<?= h($image) ?>">
<meta property="og:image:alt" content="<?= h($title) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1280">
<meta property="og:image:height" content="720">
<meta property="og:site_name" content="Reel">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($title) ?>">
<meta name="twitter:description" content="<?= h($description) ?>">
<meta name="twitter:image" content="<?= h($image) ?>">
<link rel="icon" href="icon.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
:root{--bg:#050505;--fg:#f5f5f2;--muted:rgba(255,255,255,.45)}
*{box-sizing:border-box}
html,body{width:100%;height:100%;margin:0;overflow:hidden;background:var(--bg);color:var(--fg);font-family:"Manrope",ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body{user-select:none;-webkit-user-select:none}
button,input,select{font:inherit}
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:1px solid rgba(255,255,255,.5);outline-offset:3px}
#app{position:fixed;inset:0;overflow:hidden;background:#050505;contain:strict}
#app::before{content:"";position:absolute;inset:0;z-index:3;pointer-events:none;background:radial-gradient(circle at 50% 44%,rgba(255,255,255,.045),transparent 34%),radial-gradient(circle at 50% 50%,transparent 45%,rgba(0,0,0,.34) 74%,rgba(0,0,0,.94) 126%)}
#movieCanvas{position:absolute;inset:0;width:100%;height:100%;display:block;cursor:grab;touch-action:none;transform:translateZ(0)}
#movieCanvas.dragging{cursor:grabbing}
.vignette{position:absolute;inset:0;z-index:5;pointer-events:none;background:radial-gradient(ellipse at center,transparent 22%,rgba(0,0,0,.03) 42%,rgba(0,0,0,.42) 77%,rgba(0,0,0,.92) 126%)}
.no-vignette .vignette{display:none}.no-depth #movieCanvas{filter:none}.no-ui-blur .filters-panel,.no-ui-blur .settings-panel,.no-ui-blur .search-box,.no-ui-blur .search-tag,.no-ui-blur .player-control{backdrop-filter:none;-webkit-backdrop-filter:none}
.nav{position:fixed;inset:0 0 auto;z-index:100;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;pointer-events:none}
.nav>*{pointer-events:auto}
.logo{display:flex;align-items:center;gap:9px;color:#fff;text-decoration:none}
.logo-mark{position:relative;width:21px;height:21px;border:1px solid rgba(255,255,255,.72);border-radius:50%;background:rgba(255,255,255,.02)}
.logo-mark::after{content:"";position:absolute;left:7px;top:7px;width:5px;height:5px;border-radius:50%;background:#fff}
.logo-text{font-size:15px;font-weight:650;letter-spacing:-.045em}
.nav-right{display:flex;gap:7px}
.nav-btn,.hero-btn,.details-action,.modal-close{border:1px solid rgba(255,255,255,.12);background:rgba(7,7,7,.72);color:rgba(255,255,255,.78);cursor:pointer;transition:background .2s ease,border-color .2s ease,transform .2s ease}
.nav-btn{height:38px;padding:0 14px;border-radius:999px;font-size:11px;font-weight:500}
.nav-btn:hover,.details-action:hover,.modal-close:hover{background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.22)}
.nav-btn[aria-expanded="true"]{background:rgba(8,8,8,.96);border-color:rgba(255,255,255,.22);color:#fff}

.icon-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;-webkit-tap-highlight-color:transparent}
.icon-button svg{width:15px;height:15px;stroke-width:1.7;flex:0 0 auto}
.nav-btn.icon-button{width:38px;padding:0}
.hero-btn.icon-button{width:38px;padding:0}
.details-action.icon-button{width:42px;padding:0}
.modal-close.icon-button,.player-control.icon-button{display:inline-flex;align-items:center;justify-content:center}
.modal-close.icon-button svg{width:16px;height:16px}
.player-control.icon-button svg{width:14px;height:14px}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}

.settings-wrap{position:relative}
.settings-panel{position:absolute;right:-6px;top:46px;width:350px;padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:#030303;box-shadow:0 32px 90px rgba(0,0,0,.78),0 10px 34px rgba(0,0,0,.46),inset 0 1px 0 rgba(255,255,255,.025);opacity:0;visibility:hidden;transform:translateY(-8px) scale(.975);transform-origin:top right;transition:opacity .18s ease,transform .22s cubic-bezier(.22,1,.36,1),visibility .18s ease;backdrop-filter:blur(26px);-webkit-backdrop-filter:blur(26px);overflow:hidden}
.settings-panel.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
.settings-head{display:flex;align-items:center;justify-content:space-between;padding:2px 2px 13px 4px}
.settings-head-copy{display:grid;gap:2px}
.settings-title{font-size:12px;font-weight:600;letter-spacing:-.025em;color:#fff}
.settings-subtitle{font-size:8px;line-height:1.35;color:rgba(255,255,255,.36)}
.settings-icon{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.07);border-radius:50%;background:#050505;color:rgba(255,255,255,.7)}
.settings-icon svg{width:13px;height:13px;stroke-width:1.7}
.settings-group{display:grid;gap:7px}
.settings-section-label{padding:2px 6px 0;font-size:8px;font-weight:600;color:rgba(255,255,255,.28);letter-spacing:.02em}
.settings-list{display:flex;flex-direction:column;gap:6px}
.settings-item{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:43px;padding:5px 6px 5px 13px;border:1px solid rgba(255,255,255,.075);border-radius:999px;background:#060606;box-shadow:inset 0 1px 0 rgba(255,255,255,.015);transition:border-color .18s ease,background .18s ease,transform .18s ease}
.settings-item:hover{background:#090909;border-color:rgba(255,255,255,.13);transform:translateY(-1px)}
.settings-label-wrap{display:flex;align-items:center;gap:9px;min-width:0}
.settings-label-icon{width:24px;height:24px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;border:1px solid rgba(255,255,255,.06);border-radius:50%;background:#030303;color:rgba(255,255,255,.58)}
.settings-label-icon svg{width:12px;height:12px;stroke-width:1.65}
.settings-label-copy{display:grid;gap:2px;min-width:0}
.settings-label{font-size:9px;font-weight:500;color:rgba(255,255,255,.74);letter-spacing:-.01em}
.settings-description{font-size:7px;color:rgba(255,255,255,.28);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.settings-toggle{position:relative;width:38px;height:22px;flex:0 0 auto;padding:0;border:1px solid rgba(255,255,255,.09);border-radius:999px;background:#020202;cursor:pointer;transition:background .18s ease,border-color .18s ease}
.settings-toggle span{position:absolute;left:3px;top:3px;width:14px;height:14px;border-radius:50%;background:#171717;transition:transform .18s cubic-bezier(.22,1,.36,1),background .18s ease}
.settings-toggle[aria-checked="true"]{background:#fff;border-color:#fff}
.settings-toggle[aria-checked="true"] span{transform:translateX(16px);background:#000}
.settings-select-item{padding-right:5px}
.settings-select-wrap{position:relative;width:112px;height:31px}
.settings-select{width:100%;height:31px;padding:0 27px 0 12px;appearance:none;-webkit-appearance:none;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:#020202;color:#fff;outline:none;font-size:9px;font-weight:500;color-scheme:dark;cursor:pointer}
.settings-select:hover{background:#050505;border-color:rgba(255,255,255,.12)}
.settings-select:focus{border-color:rgba(255,255,255,.22)}
.settings-select option{background:#020202;color:#fff}
.settings-arrow{position:absolute;right:11px;top:50%;width:6px;height:6px;border-right:1px solid rgba(255,255,255,.5);border-bottom:1px solid rgba(255,255,255,.5);transform:translateY(-66%) rotate(45deg);pointer-events:none}
.settings-divider{height:1px;margin:10px 2px;border-radius:1px;background:rgba(255,255,255,.055)}
.settings-footer{display:flex;align-items:center;justify-content:space-between;padding:3px 3px 1px}
.settings-footer-note{font-size:7px;color:rgba(255,255,255,.22)}
.settings-footer-button{height:28px;padding:0 10px;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:#050505;color:rgba(255,255,255,.5);font-size:8px;cursor:pointer;transition:background .18s ease,border-color .18s ease,color .18s ease}
.settings-footer-button:hover{background:#090909;border-color:rgba(255,255,255,.13);color:#fff}
.filters-wrap{position:relative}.filters-panel{position:absolute;right:-6px;top:46px;width:350px;padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:#030303;box-shadow:0 32px 90px rgba(0,0,0,.78),0 10px 34px rgba(0,0,0,.46),inset 0 1px 0 rgba(255,255,255,.025);opacity:0;visibility:hidden;transform:translateY(-8px) scale(.975);transform-origin:top right;transition:opacity .18s ease,transform .22s cubic-bezier(.22,1,.36,1),visibility .18s ease;backdrop-filter:blur(26px);-webkit-backdrop-filter:blur(26px);overflow:hidden}
.filters-panel.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
.filters-head{display:flex;align-items:center;justify-content:space-between;padding:2px 2px 13px 4px}.filters-head-copy{display:grid;gap:2px}.filters-title{font-size:12px;font-weight:600;letter-spacing:-.025em;color:#fff}.filters-subtitle{font-size:8px;line-height:1.35;color:rgba(255,255,255,.36)}.filters-icon{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.07);border-radius:50%;background:#050505;color:rgba(255,255,255,.7)}.filters-icon svg{width:13px;height:13px;stroke-width:1.7}
.filter-list{display:flex;flex-direction:column;gap:6px}.filter-item{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:43px;padding:5px 6px 5px 13px;border:1px solid rgba(255,255,255,.075);border-radius:999px;background:#060606;box-shadow:inset 0 1px 0 rgba(255,255,255,.015);transition:border-color .18s ease,background .18s ease,transform .18s ease}.filter-item:hover{background:#090909;border-color:rgba(255,255,255,.13);transform:translateY(-1px)}
.filter-label-wrap{display:flex;align-items:center;gap:9px;min-width:0}.filter-label-icon{width:24px;height:24px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;border:1px solid rgba(255,255,255,.06);border-radius:50%;background:#030303;color:rgba(255,255,255,.58)}.filter-label-icon svg{width:12px;height:12px;stroke-width:1.65}.filter-field-label{padding-left:0;font-size:9px;font-weight:500;letter-spacing:-.01em;color:rgba(255,255,255,.74)}
.filter-control{position:relative;width:138px;height:31px;flex:0 0 138px;border-radius:999px;background:#020202}.filter-control:focus-within{background:#050505}.filter-select{width:100%;height:31px;padding:0 27px 0 12px;appearance:none;-webkit-appearance:none;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:transparent;color:#fff;outline:none;font-size:9px;font-weight:500;color-scheme:dark;cursor:pointer}.filter-select:hover{border-color:rgba(255,255,255,.12)}.filter-select:focus{border-color:rgba(255,255,255,.22)}.filter-select option{background:#020202;color:#fff}.filter-arrow{position:absolute;right:11px;top:50%;width:6px;height:6px;border-right:1px solid rgba(255,255,255,.5);border-bottom:1px solid rgba(255,255,255,.5);transform:translateY(-66%) rotate(45deg);pointer-events:none}.filter-control:focus-within .filter-arrow{border-color:#fff}
.filters-divider{height:1px;margin:10px 2px;border-radius:1px;background:rgba(255,255,255,.055)}.filter-actions{display:flex;align-items:center;justify-content:flex-end;gap:6px;padding:3px}.filter-reset,.filter-apply{height:31px;border-radius:999px;font-size:9px;font-weight:500;cursor:pointer;transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease}.filter-reset{padding:0 12px;border:1px solid rgba(255,255,255,.07);background:#050505;color:rgba(255,255,255,.48)}.filter-reset:hover{background:#090909;border-color:rgba(255,255,255,.13);color:#fff}.filter-apply{padding:0 15px;border:1px solid rgba(255,255,255,.10);background:#fff;color:#000}.filter-apply:hover{background:#f2f2f2;border-color:#fff;transform:translateY(-1px)}.filter-apply:active,.filter-reset:active{transform:translateY(0)}
.center{position:fixed;left:50%;top:50%;z-index:40;width:min(470px,90vw);transform:translate(-50%,-50%);text-align:center;pointer-events:none;transition:opacity .4s ease,transform .45s ease}
.center.dim{opacity:.1;transform:translate(-50%,-51%) scale(.985)}
.kicker{display:inline-flex;align-items:center;gap:7px;color:rgba(255,255,255,.45);font-size:9px;letter-spacing:.04em;margin-bottom:17px;text-shadow:0 3px 16px rgba(0,0,0,.42)}
.kicker i{width:5px;height:5px;border-radius:50%;background:#75ff98;box-shadow:0 0 10px rgba(117,255,152,.7)}
.center h1{margin:0;font-size:clamp(38px,5.2vw,70px);line-height:.91;letter-spacing:-.075em;font-weight:700;text-shadow:0 5px 28px rgba(0,0,0,.38)}
.center p{width:290px;margin:17px auto 0;color:rgba(255,255,255,.39);font-size:11px;line-height:1.55;letter-spacing:-.01em}
.hero-row{display:flex;justify-content:center;gap:7px;margin-top:21px;pointer-events:auto}
.hero-btn{height:38px;padding:0 15px;border-radius:999px;font-size:11px;font-weight:500}
.hero-btn.primary{background:#fff;color:#000;border-color:#fff}
.hero-btn:hover{transform:translateY(-2px)}
.bottom{position:fixed;left:24px;right:24px;bottom:19px;z-index:100;display:flex;align-items:center;justify-content:space-between;pointer-events:none}
.hint{color:rgba(255,255,255,.27);font-size:10px}
.status{display:flex;align-items:center;gap:8px;padding:8px 11px;border:1px solid rgba(255,255,255,.08);background:rgba(5,5,5,.68);border-radius:999px;color:rgba(255,255,255,.42);font-size:9px}
.status-dot{width:5px;height:5px;border-radius:50%;background:#75ff98}
.credit{position:fixed;left:24px;bottom:56px;z-index:100;color:rgba(255,255,255,.2);font-size:8px;pointer-events:none}
.credit a{color:inherit}
.gradual-blur{position:absolute;left:0;right:0;bottom:0;width:100%;height:7rem;z-index:20;pointer-events:none;isolation:isolate;overflow:hidden}
.gradual-blur-inner{position:relative;width:100%;height:100%;pointer-events:none}
.gradual-blur-inner>div{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;-webkit-backdrop-filter:inherit;backdrop-filter:inherit}
.gradual-blur::after{content:"";position:absolute;inset:0;z-index:20;pointer-events:none;background:linear-gradient(to bottom,rgba(0,0,0,0) 0%,rgba(0,0,0,0) 44%,rgba(0,0,0,.035) 66%,rgba(0,0,0,.11) 84%,rgba(0,0,0,.24) 100%)}
@supports not (backdrop-filter:blur(1px)){.gradual-blur-inner>div{background:rgba(0,0,0,.25);opacity:.5}}
.search-panel{position:fixed;left:50%;top:18px;width:min(650px,calc(100vw - 28px));z-index:300;transform:translate(-50%,-150%);transition:transform .45s cubic-bezier(.22,1,.36,1)}
.search-panel.open{transform:translate(-50%,0)}
.search-box{height:54px;display:flex;align-items:center;padding:0 8px 0 16px;border:1px solid rgba(255,255,255,.14);border-radius:999px;background:rgba(8,8,8,.95);box-shadow:0 28px 90px rgba(0,0,0,.55),inset 0 1px 0 rgba(255,255,255,.025);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)}
.search-icon{margin-right:10px;color:rgba(255,255,255,.35);font-size:18px;flex:0 0 auto}
.search-box input{flex:1;min-width:0;border:0;outline:0;background:transparent;color:#fff;font-size:12px;font-weight:500;letter-spacing:-.01em}
.search-box input::placeholder{color:rgba(255,255,255,.27)}
.search-close{height:32px;padding:0 11px;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.035);color:rgba(255,255,255,.42);cursor:pointer;font-size:9px;transition:background .18s ease,border-color .18s ease,color .18s ease}
.search-close:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.7)}
.search-tags{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:8px 4px 0;opacity:0;transform:translateY(-5px);pointer-events:none;transition:opacity .2s ease,transform .2s ease}
.search-panel.open .search-tags{opacity:1;transform:none;pointer-events:auto}
.search-tag{height:29px;padding:0 10px;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(8,8,8,.76);color:rgba(255,255,255,.46);font-size:9px;font-weight:500;cursor:pointer;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease}
.search-tag:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.16);color:rgba(255,255,255,.8);transform:translateY(-1px)}
.search-tag.active{background:rgba(255,255,255,.11);border-color:rgba(255,255,255,.2);color:#fff}
.details-overlay{position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.68);opacity:0;pointer-events:none;transition:opacity .28s ease}
.details-overlay.open{opacity:1;pointer-events:auto}
.details-panel{position:absolute;right:18px;top:18px;bottom:18px;width:min(590px,calc(100vw - 36px));overflow:hidden;border:1px solid rgba(255,255,255,.13);border-radius:24px;background:#090909;box-shadow:0 40px 130px rgba(0,0,0,.75);transform:translateX(28px) scale(.985);transition:transform .42s cubic-bezier(.22,1,.36,1)}
.details-overlay.open .details-panel{transform:none}
.details-backdrop{position:absolute;inset:0;height:50%;overflow:hidden;pointer-events:none}
.details-backdrop img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:saturate(.82) brightness(.58);transform:scale(1.035)}
.details-backdrop::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(9,9,9,.05),#090909 96%),linear-gradient(90deg,rgba(9,9,9,.22),transparent 48%,rgba(9,9,9,.18))}
.details-scroll{position:relative;height:100%;overflow:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.14) transparent}
.details-top{position:relative;height:245px;padding:16px;display:flex;justify-content:flex-end}
.modal-close{width:36px;height:36px;border-radius:50%;font-size:17px;color:#fff;background:rgba(0,0,0,.4)}
.details-main{position:relative;padding:0 30px 30px}
.details-head{display:grid;grid-template-columns:132px 1fr;gap:21px;align-items:end}
.details-poster{width:132px;height:198px;border-radius:12px;overflow:hidden;background:#111;border:1px solid rgba(255,255,255,.11);box-shadow:0 18px 40px rgba(0,0,0,.5)}
.details-poster img{width:100%;height:100%;object-fit:cover;display:block}
.details-title h2{margin:0;font-size:clamp(28px,5vw,48px);line-height:.93;letter-spacing:-.065em}
.details-original{margin-top:7px;color:rgba(255,255,255,.33);font-size:10px}
.details-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:13px;color:rgba(255,255,255,.48);font-size:10px}
.score{display:inline-flex;align-items:center;gap:6px;margin-top:15px;padding:7px 10px;border:1px solid rgba(255,255,255,.09);border-radius:999px;background:rgba(255,255,255,.045);font-size:10px;color:rgba(255,255,255,.72)}
.score b{font-size:11px;color:#fff}
.details-copy{margin-top:27px}
.details-copy h3{margin:0 0 9px;font-size:9px;color:rgba(255,255,255,.31);font-weight:500;letter-spacing:.02em}
.details-copy p{margin:0;color:rgba(255,255,255,.61);font-size:12px;line-height:1.72}
.cast{display:flex;gap:8px;overflow:auto;margin-top:13px;padding-bottom:2px;scrollbar-width:none}
.cast::-webkit-scrollbar{display:none}
.cast-item{flex:0 0 58px}
.cast-photo{width:58px;height:72px;border-radius:8px;overflow:hidden;background:#151515;border:1px solid rgba(255,255,255,.08)}
.cast-photo img{width:100%;height:100%;object-fit:cover}
.cast-name{margin-top:6px;color:rgba(255,255,255,.55);font-size:8px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tv-selector{display:none;align-items:center;width:max-content;max-width:100%;margin-top:22px;border:1px solid rgba(255,255,255,.12);border-radius:999px;background:rgba(7,7,7,.72);box-shadow:inset 0 1px 0 rgba(255,255,255,.025);overflow:hidden;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.tv-selector.show{display:flex}
.tv-selector-group{position:relative;display:flex;align-items:center}
.tv-selector-group+.tv-selector-group{border-left:1px solid rgba(255,255,255,.1)}
.tv-selector select{height:36px;min-width:92px;padding:0 29px 0 12px;border:0;outline:0;appearance:none;-webkit-appearance:none;background:transparent;color:rgba(255,255,255,.78);font-size:9px;font-weight:500;color-scheme:dark;cursor:pointer;transition:color .18s ease,background .18s ease}
.tv-selector-group:first-child select{min-width:88px}
.tv-selector select:hover{background:rgba(255,255,255,.045);color:#fff}
.tv-selector select:focus{background:rgba(255,255,255,.06);color:#fff}
.tv-selector select option{background:#0b0b0b;color:#ececea}
.tv-selector-arrow{position:absolute;right:11px;top:50%;width:6px;height:6px;border-right:1px solid rgba(255,255,255,.42);border-bottom:1px solid rgba(255,255,255,.42);transform:translateY(-66%) rotate(45deg);pointer-events:none}
.tv-selector:focus-within{border-color:rgba(255,255,255,.2);background:rgba(9,9,9,.84)}
.details-actions{display:flex;gap:8px;margin-top:27px}
.details-action{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border-radius:999px;text-decoration:none;font-size:10px}
.details-action.primary{background:#fff;color:#000;border-color:#fff}
.loading{position:fixed;left:50%;top:50%;z-index:500;transform:translate(-50%,-50%);opacity:0;pointer-events:none;transition:opacity .18s}
.loading.show{opacity:1}
.spinner{width:24px;height:24px;border:1px solid rgba(255,255,255,.18);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.toast{position:fixed;left:50%;bottom:56px;z-index:450;transform:translate(-50%,14px);padding:9px 12px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(8,8,8,.9);color:rgba(255,255,255,.65);font-size:9px;opacity:0;pointer-events:none;transition:.28s cubic-bezier(.22,1,.36,1)}
.toast.show{opacity:1;transform:translate(-50%,0)}
/* surprise me */
.surprise-overlay{position:fixed;inset:0;z-index:950;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(0,0,0,.42);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease,visibility .24s ease}
.surprise-overlay.open{opacity:1;visibility:visible;pointer-events:auto}
.surprise-card{position:relative;width:min(320px,calc(100vw - 40px));overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:22px;background:#050505;box-shadow:0 36px 100px rgba(0,0,0,.72),inset 0 1px 0 rgba(255,255,255,.025);transform:translateY(12px) scale(.96) rotateX(3deg);opacity:0;transition:transform .5s cubic-bezier(.22,1,.36,1),opacity .34s ease;will-change:transform,opacity}
.surprise-overlay.open .surprise-card{transform:translateY(0) scale(1) rotateX(0deg);opacity:1}
.surprise-card-media{position:relative;aspect-ratio:2/3;overflow:hidden;background:#0a0a0a}
.surprise-card-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(15px) brightness(.46) saturate(.72);transform:scale(1.05);opacity:.18;transition:filter .75s cubic-bezier(.22,1,.36,1),opacity .75s ease,transform 1s cubic-bezier(.22,1,.36,1)}
.surprise-card.reveal .surprise-card-media img{filter:blur(0) brightness(.78) saturate(.82);opacity:1;transform:scale(1.01)}
.surprise-card-media::after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(180deg,rgba(0,0,0,.06) 20%,rgba(0,0,0,.08) 46%,rgba(0,0,0,.84) 100%)}
.surprise-card-scan{position:absolute;left:0;right:0;top:-12%;height:18%;background:linear-gradient(180deg,transparent,rgba(255,255,255,.09),transparent);opacity:0;transform:translateY(0);pointer-events:none}
.surprise-card.reveal .surprise-card-scan{animation:surprise-scan .9s cubic-bezier(.22,1,.36,1) forwards}
@keyframes surprise-scan{0%{opacity:0;transform:translateY(-40%)}18%{opacity:.65}100%{opacity:0;transform:translateY(620%)}}
.surprise-card-content{position:absolute;left:0;right:0;bottom:0;z-index:2;padding:20px 20px 19px;background:linear-gradient(180deg,transparent,rgba(0,0,0,.42) 10%,rgba(0,0,0,.9) 100%);transform:translateY(7px);opacity:.7;transition:transform .55s cubic-bezier(.22,1,.36,1) .1s,opacity .45s ease .1s}
.surprise-card.reveal .surprise-card-content{transform:none;opacity:1}
.surprise-kicker{display:flex;align-items:center;gap:7px;margin-bottom:8px;font-size:8px;color:rgba(255,255,255,.42);letter-spacing:.04em}
.surprise-kicker i{width:5px;height:5px;border-radius:50%;background:#75ff98;box-shadow:0 0 10px rgba(117,255,152,.62)}
.surprise-title{margin:0;font-size:clamp(24px,6vw,32px);line-height:.98;letter-spacing:-.055em;font-weight:700;color:#fff;text-wrap:balance}
.surprise-meta{display:flex;align-items:center;gap:7px;margin-top:9px;font-size:9px;color:rgba(255,255,255,.5)}
.surprise-overlay.loading .surprise-card-content{opacity:.35}
@media(max-width:620px){.surprise-overlay{padding:16px}.surprise-card{width:min(286px,calc(100vw - 32px));border-radius:19px}.surprise-card-content{padding:17px 17px 16px}}
.player-overlay{position:fixed;inset:0;z-index:1000;background:#000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;visibility:hidden;transition:opacity .24s ease,visibility .24s ease}
.player-overlay.open{opacity:1;pointer-events:auto;visibility:visible}
.player-frame{position:relative;width:100%;height:100%;background:#000;overflow:hidden}
.player-frame iframe{position:absolute;inset:0;width:100%;height:100%;border:0;background:#000}
.player-chrome{position:absolute;left:0;right:0;top:0;z-index:4;display:flex;align-items:center;justify-content:space-between;padding:16px 18px;pointer-events:none;background:linear-gradient(180deg,rgba(0,0,0,.72),rgba(0,0,0,0))}
.player-chrome>*{pointer-events:auto}
.player-movie-title{max-width:min(60vw,700px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;font-weight:600;color:rgba(255,255,255,.84);text-shadow:0 2px 14px rgba(0,0,0,.65)}
.player-controls{display:flex;gap:7px;align-items:center}
.player-control{height:36px;padding:0 12px;border:1px solid rgba(255,255,255,.16);border-radius:999px;background:rgba(12,12,12,.62);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);color:rgba(255,255,255,.84);font-size:10px;cursor:pointer;transition:.2s ease}
.player-control:hover{background:rgba(255,255,255,.11);border-color:rgba(255,255,255,.3);color:#fff}
.player-control.close{width:36px;padding:0;font-size:18px;line-height:1}
.player-loading{position:absolute;left:50%;top:50%;z-index:3;transform:translate(-50%,-50%);width:28px;height:28px;border:1px solid rgba(255,255,255,.18);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;pointer-events:none;opacity:.8}
@media(max-width:900px){.details-panel{right:12px;top:12px;bottom:12px;width:min(560px,calc(100vw - 24px))}.details-main{padding-inline:24px}}
@media(max-width:620px){.nav{padding:16px}.nav-btn{height:34px;padding:0 11px}.nav-btn.icon-button{width:34px;padding:0}.center h1{font-size:42px}.center p{width:260px}.hero-row{gap:5px}.hero-btn{padding-inline:12px}.hint,.credit{display:none}.bottom{left:14px;right:14px;bottom:13px}.filters-panel{right:-2px;width:min(350px,calc(100vw - 28px))}.settings-panel{right:-2px;width:min(350px,calc(100vw - 28px))}.filter-item{min-height:41px;padding-left:10px}.filter-control{width:132px;flex-basis:132px}.filter-field-label{font-size:8px}.filter-select{height:30px}.filter-control{height:30px}.filter-actions{margin-top:0}.filters-subtitle{font-size:7px}.search-panel{width:calc(100vw - 28px)}.search-box{height:52px}.search-tags{gap:5px;margin-top:7px}.search-tag{height:28px;padding-inline:9px}.tv-selector{margin-top:18px;max-width:100%}.tv-selector select{height:34px;font-size:8px;min-width:88px}.tv-selector-group:first-child select{min-width:82px}.gradual-blur{height:5.5rem}.player-chrome{padding:13px 12px}.player-movie-title{font-size:10px;max-width:48vw}.player-control{height:34px;padding:0 10px}.player-control.close{width:34px}.details-panel{left:0;right:0;top:auto;bottom:0;width:100%;height:min(86vh,760px);border-radius:22px 22px 0 0;transform:translateY(28px) scale(.99)}.details-backdrop{height:280px}.details-top{height:226px}.details-main{padding:0 18px 24px}.details-head{grid-template-columns:98px 1fr;gap:15px}.details-poster{width:98px;height:147px}.details-title h2{font-size:31px}.details-meta{font-size:9px}.details-copy{margin-top:22px}.details-actions{margin-top:22px}}
@media(max-width:390px){.center h1{font-size:37px}.hero-btn{font-size:10px;padding-inline:10px}.details-head{grid-template-columns:88px 1fr}.details-poster{width:88px;height:132px}.details-title h2{font-size:27px}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition-duration:0s!important;animation-duration:0s!important}}
</style>
</head>
<body>
<div id="app">
<canvas id="movieCanvas" aria-label="Endless movie browser"></canvas>
<div class="vignette"></div>
<div class="gradual-blur" id="gradualBlur" aria-hidden="true"><div class="gradual-blur-inner" id="gradualBlurInner"></div></div>
<nav class="nav">
<a class="logo" href="#"><span class="logo-mark"></span><span class="logo-text">reel</span></a>
<div class="nav-right">
<button class="nav-btn icon-button" id="searchButton" title="Search" aria-label="Search"><i data-lucide="search"></i></button>

<div class="filters-wrap">
<button class="nav-btn icon-button" id="filterButton" aria-expanded="false" title="Filters" aria-label="Filters"><i data-lucide="list-filter"></i></button>
<div class="filters-panel" id="filtersPanel" role="menu" aria-label="Reel filters">
<div class="filters-head">
<div class="filters-head-copy">
<div class="filters-title">filters</div>
<div class="filters-subtitle">Refine what appears on the canvas.</div>
</div>
<div class="filters-icon"><i data-lucide="list-filter"></i></div>
</div>

<div class="filter-list">
<div class="filter-item">
<div class="filter-label-wrap"><div class="filter-label-icon"><i data-lucide="clapperboard"></i></div><label class="filter-field-label" for="mediaType">type</label></div>
<div class="filter-control"><select class="filter-select" id="mediaType"><option value="movie">movies</option><option value="tv">tv shows</option></select><span class="filter-arrow"></span></div>
</div>

<div class="filter-item">
<div class="filter-label-wrap"><div class="filter-label-icon"><i data-lucide="arrow-down-up"></i></div><label class="filter-field-label" for="sortType">sort</label></div>
<div class="filter-control"><select class="filter-select" id="sortType"><option value="popular">popular</option><option value="top">top rated</option><option value="trending">trending</option><option value="newest">newest</option></select><span class="filter-arrow"></span></div>
</div>

<div class="filter-item">
<div class="filter-label-wrap"><div class="filter-label-icon"><i data-lucide="tags"></i></div><label class="filter-field-label" for="genreSelect">genre</label></div>
<div class="filter-control"><select class="filter-select" id="genreSelect"><option value="">all genres</option></select><span class="filter-arrow"></span></div>
</div>

<div class="filter-item">
<div class="filter-label-wrap"><div class="filter-label-icon"><i data-lucide="star"></i></div><label class="filter-field-label" for="minRating">rating</label></div>
<div class="filter-control"><select class="filter-select" id="minRating"><option value="0">any</option><option value="5">5+</option><option value="6">6+</option><option value="7">7+</option><option value="8">8+</option></select><span class="filter-arrow"></span></div>
</div>
</div>

<div class="filters-divider"></div>
<div class="filter-actions"><button class="filter-reset" id="filterReset" type="button">reset</button><button class="filter-apply" id="filterApply" type="button">apply filters</button></div>
</div>
</div>

<button class="nav-btn icon-button" id="randomButton" title="Surprise me" aria-label="Surprise me"><i data-lucide="dices"></i></button>

<div class="settings-wrap">
<button class="nav-btn icon-button" id="settingsButton" title="Settings" aria-label="Settings" aria-expanded="false"><i data-lucide="settings"></i></button>
<div class="settings-panel" id="settingsPanel" role="menu" aria-label="Reel settings">
<div class="settings-head">
<div class="settings-head-copy">
<div class="settings-title">settings</div>
<div class="settings-subtitle">Control visual effects and rendering quality.</div>
</div>
<div class="settings-icon"><i data-lucide="sliders-horizontal"></i></div>
</div>

<div class="settings-group">
<div class="settings-section-label">effects</div>
<div class="settings-list">
<div class="settings-item">
<div class="settings-label-wrap"><div class="settings-label-icon"><i data-lucide="waves"></i></div><div class="settings-label-copy"><div class="settings-label">gradual blur</div><div class="settings-description">softens the lower edge</div></div></div>
<button class="settings-toggle" id="blurToggle" type="button" role="switch" aria-checked="true" aria-label="Toggle gradual blur"><span></span></button>
</div>
<div class="settings-item">
<div class="settings-label-wrap"><div class="settings-label-icon"><i data-lucide="circle-dot"></i></div><div class="settings-label-copy"><div class="settings-label">vignette</div><div class="settings-description">darkens the outer frame</div></div></div>
<button class="settings-toggle" id="vignetteToggle" type="button" role="switch" aria-checked="true" aria-label="Toggle vignette"><span></span></button>
</div>
<div class="settings-item">
<div class="settings-label-wrap"><div class="settings-label-icon"><i data-lucide="layers-3"></i></div><div class="settings-label-copy"><div class="settings-label">depth effect</div><div class="settings-description">adds canvas perspective</div></div></div>
<button class="settings-toggle" id="depthToggle" type="button" role="switch" aria-checked="true" aria-label="Toggle depth effect"><span></span></button>
</div>
<div class="settings-item">
<div class="settings-label-wrap"><div class="settings-label-icon"><i data-lucide="blur"></i></div><div class="settings-label-copy"><div class="settings-label">interface blur</div><div class="settings-description">blurs floating controls</div></div></div>
<button class="settings-toggle" id="uiBlurToggle" type="button" role="switch" aria-checked="true" aria-label="Toggle interface blur"><span></span></button>
</div>
</div>
</div>

<div class="settings-divider"></div>

<div class="settings-group">
<div class="settings-section-label">performance</div>
<div class="settings-list">
<div class="settings-item settings-select-item">
<div class="settings-label-wrap"><div class="settings-label-icon"><i data-lucide="gauge"></i></div><div class="settings-label-copy"><div class="settings-label">rendering mode</div><div class="settings-description">prioritize frame rate</div></div></div>
<div class="settings-select-wrap"><select id="qualitySelect" class="settings-select" aria-label="Performance mode"><option value="auto">auto</option><option value="performance">performance</option><option value="balanced">balanced</option><option value="high">high</option></select><span class="settings-arrow"></span></div>
</div>
</div>
</div>

<div class="settings-divider"></div>

<div class="settings-footer">
<div class="settings-footer-note">settings are saved automatically</div>
<button class="settings-footer-button" id="settingsDefaults" type="button">restore defaults</button>
</div>
</div>
</div>
</div>
</nav>
<section class="center" id="center">
<div class="kicker"><i></i><span id="modeLabel">infinite cinema</span></div>
<h1>find something<br>worth watching.</h1>
<p>Drag through an endless collection of movies. Click a poster for details.</p>
<div class="hero-row">
<button class="hero-btn primary icon-button" id="exploreButton" title="Explore" aria-label="Explore"><i data-lucide="compass"></i></button>
<button class="hero-btn icon-button" id="popularButton" title="Popular" aria-label="Popular"><i data-lucide="flame"></i></button>
<button class="hero-btn icon-button" id="trendingButton" title="Trending" aria-label="Trending"><i data-lucide="trending-up"></i></button>
</div>
</section>
<div class="search-panel" id="searchPanel"><div class="search-box"><span class="search-icon">⌕</span><input id="searchInput" type="text" autocomplete="off" spellcheck="false" placeholder="search titles or use /tags..."><button class="search-close icon-button" id="searchClose" title="Close search" aria-label="Close search"><i data-lucide="x"></i></button></div><div class="search-tags" id="searchTags"><button class="search-tag" type="button" data-tag="/tv">/tv</button><button class="search-tag" type="button" data-tag="/movie">/movie</button><button class="search-tag" type="button" data-tag="/horror">/horror</button><button class="search-tag" type="button" data-tag="/action">/action</button><button class="search-tag" type="button" data-tag="/trending">/trending</button><button class="search-tag" type="button" data-tag="/top">/top</button><button class="search-tag" type="button" data-tag="/2020s">/2020s</button></div></div>
<div class="surprise-overlay" id="surpriseOverlay" aria-hidden="true"><div class="surprise-card" id="surpriseCard" role="dialog" aria-modal="true" aria-label="Surprise title"><div class="surprise-card-media"><img id="surprisePoster" src="" alt=""><div class="surprise-card-scan"></div></div><div class="surprise-card-content"><div class="surprise-kicker"><i></i><span id="surpriseKicker">finding something...</span></div><h2 class="surprise-title" id="surpriseTitle">surprise me</h2><div class="surprise-meta" id="surpriseMeta"></div></div></div></div>
<div class="loading" id="loading"><div class="spinner"></div></div>
<div class="toast" id="toast"></div>
<div class="bottom"><div class="hint">drag to explore · scroll to move · / to search</div><div class="status"><span class="status-dot"></span><span id="statusText">loading cinema</span></div></div>
<div class="credit">Movie data and images from <a href="https://www.themoviedb.org" target="_blank" rel="noopener">TMDB</a>.</div>
<div class="player-overlay" id="playerOverlay" aria-hidden="true"><div class="player-frame" id="playerFrame"><div class="player-loading" id="playerLoading"></div><iframe id="playerIframe" title="Movie player" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen referrerpolicy="origin-when-cross-origin"></iframe><div class="player-chrome"><div class="player-movie-title" id="playerMovieTitle">watching</div><div class="player-controls"><button class="player-control icon-button" id="playerFullscreen" title="Fullscreen" aria-label="Fullscreen"><i data-lucide="maximize"></i></button><button class="player-control close icon-button" id="playerClose" aria-label="Exit player" title="Close"><i data-lucide="x"></i></button></div></div></div></div>
<div class="details-overlay" id="detailsOverlay"><aside class="details-panel" role="dialog" aria-modal="true" aria-label="Title details"><div class="details-backdrop"><img id="detailsBackdrop" src="" alt=""></div><div class="details-scroll" id="detailsScroll"><div class="details-top"><button class="modal-close icon-button" id="modalClose" aria-label="Close" title="Close"><i data-lucide="x"></i></button></div><div class="details-main"><div class="details-head"><div class="details-poster"><img id="modalPoster" src="" alt=""></div><div class="details-title"><h2 id="modalTitle"></h2><div class="details-original" id="modalOriginal"></div><div class="details-meta"><span id="modalYear"></span><span>·</span><span id="modalRuntime"></span><span>·</span><span id="modalGenres"></span></div><div class="score">★ <b id="modalRating">—</b><span id="modalVotes"></span></div></div></div><div class="details-copy"><h3>overview</h3><p id="modalOverview">No overview available.</p></div><div class="details-copy" id="castSection" hidden><h3>cast</h3><div class="cast" id="castList"></div></div><div class="tv-selector" id="tvSelector" aria-label="Choose season and episode"><div class="tv-selector-group"><select id="seasonSelect" aria-label="Season"></select><span class="tv-selector-arrow"></span></div><div class="tv-selector-group"><select id="episodeSelect" aria-label="Episode"></select><span class="tv-selector-arrow"></span></div></div><div class="details-actions"><button class="details-action primary icon-button" id="watchButton" type="button" title="Watch" aria-label="Watch"><i data-lucide="play"></i></button><button class="details-action icon-button" id="shareButton" type="button" title="Copy share link" aria-label="Copy share link"><i data-lucide="share-2"></i></button><a class="details-action icon-button" id="tmdbLink" href="#" target="_blank" rel="noopener" title="View on TMDB" aria-label="View on TMDB"><i data-lucide="external-link"></i></a><a class="details-action icon-button" id="trailerLink" href="#" target="_blank" rel="noopener" hidden title="Watch trailer" aria-label="Watch trailer"><i data-lucide="youtube"></i></a></div></div></div></aside></div>
</div>
<script>
const TMDB_API_KEY="4e44d9029b1270a757cddc766a1bcb63";
const TMDB_API="https://api.themoviedb.org/3";
const TMDB_IMAGE="https://image.tmdb.org/t/p/";
const CACHE_KEY="reel_media_canvas_v2";
const MAX_CACHE=800;
let DPR_CAP=1.5;
let TILE_COLS=11;
let TILE_ROWS=9;
let BLUR_LAYERS=window.innerWidth<=620?5:6;
let QUALITY_MODE="auto";
let adaptivePerformanceLevel=0;
let lowPowerDevice=false;
const canvas=document.getElementById("movieCanvas");
const ctx=canvas.getContext("2d",{alpha:false,desynchronized:true});
ctx.imageSmoothingEnabled=true;
const center=document.getElementById("center");
const modeLabel=document.getElementById("modeLabel");
const loading=document.getElementById("loading");
const statusText=document.getElementById("statusText");
const toast=document.getElementById("toast");const surpriseOverlay=document.getElementById("surpriseOverlay");const surpriseCard=document.getElementById("surpriseCard");const surprisePoster=document.getElementById("surprisePoster");const surpriseTitle=document.getElementById("surpriseTitle");const surpriseMeta=document.getElementById("surpriseMeta");const surpriseKicker=document.getElementById("surpriseKicker");
const searchPanel=document.getElementById("searchPanel");
const searchInput=document.getElementById("searchInput");
const searchTags=document.getElementById("searchTags");
const detailsOverlay=document.getElementById("detailsOverlay");
const detailsScroll=document.getElementById("detailsScroll");
const detailsBackdrop=document.getElementById("detailsBackdrop");
const modalPoster=document.getElementById("modalPoster");
const modalTitle=document.getElementById("modalTitle");
const modalOriginal=document.getElementById("modalOriginal");
const modalYear=document.getElementById("modalYear");
const modalRuntime=document.getElementById("modalRuntime");
const modalGenres=document.getElementById("modalGenres");
const modalRating=document.getElementById("modalRating");
const modalVotes=document.getElementById("modalVotes");
const modalOverview=document.getElementById("modalOverview");
const castSection=document.getElementById("castSection");
const castList=document.getElementById("castList");
const tvSelector=document.getElementById("tvSelector");
const seasonSelect=document.getElementById("seasonSelect");
const episodeSelect=document.getElementById("episodeSelect");
const tmdbLink=document.getElementById("tmdbLink");
const trailerLink=document.getElementById("trailerLink");
const watchButton=document.getElementById("watchButton");
const shareButton=document.getElementById("shareButton");
const playerOverlay=document.getElementById("playerOverlay");
const playerFrame=document.getElementById("playerFrame");
const playerIframe=document.getElementById("playerIframe");
const playerLoading=document.getElementById("playerLoading");
const playerMovieTitle=document.getElementById("playerMovieTitle");
const playerClose=document.getElementById("playerClose");
const playerFullscreen=document.getElementById("playerFullscreen");
const filterButton=document.getElementById("filterButton");
const filtersPanel=document.getElementById("filtersPanel");
const mediaType=document.getElementById("mediaType");
const sortType=document.getElementById("sortType");
const genreSelect=document.getElementById("genreSelect");
const minRating=document.getElementById("minRating");
const filterApply=document.getElementById("filterApply");
const filterReset=document.getElementById("filterReset");
const settingsButton=document.getElementById("settingsButton");
const settingsPanel=document.getElementById("settingsPanel");
const blurToggle=document.getElementById("blurToggle");
const vignetteToggle=document.getElementById("vignetteToggle");
const depthToggle=document.getElementById("depthToggle");
const uiBlurToggle=document.getElementById("uiBlurToggle");
const qualitySelect=document.getElementById("qualitySelect");
const settingsDefaults=document.getElementById("settingsDefaults");
const tiles=[];
const imageCache=new Map();
const pendingImages=new Set();
const genreCache=new Map();
const metrics={w:0,h:0,dpr:1,tileW:0,tileH:0,gap:0,stepX:0,stepY:0,totalW:0,totalH:0};
const state={movies:[],movieIds:new Set(),pagesLoaded:new Set(),nextPage:1,totalPages:1,query:"",searchRaw:"",searchFilters:{mediaType:"movie",sort:"popular",genre:"",minRating:0,year:null,decade:null},mode:"popular",mediaType:"movie",sort:"popular",genre:"",minRating:0,x:0,y:0,vx:0,vy:0,dragging:false,moved:false,px:0,py:0,downX:0,downY:0,travel:0,fetching:new Set(),raf:0,rendering:false,lastT:0,dirty:true,lastHit:[],currentMovie:null,lastSelectedId:null,worldAssignments:new Map(),searchTimer:null,requestId:0,effects:{blur:true,vignette:true,depth:true,uiBlur:true},adaptiveQuality:false,fpsFrames:0,fpsLast:0,fpsWindowStart:0,fpsSlowWindows:0};
const blurCurves={linear:p=>p,bezier:p=>p*p*(3-2*p),"ease-in":p=>p*p,"ease-out":p=>1-Math.pow(1-p,2),"ease-in-out":p=>p<.5?2*p*p:1-Math.pow(-2*p+2,2)/2};
function buildGradualBlur(){const container=document.getElementById("gradualBlur"),inner=document.getElementById("gradualBlurInner");if(!container||!inner)return;inner.textContent="";if(!state.effects.blur||BLUR_LAYERS<1){container.style.display="none";return}container.style.display="block";const increment=100/BLUR_LAYERS,curve=blurCurves.bezier;for(let i=1;i<=BLUR_LAYERS;i++){let progress=curve(i/BLUR_LAYERS);const blurValue=Math.pow(2,progress*4)*.0625*2;const p1=Math.round((increment*i-increment)*10)/10,p2=Math.round(increment*i*10)/10,p3=Math.round((increment*i+increment)*10)/10,p4=Math.round((increment*i+increment*2)*10)/10;let gradient=`transparent ${p1}%,black ${p2}%`;if(p3<=100)gradient+=`,black ${p3}%`;if(p4<=100)gradient+=`,transparent ${p4}%`;const layer=document.createElement("div");layer.style.maskImage=`linear-gradient(to bottom,${gradient})`;layer.style.webkitMaskImage=`linear-gradient(to bottom,${gradient})`;layer.style.backdropFilter=`blur(${blurValue.toFixed(3)}rem)`;layer.style.webkitBackdropFilter=`blur(${blurValue.toFixed(3)}rem)`;layer.style.opacity=".9";inner.appendChild(layer)}}
function showToast(message){toast.textContent=message;toast.classList.add("show");clearTimeout(showToast.timer);showToast.timer=setTimeout(()=>toast.classList.remove("show"),2400)}
function setLoading(value){loading.classList.toggle("show",value)}
function imageUrl(path,size="w342"){return path?TMDB_IMAGE+size+path:""}
function backdropUrl(path){return path?TMDB_IMAGE+"w1280"+path:""}
function formatVotes(value){if(!value)return "";if(value>=1e6)return `${(value/1e6).toFixed(1)}m votes`;if(value>=1e3)return `${(value/1e3).toFixed(1)}k votes`;return `${value} votes`}
function clamp(value,min,max){return value<min?min:value>max?max:value}
function wrap(value,range){return (((value+range/2)%range+range)%range)-range/2}
function detectLowPowerDevice(){const cores=navigator.hardwareConcurrency||8;const memory=navigator.deviceMemory||8;const saveData=!!navigator.connection?.saveData;return saveData||cores<=4||memory<=4}

function applyQualityMode(mode,adaptive=false){QUALITY_MODE=mode;state.adaptiveQuality=adaptive;/* Keep the world geometry fixed. Quality changes must never alter the tile count because the wrap dimensions define the infinite surface. */TILE_COLS=11;TILE_ROWS=9;if(mode==="performance"){DPR_CAP=1;BLUR_LAYERS=2}else if(mode==="balanced"){DPR_CAP=1.25;BLUR_LAYERS=4}else if(mode==="high"){DPR_CAP=1.5;BLUR_LAYERS=6}else{const low=lowPowerDevice||adaptivePerformanceLevel>0;DPR_CAP=low?1.1:1.5;BLUR_LAYERS=low?3:(window.innerWidth<=620?5:6)}createTiles();metrics.w=0;metrics.h=0;metrics.dpr=0;resizeCanvas();buildGradualBlur();state.dirty=true;startRenderer()}

function updateEffectUI(){document.body.classList.toggle("no-vignette",!state.effects.vignette);document.body.classList.toggle("no-ui-blur",!state.effects.uiBlur);document.body.classList.toggle("no-depth",!state.effects.depth);blurToggle.setAttribute("aria-checked",String(state.effects.blur));vignetteToggle.setAttribute("aria-checked",String(state.effects.vignette));depthToggle.setAttribute("aria-checked",String(state.effects.depth));uiBlurToggle.setAttribute("aria-checked",String(state.effects.uiBlur));qualitySelect.value=QUALITY_MODE}

function saveSettings(){try{localStorage.setItem("reel_settings_v3",JSON.stringify({effects:state.effects,quality:QUALITY_MODE}))}catch{}}

function loadSettings(){try{const raw=localStorage.getItem("reel_settings_v3");if(!raw)return;const data=JSON.parse(raw);if(data.effects)state.effects={...state.effects,...data.effects};if(["auto","performance","balanced","high"].includes(data.quality))QUALITY_MODE=data.quality}catch{}}

function setupPerformanceMonitor(){state.fpsFrames=0;state.fpsLast=performance.now();state.fpsWindowStart=state.fpsLast;function sample(now){state.fpsFrames++;if(now-state.fpsWindowStart>=1000){const fps=state.fpsFrames*1000/(now-state.fpsWindowStart);state.fpsFrames=0;state.fpsWindowStart=now;if(QUALITY_MODE==="auto"&&fps<50&&adaptivePerformanceLevel===0&&state.dragging===false){adaptivePerformanceLevel=1;applyQualityMode("auto",true)}else if(QUALITY_MODE==="auto"&&fps>=58&&adaptivePerformanceLevel>0&&state.fpsSlowWindows>2){adaptivePerformanceLevel=0;applyQualityMode("auto",false);state.fpsSlowWindows=0}if(QUALITY_MODE==="auto"&&adaptivePerformanceLevel>0){if(fps>=58)state.fpsSlowWindows++;else state.fpsSlowWindows=0}}requestAnimationFrame(sample)}requestAnimationFrame(sample)}

function resizeCanvas(){const width=window.innerWidth,height=window.innerHeight,dpr=Math.min(window.devicePixelRatio||1,DPR_CAP);if(metrics.w===width&&metrics.h===height&&metrics.dpr===dpr)return;metrics.w=width;metrics.h=height;metrics.dpr=dpr;canvas.width=Math.max(1,Math.round(width*dpr));canvas.height=Math.max(1,Math.round(height*dpr));canvas.style.width=width+"px";canvas.style.height=height+"px";ctx.setTransform(dpr,0,0,dpr,0,0);const minSide=Math.min(width,height),tileW=Math.round(clamp(minSide*.155,104,172)),tileH=Math.round(tileW*1.5),gap=Math.round(clamp(tileW*.08,9,15));metrics.tileW=tileW;metrics.tileH=tileH;metrics.gap=gap;metrics.stepX=tileW+gap;metrics.stepY=tileH+gap;metrics.totalW=TILE_COLS*metrics.stepX;metrics.totalH=TILE_ROWS*metrics.stepY;state.dirty=true}
function saveCache(){try{localStorage.setItem(CACHE_KEY,JSON.stringify({movies:state.movies.slice(-MAX_CACHE)}))}catch{}}
function loadCache(){try{const raw=localStorage.getItem(CACHE_KEY);if(!raw)return;const data=JSON.parse(raw);if(!Array.isArray(data.movies))return;state.movies=data.movies.filter(movie=>movie&&movie.id&&movie.poster_path);state.movieIds=new Set(state.movies.map(movie=>movie.id))}catch{}}
async function tmdb(path,params={}){const url=new URL(TMDB_API+path);url.searchParams.set("api_key",TMDB_API_KEY);url.searchParams.set("language","en-US");for(const [key,value] of Object.entries(params))url.searchParams.set(key,value);const response=await fetch(url.toString(),{headers:{accept:"application/json"}});let body={};try{body=await response.json()}catch{}if(!response.ok)throw new Error(body.status_message||`TMDB request failed (${response.status})`);return body}
function endpointFor(mode,mediaType){const prefix=mediaType==="tv"?"/tv":"/movie";if(mode==="trending")return `/trending/${mediaType}/week`;if(mode==="top")return `${prefix}/top_rated`;if(mode==="newest")return `/discover/${mediaType}`;return `${prefix}/popular`}
function discoverParams(page){const type=state.mediaType,params={page,include_adult:"false"};if(state.genre)params.with_genres=state.genre;if(Number(state.minRating)>0){params["vote_average.gte"]=state.minRating;params["vote_count.gte"]=50}const filters=state.searchFilters||{};const dateField=type==="tv"?"first_air_date":"primary_release_date";if(state.mode==="search"&&!state.query.trim()&&filters.year){params[dateField+".gte"]=`${filters.year}-01-01`;params[dateField+".lte"]=`${filters.year}-12-31`}else if(state.mode==="search"&&!state.query.trim()&&filters.decade){params[dateField+".gte"]=`${filters.decade}-01-01`;params[dateField+".lte"]=`${filters.decade+9}-12-31`}if(state.sort==="newest")params.sort_by=type==="tv"?"first_air_date.desc":"primary_release_date.desc";else if(state.sort==="top")params.sort_by="vote_average.desc";else params.sort_by="popularity.desc";return params}
async function loadGenres(type){if(genreCache.has(type)){const genres=genreCache.get(type);genreSelect.innerHTML=`<option value="">all genres</option>`+genres.map(genre=>`<option value="${genre.id}">${escapeHtml(genre.name)}</option>`).join("");genreSelect.value=state.genre;return}try{const data=await tmdb(`/genre/${type}/list`),genres=Array.isArray(data.genres)?data.genres:[];genreCache.set(type,genres);const current=state.genre;genreSelect.innerHTML=`<option value="">all genres</option>`+genres.map(genre=>`<option value="${genre.id}">${escapeHtml(genre.name)}</option>`).join("");genreSelect.value=current}catch(error){console.error(error)}}
function escapeHtml(value){return String(value??"").replace(/[&<>'"]/g,character=>({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;","\"":"&quot;"}[character]))}
const SEARCH_GENRE_ALIASES={action:28,adventure:12,animation:16,comedy:35,crime:80,documentary:99,drama:18,family:10751,fantasy:14,history:36,horror:27,music:10402,mystery:9648,romance:10749,scifi:878,"sci fi":878,"science fiction":878,thriller:53,war:10752,western:37,"tv movie":10770,kids:10762,news:10763,reality:10764,soap:10766,talk:10767};
function normalizeSearchTag(value){return String(value||"").toLowerCase().replace(/[._-]+/g," ").replace(/\s+/g," ").trim()}
function resolveGenreId(tag,type){const normalized=normalizeSearchTag(tag);if(SEARCH_GENRE_ALIASES[normalized])return String(SEARCH_GENRE_ALIASES[normalized]);const genres=genreCache.get(type)||[];const found=genres.find(genre=>normalizeSearchTag(genre.name)===normalized);return found?.id?String(found.id):null}
function parseAdvancedSearch(raw){const tokens=String(raw||"").trim().split(/\s+/).filter(Boolean);const parsed={mediaType:state.mediaType,sort:"popular",genre:"",minRating:0,year:null,decade:null};const text=[];for(const token of tokens){if(token[0]!=="/"||token.length<2){text.push(token);continue}const tag=token.slice(1).toLowerCase();if(["tv","show","shows","series"].includes(tag)){parsed.mediaType="tv";continue}if(["movie","movies","film","films"].includes(tag)){parsed.mediaType="movie";continue}if(["popular","pop"].includes(tag)){parsed.sort="popular";continue}if(["top","rated","toprated"].includes(tag)){parsed.sort="top";continue}if(["trending","trend"].includes(tag)){parsed.sort="trending";continue}if(["new","newest","latest"].includes(tag)){parsed.sort="newest";continue}const decadeMatch=tag.match(/^(19|20)\d0s$/);if(decadeMatch){parsed.decade=Number(tag.slice(0,-1));continue}const yearMatch=tag.match(/^(19|20)\d{2}$/);if(yearMatch){parsed.year=Number(tag);continue}const ratingMatch=tag.match(/^(?:>=?|min)?(\d(?:\.\d)?)\+?$/);if(ratingMatch){const rating=Number(ratingMatch[1]);if(rating>=0&&rating<=10){parsed.minRating=rating;continue}}const genreId=resolveGenreId(tag,parsed.mediaType);if(genreId){parsed.genre=genreId;continue}text.push(token)}return {...parsed,text:text.join(" ").trim()}}
function filterAdvancedResults(results){const filters=state.searchFilters||{};return results.filter(movie=>{if(filters.genre){const ids=Array.isArray(movie.genre_ids)?movie.genre_ids.map(String):[];if(!ids.includes(String(filters.genre)))return false}if(filters.minRating>0&&Number(movie.vote_average||0)<filters.minRating)return false;const date=movie.release_date||movie.first_air_date||"";if(filters.year&&date.slice(0,4)!==String(filters.year))return false;if(filters.decade){const year=Number(date.slice(0,4));if(!year||year<filters.decade||year>filters.decade+9)return false}return true})}
async function runAdvancedSearch(raw){const parsed=parseAdvancedSearch(raw);state.searchFilters=parsed;state.mediaType=parsed.mediaType;state.sort=parsed.sort;state.genre=parsed.genre;state.minRating=parsed.minRating;state.searchRaw=String(raw||"").trim();mediaType.value=state.mediaType;sortType.value=state.sort;minRating.value=String(state.minRating);await loadGenres(state.mediaType);genreSelect.value=parsed.genre||"";await resetAndLoad("search",parsed.text,parsed)}
function updateSearchTags(){if(!searchTags)return;const active=new Set(searchInput.value.trim().split(/\s+/).filter(Boolean));searchTags.querySelectorAll(".search-tag").forEach(tag=>tag.classList.toggle("active",active.has(tag.dataset.tag)))}
function appendSearchTag(tag){const parts=searchInput.value.trim().split(/\s+/).filter(Boolean);const cleaned=parts.filter(value=>!(["/tv","/movie"].includes(tag)&&["/tv","/movie"].includes(value)));if(!cleaned.includes(tag))cleaned.push(tag);searchInput.value=cleaned.join(" ");updateSearchTags();clearTimeout(state.searchTimer);state.searchTimer=setTimeout(()=>{const raw=searchInput.value.trim();raw?runAdvancedSearch(raw):resetAndLoad(state.sort,"")},120);searchInput.focus()}

function getImage(path){if(!path)return null;const source=imageUrl(path,"w342");let record=imageCache.get(source);if(record)return record;record={img:new Image(),ready:false,error:false};record.img.decoding="async";record.img.loading="eager";record.img.onload=()=>{record.ready=true;pendingImages.delete(source);state.dirty=true;startRenderer()};record.img.onerror=()=>{record.error=true;pendingImages.delete(source)};pendingImages.add(source);record.img.src=source;imageCache.set(source,record);return record}
function createTiles(){tiles.length=0;let index=0;for(let row=-Math.floor(TILE_ROWS/2);row<=Math.floor(TILE_ROWS/2);row++)for(let col=-Math.floor(TILE_COLS/2);col<=Math.floor(TILE_COLS/2);col++)tiles.push({row,col,index:index++,cx:0,cy:0,w:0,h:0,movie:null})}
function hash2D(x,y){let h=Math.imul(x|0,0x45d9f3b)+Math.imul(y|0,0x119de1f3);h^=h>>>16;h=Math.imul(h,0x45d9f3b);h^=h>>>16;return h>>>0}
function worldKey(col,row){return `${col},${row}`}
function movieAtWorld(col,row){const movieCount=state.movies.length;if(movieCount===0)return null;const key=worldKey(col,row),assigned=state.worldAssignments.get(key);if(assigned!==undefined)return state.movies[assigned]||null;const index=hash2D(col*73856093,row*19349663)%movieCount,movie=state.movies[index]||null;if(movie)state.worldAssignments.set(key,index);return movie}
function roundedPath(x,y,width,height,radius){const r=Math.min(radius,width*.5,height*.5);ctx.beginPath();ctx.moveTo(x+r,y);ctx.arcTo(x+width,y,x+width,y+height,r);ctx.arcTo(x+width,y+height,x,y+height,r);ctx.arcTo(x,y+height,x,y,r);ctx.arcTo(x,y,x+width,y,r);ctx.closePath()}
function drawPoster(image,x,y,width,height,rotate,alpha,scaleX,scaleY){ctx.save();ctx.translate(x,y);ctx.rotate(rotate);ctx.scale(scaleX,scaleY);ctx.globalAlpha=alpha;roundedPath(-width*.5,-height*.5,width,height,10);ctx.clip();ctx.drawImage(image,-width*.5,-height*.5,width,height);ctx.fillStyle="rgba(0,0,0,.055)";ctx.fillRect(-width*.5,-height*.5,width,height);ctx.restore()}
function drawPlaceholder(x,y,width,height,alpha,scaleX,scaleY){ctx.save();ctx.translate(x,y);ctx.scale(scaleX,scaleY);ctx.globalAlpha=alpha;roundedPath(-width*.5,-height*.5,width,height,10);ctx.fillStyle="#111";ctx.fill();ctx.restore()}
function draw(){if(!state.dirty)return;state.dirty=false;const {w,h,stepX,stepY,totalW,totalH,tileW,tileH}=metrics;ctx.clearRect(0,0,w,h);ctx.fillStyle="#050505";ctx.fillRect(0,0,w,h);const centerX=w*.5,centerY=h*.5,lensX=Math.max(w*.56,420),lensY=Math.max(h*.61,380),halfCols=Math.floor(TILE_COLS/2),halfRows=Math.floor(TILE_ROWS/2);state.lastHit.length=0;for(const tile of tiles){const rawX=tile.col*stepX+state.x,rawY=tile.row*stepY+state.y,wrapX=Math.floor((rawX+totalW*.5)/totalW),wrapY=Math.floor((rawY+totalH*.5)/totalH),x=wrap(rawX,totalW),y=wrap(rawY,totalH),nx=x/lensX,ny=y/lensY,radial=Math.hypot(nx,ny),r=clamp(radial/Math.SQRT2,0,1.35),edge=r>1?1:r,rSquared=r*r,dome=rSquared>=1?0:1-rSquared,edge155=Math.pow(edge,1.55),edge14=Math.pow(edge,1.4),edge175=Math.pow(edge,1.75),radialStretch=1+edge155*.34,lensXPos=x*radialStretch,lensYPos=y*(1+edge14*.11),z=state.effects.depth?(dome*34-Math.pow(r,1.7)*360):0,perspective=state.effects.depth?clamp(1/(1-z/1250),.72,1.08):1,baseScale=state.effects.depth?.86+dome*.17:.91,edgeScaleX=state.effects.depth?1+edge175*.56:1,edgeScaleY=state.effects.depth?1+edge155*.10:1,scaleX=baseScale*perspective*edgeScaleX,scaleY=baseScale*perspective*edgeScaleY,px=centerX+lensXPos,py=centerY+lensYPos,rotate=state.effects.depth?clamp(nx*.27-ny*.17,-.42,.42):0,alpha=state.effects.depth?clamp(1-Math.pow(Math.max(r-.56,0),1.35),.16,1):1,worldCol=wrapX*TILE_COLS+tile.col+halfCols,worldRow=wrapY*TILE_ROWS+tile.row+halfRows,movie=movieAtWorld(worldCol,worldRow);tile.movie=movie;tile.cx=px;tile.cy=py;tile.w=tileW*scaleX;tile.h=tileH*scaleY;tile.sx=scaleX;tile.sy=scaleY;tile.rotate=rotate;if(tile.moviePath!==movie?.poster_path){tile.moviePath=movie?.poster_path;tile.imageRecord=null}if(movie&&movie.poster_path){const record=tile.imageRecord||(tile.imageRecord=getImage(movie.poster_path));if(record&&record.ready&&!record.error)drawPoster(record.img,px,py,tileW,tileH,rotate,alpha,scaleX,scaleY);else drawPlaceholder(px,py,tileW,tileH,alpha,scaleX,scaleY)}if(px>-tile.w*.7&&px<w+tile.w*.7&&py>-tile.h*.7&&py<h+tile.h*.7&&movie)state.lastHit.push(tile)}drawCenterGlow(w,h)}
function drawCenterGlow(width,height){const radius=Math.min(width,height),gradient=ctx.createRadialGradient(width*.5,height*.47,radius*.05,width*.5,height*.47,radius*.47);gradient.addColorStop(0,"rgba(255,255,255,.035)");gradient.addColorStop(.5,"rgba(255,255,255,.012)");gradient.addColorStop(1,"rgba(0,0,0,0)");ctx.fillStyle=gradient;ctx.fillRect(0,0,width,height)}
function startRenderer(){if(state.rendering)return;state.rendering=true;state.lastT=performance.now();state.raf=requestAnimationFrame(tick)}
function tick(now){const dt=Math.min((now-state.lastT)/16.666,2.5);state.lastT=now;const moving=state.dragging||Math.abs(state.vx)>.012||Math.abs(state.vy)>.012;if(!state.dragging&&(Math.abs(state.vx)>.0001||Math.abs(state.vy)>.0001)){state.x+=state.vx*dt;state.y+=state.vy*dt;state.dirty=true;const friction=Math.pow(.90,dt);state.vx*=friction;state.vy*=friction;if(Math.abs(state.vx)<.012)state.vx=0;if(Math.abs(state.vy)<.012)state.vy=0}if(state.dirty||moving)draw();const stillMoving=state.dragging||Math.abs(state.vx)>.012||Math.abs(state.vy)>.012;if(stillMoving||state.dirty)state.raf=requestAnimationFrame(tick);else state.rendering=false}
function updateStatus(){const noun=state.mediaType==="tv"?"shows":"movies";statusText.textContent=state.query?`${state.movies.length} results`:`${state.movies.length} ${noun}`}
function contentTitle(item){return item?.title||item?.name||"Untitled"}
function contentOriginal(item){return item?.original_title||item?.original_name||""}
function contentDate(item){return item?.release_date||item?.first_air_date||""}
async function fetchPage(page,append=true){const requestId=state.requestId;const filters=state.searchFilters||{};const key=`${state.mediaType}:${state.mode}:${state.query}:${state.searchRaw}:${state.genre}:${state.minRating}:${filters.year||""}:${filters.decade||""}:${page}`;if(state.fetching.has(key)||state.pagesLoaded.has(page)||(page>state.totalPages&&state.totalPages>1))return;state.fetching.add(key);if(page===1)setLoading(true);try{let data;if(state.mode==="search"&&state.query.trim()){const path=state.mediaType==="tv"?"/search/tv":"/search/movie";const params={query:state.query.trim(),page,include_adult:"false"};if(filters.year)params[state.mediaType==="tv"?"first_air_date_year":"year"]=filters.year;data=await tmdb(path,params);data={...data,results:filterAdvancedResults(Array.isArray(data.results)?data.results:[])}}else if(state.mode==="search"){data=state.sort==="trending"?await tmdb(endpointFor("trending",state.mediaType),{}):await tmdb(endpointFor(state.sort,state.mediaType),discoverParams(page));data={...data,results:filterAdvancedResults(Array.isArray(data.results)?data.results:[])}}else if(state.sort==="trending"&&!state.query){data=await tmdb(endpointFor(state.sort,state.mediaType),{});if(page>1)data={...data,results:[]}}else if(state.sort==="popular"&&!state.genre&&!Number(state.minRating)){data=await tmdb(endpointFor("popular",state.mediaType),{page})}else data=await tmdb(endpointFor(state.sort,state.mediaType),discoverParams(page));if(requestId!==state.requestId)return;const incoming=(Array.isArray(data.results)?data.results:[]).filter(movie=>movie&&movie.id&&(movie.poster_path||movie.backdrop_path)&&(movie.title||movie.name));state.totalPages=Math.min(Number(data.total_pages)||1,500);if((state.sort==="trending"||state.mode==="search"&&state.sort==="trending")&&!state.query)state.totalPages=1;if(!append){state.movies=[];state.movieIds.clear();state.worldAssignments.clear();state.nextPage=1}for(const item of incoming)if(!state.movieIds.has(item.id)){state.movieIds.add(item.id);state.movies.push({...item,media_type:state.mediaType})}state.pagesLoaded.add(page);state.nextPage=Math.max(state.nextPage,page+1);saveCache();updateStatus();state.dirty=true;startRenderer();if(state.movies.length)primeImages(18)}catch(error){if(requestId!==state.requestId)return;console.error(error);if(!state.movies.length){statusText.textContent="tmdb error";showToast(error.message||"Could not load titles")}}finally{state.fetching.delete(key);if(requestId===state.requestId)setLoading(false)}}
function primeImages(count=14){for(let i=0;i<Math.min(count,state.movies.length);i++)getImage(state.movies[i].poster_path)}
async function resetAndLoad(mode="popular",query="",advanced=null){state.requestId+=1;state.mode=mode;state.query=query;state.searchRaw=mode==="search"?state.searchRaw:"";if(mode==="search"&&advanced){state.searchFilters=advanced;state.mediaType=advanced.mediaType;state.sort=advanced.sort;state.genre=advanced.genre;state.minRating=advanced.minRating}else if(mode!=="search"){state.searchFilters={mediaType:state.mediaType,sort:state.sort,genre:state.genre,minRating:state.minRating,year:null,decade:null}}state.nextPage=1;state.totalPages=1;state.pagesLoaded.clear();state.fetching.clear();state.travel=0;state.x=0;state.y=0;state.vx=0;state.vy=0;state.movies=[];state.movieIds.clear();state.worldAssignments.clear();state.lastHit.length=0;const label=mode==="search"?(state.searchRaw?`search · ${state.searchRaw}`:"search"):state.mediaType==="tv"?(mode==="trending"?"trending tv":mode==="top"?"top rated tv":mode==="newest"?"new tv":"tv cinema"):(mode==="trending"?"trending cinema":mode==="top"?"top rated":mode==="newest"?"new movies":"infinite cinema");modeLabel.textContent=label;state.dirty=true;startRenderer();closeFilters();await loadGenres(state.mediaType);if(mode==="search"&&state.genre)genreSelect.value=state.genre;await fetchPage(1,true);if(state.sort!=="trending"&&state.totalPages>1)fetchPage(2,true)}
function maybeLoadMore(){if(state.fetching.size||state.sort==="trending")return;if(state.nextPage<=state.totalPages&&state.movies.length){state.travel=0;fetchPage(state.nextPage,true)}}
const SHARE_BASE_URL="https://reel.gt.tc/";
const BASE_SHARE_IMAGE="https://github.com/user-attachments/assets/dd77a8ae-79c1-4e67-9cfa-03f7be95b11f";

function setMeta(property,content){
  let meta=document.querySelector(`meta[property="${property}"]`);
  if(!meta){
    meta=document.createElement("meta");
    meta.setAttribute("property",property);
    document.head.appendChild(meta);
  }
  meta.setAttribute("content",content||"");
}

function setNameMeta(name,content){
  let meta=document.querySelector(`meta[name="${name}"]`);
  if(!meta){
    meta=document.createElement("meta");
    meta.setAttribute("name",name);
    document.head.appendChild(meta);
  }
  meta.setAttribute("content",content||"");
}

function updateShareMetadata(movie){
  if(!movie)return;
  const title=contentTitle(movie);
  const description=(movie.overview||`Discover ${title} on Reel.`).replace(/\s+/g," ").trim();
  const image=backdropUrl(movie.backdrop_path||movie.poster_path);
  const shareUrl=`${SHARE_BASE_URL}?id=${encodeURIComponent(movie.id)}`;
  document.title=`${title} · Reel`;
  setMeta("og:type","website");
  setMeta("og:title",title);
  setMeta("og:description",description);
  setMeta("og:url",shareUrl);
  setMeta("og:image",image||BASE_SHARE_IMAGE);
  setMeta("og:image:alt",`${title} artwork`);
  setMeta("og:site_name","Reel");
  setNameMeta("twitter:card","summary_large_image");
  setNameMeta("twitter:title",title);
  setNameMeta("twitter:description",description);
  setNameMeta("twitter:image",image||BASE_SHARE_IMAGE);
}

function resetShareMetadata(){
  document.title="Reel";
  setMeta("og:type","website");
  setMeta("og:title","Reel");
  setMeta("og:description","Discover movies and TV shows on Reel.");
  setMeta("og:url",SHARE_BASE_URL);
  setMeta("og:image",BASE_SHARE_IMAGE);
  setMeta("og:image:alt","Reel");
  setMeta("og:site_name","Reel");
  setNameMeta("twitter:card","summary_large_image");
  setNameMeta("twitter:title","Reel");
  setNameMeta("twitter:description","Discover movies and TV shows on Reel.");
  setNameMeta("twitter:image",BASE_SHARE_IMAGE);
}

function updateShareUrl(id,replace=false){
  const url=new URL(location.href);
  url.searchParams.delete("id");
  if(id)url.searchParams.set("id",String(id));
  const next=url.pathname+(url.search||"")+(url.hash||"");
  if(replace)history.replaceState({id:id||null},"",next);
  else history.pushState({id:id||null},"",next);
}

async function copyShareLink(){
  if(!state.currentMovie)return;
  const url=`${SHARE_BASE_URL}?id=${encodeURIComponent(state.currentMovie.id)}`;
  try{
    await navigator.clipboard.writeText(url);
    showToast("share link copied");
  }catch{
    const textarea=document.createElement("textarea");
    textarea.value=url;
    textarea.style.position="fixed";
    textarea.style.left="-9999px";
    document.body.appendChild(textarea);
    textarea.select();
    try{
      document.execCommand("copy");
      showToast("share link copied");
    }catch{
      showToast("copy unavailable");
    }
    textarea.remove();
  }
}

async function openSharedTitle(id){
  const numericId=Number(id);
  if(!Number.isInteger(numericId)||numericId<=0)return false;
  try{
    let item;
    try{
      item=await tmdb(`/movie/${numericId}`);
      item.media_type="movie";
    }catch{
      item=await tmdb(`/tv/${numericId}`);
      item.media_type="tv";
    }
    if(!item||!item.id)return false;
    openMovie(item,{updateUrl:false});
    return true;
  }catch(error){
    console.error(error);
    showToast("could not open shared title");
    return false;
  }
}

function openMovie(movie,options={}){if(!movie)return;state.lastSelectedId=movie.id;state.currentMovie=movie;const isTV=state.mediaType==="tv"||movie.media_type==="tv"||(movie.name&&!movie.title);movie.media_type=isTV?"tv":"movie";const updateUrl=options.updateUrl!==false;if(updateUrl)updateShareUrl(movie.id,false);updateShareMetadata(movie);detailsBackdrop.src=backdropUrl(movie.backdrop_path);detailsBackdrop.style.display=movie.backdrop_path?"block":"none";modalPoster.src=imageUrl(movie.poster_path||movie.backdrop_path,"w500");modalTitle.textContent=contentTitle(movie);modalOriginal.textContent=contentOriginal(movie)&&contentOriginal(movie)!==contentTitle(movie)?contentOriginal(movie):"";modalYear.textContent=contentDate(movie)?contentDate(movie).slice(0,4):"";modalRuntime.textContent="";modalGenres.textContent="";modalRating.textContent=movie.vote_average?Number(movie.vote_average).toFixed(1):"—";modalVotes.textContent=movie.vote_count?formatVotes(movie.vote_count):"";modalOverview.textContent=movie.overview||"No overview available.";tmdbLink.href=`https://www.themoviedb.org/${isTV?"tv":"movie"}/${movie.id}`;trailerLink.hidden=true;trailerLink.removeAttribute("href");castSection.hidden=true;castList.innerHTML="";tvSelector.classList.remove("show");seasonSelect.innerHTML="";episodeSelect.innerHTML="";seasonSelect.onchange=null;detailsOverlay.classList.add("open");detailsScroll.scrollTop=0;loadMovieDetails(movie.id,isTV)}
async function loadMovieDetails(id,isTV){try{const details=await tmdb(`/${isTV?"tv":"movie"}/${id}`,{append_to_response:"credits,videos"});if(!detailsOverlay.classList.contains("open")||state.lastSelectedId!==id)return;if(isTV){modalRuntime.textContent=details.number_of_seasons?`${details.number_of_seasons} season${details.number_of_seasons===1?"":"s"}`:"";modalGenres.textContent=Array.isArray(details.genres)?details.genres.slice(0,3).map(genre=>genre.name).join(" · "):"";modalOriginal.textContent=details.original_name&&details.original_name!==details.name?details.original_name:"";buildTvSelector(details)}else{modalRuntime.textContent=details.runtime?`${details.runtime} min`:"";modalGenres.textContent=Array.isArray(details.genres)?details.genres.slice(0,3).map(genre=>genre.name).join(" · "):"";if(details.original_title&&details.original_title!==details.title)modalOriginal.textContent=details.original_title;tvSelector.classList.remove("show")}modalRating.textContent=details.vote_average?Number(details.vote_average).toFixed(1):"—";modalVotes.textContent=details.vote_count?formatVotes(details.vote_count):"";const cast=Array.isArray(details.credits?.cast)?details.credits.cast.filter(person=>person&&person.name).slice(0,8):[];if(cast.length){castSection.hidden=false;for(const person of cast){const item=document.createElement("div");item.className="cast-item";const photo=document.createElement("div");photo.className="cast-photo";const image=document.createElement("img");image.alt=person.name;image.src=person.profile_path?imageUrl(person.profile_path,"w185"):"";photo.append(image);const name=document.createElement("div");name.className="cast-name";name.textContent=person.name;item.append(photo,name);castList.append(item)}}const videos=Array.isArray(details.videos?.results)?details.videos.results:[];const trailer=videos.find(video=>video.type==="Trailer"&&video.site==="YouTube")||videos.find(video=>video.type==="Teaser"&&video.site==="YouTube");if(trailer){trailerLink.hidden=false;trailerLink.href=`https://www.youtube.com/watch?v=${trailer.key}`}}catch(error){console.error(error)}}
function buildTvSelector(details){const seasons=Array.isArray(details.seasons)?details.seasons.filter(season=>season&&season.season_number>0&&Number(season.episode_count)>0):[];if(!seasons.length){tvSelector.classList.remove("show");seasonSelect.innerHTML="";episodeSelect.innerHTML="";return}seasonSelect.innerHTML=seasons.map(season=>`<option value="${season.season_number}">season ${season.season_number}</option>`).join("");const populateEpisodes=()=>{const selectedSeason=seasons.find(season=>String(season.season_number)===seasonSelect.value)||seasons[0];const count=Math.max(1,Number(selectedSeason.episode_count)||1);episodeSelect.innerHTML="";for(let episode=1;episode<=count;episode++){const option=document.createElement("option");option.value=String(episode);option.textContent=`episode ${episode}`;episodeSelect.append(option)}};seasonSelect.onchange=populateEpisodes;seasonSelect.value=String(seasons[0].season_number);populateEpisodes();tvSelector.classList.add("show")}
function watchMovie(movie){if(!movie||!movie.id)return;state.currentMovie=movie;const isTV=movie.media_type==="tv"||state.mediaType==="tv"||(movie.name&&!movie.title);playerMovieTitle.textContent=contentTitle(movie);playerLoading.style.display="block";playerOverlay.classList.add("open");playerOverlay.setAttribute("aria-hidden","false");document.body.style.overflow="hidden";detailsOverlay.classList.remove("open");const params=new URLSearchParams({autoplay:"true",color:"%23ffffff",quality:"1080",back:"close"});if(isTV){params.set("s",seasonSelect.value||"1");params.set("e",episodeSelect.value||"1");params.set("autonext","true");playerIframe.src=`https://cinesrc.st/embed/tv/${encodeURIComponent(movie.id)}?${params.toString()}`}else playerIframe.src=`https://cinesrc.st/embed/movie/${encodeURIComponent(movie.id)}?${params.toString()}`}
function closePlayer(){playerOverlay.classList.remove("open");playerOverlay.setAttribute("aria-hidden","true");playerLoading.style.display="none";playerIframe.src="about:blank";document.body.style.overflow="";try{if(document.fullscreenElement)document.exitFullscreen()}catch{}}
watchButton.addEventListener("click",()=>{if(state.currentMovie)watchMovie(state.currentMovie)});
shareButton.addEventListener("click",copyShareLink);
playerClose.addEventListener("click",closePlayer);
playerFullscreen.addEventListener("click",async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else await playerFrame.requestFullscreen()}catch{showToast("fullscreen is unavailable")}});
playerIframe.addEventListener("load",()=>{playerLoading.style.display="none"});
window.addEventListener("message",event=>{if(event.origin!=="https://cinesrc.st")return;const data=event.data||{};if(data.type==="cinesrc:close")closePlayer();if(data.type==="cinesrc:error"){playerLoading.style.display="none";showToast("player reported a streaming error")}});
let surpriseTimer=null;function closeSurprise(){clearTimeout(surpriseTimer);surpriseOverlay.classList.remove("open","loading");surpriseOverlay.setAttribute("aria-hidden","true");surpriseCard.classList.remove("reveal");surpriseKicker.textContent="finding something...";surpriseMeta.textContent=""}function runSurprise(){if(!state.movies.length){showToast("titles are still loading");return}const candidates=state.movies.length>1&&state.currentMovie?state.movies.filter(movie=>movie.id!==state.currentMovie.id):state.movies.slice();if(!candidates.length){showToast("not enough titles loaded yet");return}const movie=candidates[Math.floor(Math.random()*candidates.length)];const isTV=state.mediaType==="tv"||movie.media_type==="tv"||(movie.name&&!movie.title);surprisePoster.src=imageUrl(movie.poster_path||movie.backdrop_path,"w500");surprisePoster.alt=contentTitle(movie);surpriseTitle.textContent="surprise me";surpriseMeta.textContent="";surpriseKicker.textContent="finding something...";surpriseOverlay.classList.add("open","loading");surpriseOverlay.setAttribute("aria-hidden","false");surpriseCard.classList.remove("reveal");clearTimeout(surpriseTimer);requestAnimationFrame(()=>{requestAnimationFrame(()=>{surpriseCard.classList.add("reveal");surpriseKicker.textContent="your pick";surpriseTitle.textContent=contentTitle(movie);surpriseMeta.textContent=[contentDate(movie)?.slice(0,4),isTV?"tv":"movie",movie.vote_average?`★ ${Number(movie.vote_average).toFixed(1)}`:""].filter(Boolean).join(" · ");surpriseOverlay.classList.remove("loading")})});surpriseTimer=setTimeout(()=>{closeSurprise();openMovie(movie)},1250)}
function closeMovie(updateUrl=true){detailsOverlay.classList.remove("open");state.lastSelectedId=null;if(updateUrl){updateShareUrl(null,true);resetShareMetadata()}}
document.getElementById("modalClose").addEventListener("click",closeMovie);
detailsOverlay.addEventListener("click",event=>{if(event.target===detailsOverlay)closeMovie()});
function hitTest(x,y){let best=null,bestDistance=Infinity;for(let i=state.lastHit.length-1;i>=0;i--){const tile=state.lastHit[i],dx=x-tile.cx,dy=y-tile.cy,radiusX=tile.w*.5+8,radiusY=tile.h*.5+8;if(Math.abs(dx)<=radiusX&&Math.abs(dy)<=radiusY){const distance=(dx*dx)/(radiusX*radiusX)+(dy*dy)/(radiusY*radiusY);if(distance<bestDistance){bestDistance=distance;best=tile}}}return best?.movie||null}
canvas.addEventListener("pointerdown",event=>{if(detailsOverlay.classList.contains("open"))return;state.dragging=true;state.moved=false;state.px=event.clientX;state.py=event.clientY;state.downX=event.clientX;state.downY=event.clientY;state.vx=0;state.vy=0;state.travel=0;state.dirty=true;canvas.classList.add("dragging");center.classList.add("dim");try{canvas.setPointerCapture(event.pointerId)}catch{}startRenderer()});
canvas.addEventListener("pointermove",event=>{if(!state.dragging)return;const dx=event.clientX-state.px,dy=event.clientY-state.py;state.px=event.clientX;state.py=event.clientY;if(Math.hypot(event.clientX-state.downX,event.clientY-state.downY)>6)state.moved=true;state.x+=dx;state.y+=dy;state.travel+=Math.abs(dx)+Math.abs(dy);state.vx=dx*.72;state.vy=dy*.72;state.dirty=true;startRenderer();if(state.travel>760&&state.nextPage<=state.totalPages)maybeLoadMore()});
function endPointer(){state.dragging=false;canvas.classList.remove("dragging");if(!state.moved){const movie=hitTest(state.px,state.py);if(movie)openMovie(movie)}setTimeout(()=>{state.moved=false},80)}
canvas.addEventListener("pointerup",endPointer);
canvas.addEventListener("pointercancel",()=>{state.dragging=false;canvas.classList.remove("dragging")});
canvas.addEventListener("wheel",event=>{if(detailsOverlay.classList.contains("open"))return;event.preventDefault();const multiplier=event.deltaMode===1?15:1,dx=event.deltaX*multiplier*.62,dy=event.deltaY*multiplier*.62;state.x-=dx;state.y-=dy;state.travel+=Math.abs(dx)+Math.abs(dy);state.vx=-dx*.24;state.vy=-dy*.24;state.dirty=true;center.classList.add("dim");startRenderer();if(state.travel>760&&state.nextPage<=state.totalPages)maybeLoadMore();clearTimeout(canvas.centerTimer);canvas.centerTimer=setTimeout(()=>center.classList.remove("dim"),800)},{passive:false});
function openSearch(){searchPanel.classList.add("open");updateSearchTags();setTimeout(()=>searchInput.focus(),100)}
function closeSearch(){searchPanel.classList.remove("open")}
function openFilters(){filtersPanel.classList.add("open");filterButton.setAttribute("aria-expanded","true")}
function closeFilters(){filtersPanel.classList.remove("open");filterButton.setAttribute("aria-expanded","false")}
filterButton.addEventListener("click",event=>{event.stopPropagation();settingsPanel.classList.remove("open");filterButton.focus();filtersPanel.classList.contains("open")?closeFilters():openFilters()});

function openSettings(){settingsPanel.classList.add("open");settingsButton.setAttribute("aria-expanded","true");filtersPanel.classList.remove("open");filterButton.setAttribute("aria-expanded","false")}
function closeSettings(){settingsPanel.classList.remove("open");settingsButton.setAttribute("aria-expanded","false")}
settingsButton.addEventListener("click",event=>{event.stopPropagation();settingsPanel.classList.contains("open")?closeSettings():openSettings()});

function toggleEffect(toggle,key){state.effects[key]=!state.effects[key];toggle.setAttribute("aria-checked",String(state.effects[key]));saveSettings();updateEffectUI();if(key==="blur")buildGradualBlur();state.dirty=true;startRenderer()}
blurToggle.addEventListener("click",()=>toggleEffect(blurToggle,"blur"));
vignetteToggle.addEventListener("click",()=>toggleEffect(vignetteToggle,"vignette"));
depthToggle.addEventListener("click",()=>toggleEffect(depthToggle,"depth"));
uiBlurToggle.addEventListener("click",()=>toggleEffect(uiBlurToggle,"uiBlur"));
qualitySelect.addEventListener("change",()=>{adaptivePerformanceLevel=0;applyQualityMode(qualitySelect.value,false);saveSettings();updateEffectUI()});
settingsDefaults.addEventListener("click",()=>{state.effects={blur:true,vignette:true,depth:true,uiBlur:true};adaptivePerformanceLevel=0;applyQualityMode("auto",false);saveSettings();updateEffectUI();showToast("settings restored")});
document.getElementById("searchButton").addEventListener("click",openSearch);
document.getElementById("searchClose").addEventListener("click",closeSearch);
searchInput.addEventListener("input",()=>{updateSearchTags();clearTimeout(state.searchTimer);const query=searchInput.value.trim();state.searchTimer=setTimeout(()=>{query?runAdvancedSearch(query):resetAndLoad(state.sort,"")},360)});
searchInput.addEventListener("keydown",event=>{if(event.key==="Enter"){event.preventDefault();clearTimeout(state.searchTimer);const query=searchInput.value.trim();query?runAdvancedSearch(query):resetAndLoad(state.sort,"")}});
searchTags?.addEventListener("click",event=>{const button=event.target.closest(".search-tag");if(button)appendSearchTag(button.dataset.tag)});
mediaType.addEventListener("change",()=>{state.mediaType=mediaType.value;state.genre="";state.searchFilters={...state.searchFilters,mediaType:state.mediaType,genre:""};loadGenres(mediaType.value);if(searchInput.value.trim())updateSearchTags()});
filterApply.addEventListener("click",()=>{state.mediaType=mediaType.value;state.sort=sortType.value;state.genre=genreSelect.value;state.minRating=Number(minRating.value)||0;const raw=searchInput.value.trim();if(raw)runAdvancedSearch(raw);else resetAndLoad(state.sort,"")});
filterReset.addEventListener("click",()=>{mediaType.value="movie";sortType.value="popular";minRating.value="0";state.mediaType="movie";state.sort="popular";state.genre="";state.minRating=0;state.searchFilters={mediaType:"movie",sort:"popular",genre:"",minRating:0,year:null,decade:null};const raw=searchInput.value.trim();raw?runAdvancedSearch(raw):resetAndLoad("popular","")});
document.getElementById("popularButton").addEventListener("click",()=>{state.sort="popular";sortType.value="popular";resetAndLoad("popular","")});
document.getElementById("trendingButton").addEventListener("click",()=>{state.sort="trending";sortType.value="trending";resetAndLoad("trending","")});
document.getElementById("exploreButton").addEventListener("click",()=>{center.classList.add("dim");state.vx=1.85;state.vy=-1.05;state.travel+=700;state.dirty=true;startRenderer();if(state.sort!=="trending"&&state.nextPage<=state.totalPages)maybeLoadMore();setTimeout(()=>center.classList.remove("dim"),1000)});
document.getElementById("randomButton").addEventListener("click",runSurprise);
document.addEventListener("click",event=>{if(!filtersPanel.contains(event.target)&&event.target!==filterButton)closeFilters();if(!settingsPanel.contains(event.target)&&event.target!==settingsButton)closeSettings()});surpriseOverlay.addEventListener("click",event=>{if(event.target===surpriseOverlay)closeSurprise()});
document.addEventListener("keydown",event=>{if(event.key==="Escape"){if(surpriseOverlay.classList.contains("open")){closeSurprise();return}if(playerOverlay.classList.contains("open")){closePlayer();return}closeMovie();closeSearch();closeFilters();closeSettings()}if(event.key==="/"&&document.activeElement!==searchInput&&!playerOverlay.classList.contains("open")){event.preventDefault();openSearch()}if(playerOverlay.classList.contains("open")||detailsOverlay.classList.contains("open"))return;if(event.key==="ArrowLeft"){state.vx+=1.1;state.dirty=true;startRenderer()}if(event.key==="ArrowRight"){state.vx-=1.1;state.dirty=true;startRenderer()}if(event.key==="ArrowUp"){state.vy+=1.1;state.dirty=true;startRenderer()}if(event.key==="ArrowDown"){state.vy-=1.1;state.dirty=true;startRenderer()}});
window.addEventListener("popstate",async()=>{
  const id=new URL(location.href).searchParams.get("id");
  if(id){
    await openSharedTitle(id);
  }else{
    closeMovie(false);
    resetShareMetadata();
  }
});

let resizeTimer;window.addEventListener("resize",()=>{clearTimeout(resizeTimer);resizeTimer=setTimeout(()=>{resizeCanvas();state.dirty=true;startRenderer()},100)});
async function boot(){loadSettings();lowPowerDevice=detectLowPowerDevice();applyQualityMode(QUALITY_MODE,false);updateEffectUI();updateSearchTags();if(window.lucide)lucide.createIcons();resetShareMetadata();const sharedId=new URL(location.href).searchParams.get("id");loadCache();mediaType.value=state.mediaType;sortType.value=state.sort;minRating.value=String(state.minRating);await loadGenres(state.mediaType);if(state.movies.length){updateStatus();primeImages(18);state.dirty=true;startRenderer()}try{setLoading(true);state.movies=[];state.movieIds.clear();state.pagesLoaded.clear();state.nextPage=1;state.totalPages=1;state.worldAssignments.clear();state.dirty=true;await fetchPage(1,true);if(state.sort!=="trending"&&state.totalPages>1)fetchPage(2,true);if(sharedId)await openSharedTitle(sharedId)}catch(error){console.error(error);if(state.movies.length){updateStatus();showToast("using cached titles · "+(error.message||"TMDB unavailable"))}else{statusText.textContent="tmdb error";showToast(error.message||"Could not connect to TMDB")}}finally{setLoading(false)}setupPerformanceMonitor()}
if(window.lucide)lucide.createIcons();
boot();
</script>
</body>
</html>
