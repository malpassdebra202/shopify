<?php
session_start();

// Load config and functions
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Set default values if config is empty
if (!is_array($config)) {
    $config = array(
        'store' => array(
            'name' => 'Store Name',
            'currency' => 'USD',
            'theme_color' => '#FFBABA',
            'logo' => '/img/logo.jpg',
        ),
        'product' => array(
            'name' => 'Product Name',
            'price' => 0.00,
            'size' => '',
            'image' => '/img/product.jpg',
            'shipping' => array(
                'method' => 'Standard Shipping',
                'price' => 'FREE'
            )
        )
    );
}

// Add this after the existing config array definition
if (!isset($config['generated_links'])) {
    $config['generated_links'] = array();
}

// Add this helper function to get the base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start with existing config
    $newConfig = $config;
    
    // Handle link generation
    if (isset($_POST['save_generated_link'])) {
        $linkId = uniqid('store_');
        
        // Handle store logo upload for this link
        $linkStoreLogo = get_config_value('store.logo'); // Default to main store logo
        if (isset($_FILES['link_store_logo']) && $_FILES['link_store_logo']['error'] === 0) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/links/' . $linkId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = basename($_FILES['link_store_logo']['name']);
            $fileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
            $logoPath = '/assets/images/links/' . $linkId . '/' . $fileName;
            $fullPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['link_store_logo']['tmp_name'], $fullPath)) {
                chmod($fullPath, 0644);
                $linkStoreLogo = $logoPath;
            }
        }
        
        // Handle product image upload for this link
        $linkProductImage = get_config_value('product.image'); // Default to main product image
        if (isset($_FILES['link_product_image']) && $_FILES['link_product_image']['error'] === 0) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/links/' . $linkId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = basename($_FILES['link_product_image']['name']);
            $fileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
            $imagePath = '/assets/images/links/' . $linkId . '/' . $fileName;
            $fullPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['link_product_image']['tmp_name'], $fullPath)) {
                chmod($fullPath, 0644);
                $linkProductImage = $imagePath;
            }
        }
        
        $linkConfig = array(
            'id' => $linkId,
            'created_at' => date('Y-m-d H:i:s'),
            'store' => array(
                'name' => strip_tags($_POST['link_store_name']),
                'currency' => $_POST['link_currency'],
                'theme_color' => $_POST['link_theme_color'],
                'logo' => $linkStoreLogo,
            ),
            'product' => array(
                'name' => strip_tags($_POST['link_product_name']),
                'price' => floatval($_POST['link_product_price']),
                'size' => strip_tags($_POST['link_product_size']),
                'image' => $linkProductImage,
                'shipping' => array(
                    'method' => 'Standard Shipping',
                    'price' => 'FREE'
                )
            ),
            'tracking' => array(
                'facebook_pixel' => strip_tags($_POST['link_fb_pixel']),
                'tiktok_pixel' => strip_tags($_POST['link_tiktok_pixel'])
            )
        );
        
        $newConfig['generated_links'][$linkId] = $linkConfig;
        $successMessage = "New store link generated successfully!";
    }
    // Handle regular store updates
    else if (!isset($_POST['delete_link'])) {
        // Update store settings
        if (isset($newConfig['store'])) {
            if (isset($_POST['theme_color'])) {
                $newConfig['store']['theme_color'] = $_POST['theme_color'];
            }
            if (isset($_POST['store_name'])) {
                $newConfig['store']['name'] = strip_tags($_POST['store_name']);
            }
            if (isset($_POST['currency'])) {
                $newConfig['store']['currency'] = $_POST['currency'];
            }
        }

        // Update product settings
        if (isset($newConfig['product'])) {
            if (isset($_POST['product_name'])) {
                $newConfig['product']['name'] = strip_tags($_POST['product_name']);
            }
            if (isset($_POST['product_price'])) {
                $newConfig['product']['price'] = floatval($_POST['product_price']);
            }
            if (isset($_POST['product_size'])) {
                $newConfig['product']['size'] = strip_tags($_POST['product_size']);
            }
        }
        
        $successMessage = "Store configuration updated successfully!";
    }

    // Handle link deletion
    if (isset($_POST['delete_link']) && isset($_POST['link_id'])) {
        $linkId = $_POST['link_id'];
        if (isset($newConfig['generated_links'][$linkId])) {
            unset($newConfig['generated_links'][$linkId]);
            $successMessage = "Store link deleted successfully!";
        }
    }
    
    // Handle file uploads
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['store_logo']['name']);
        $fileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $logoPath = '/assets/images/' . $fileName;
        $fullPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $fullPath)) {
            chmod($fullPath, 0644);
            $newConfig['store']['logo'] = $logoPath;
        }
    }
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['product_image']['name']);
        $fileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $imagePath = '/img/' . $fileName;
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $fullPath)) {
            chmod($fullPath, 0644);
            $newConfig['product']['image'] = $imagePath;
        }
    }

    // Save all configuration changes
    $configContent = "<?php return " . var_export($newConfig, true) . ";";
    if (file_put_contents(__DIR__ . '/config.php', $configContent, LOCK_EX)) {
        $config = $newConfig;
        error_log('Config saved successfully');
    } else {
        $errorMessage = "Failed to save configuration. Please check file permissions.";
        error_log('Failed to save config. Current permissions: ' . substr(sprintf('%o', fileperms('config.php')), -4));
    }
}

// Helper function to safely get config values
function get_config_value($path, $default = '') {
    global $config;
    $keys = explode('.', $path);
    $value = $config;
    
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return $default;
        }
        $value = $value[$key];
    }
    
    return $value;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">Store Admin</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin_panel.php" class="text-gray-600 hover:text-gray-900">Sessions</a>
                    <a href="/admin.php" class="text-blue-600 font-medium">Settings</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($successMessage)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <?php endif; ?>

        <div class="md:flex md:space-x-6">
            <!-- Main Configuration Form -->
            <div class="md:w-2/3">
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <!-- Store Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Store Information
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                                    <input type="text" name="store_name" value="<?php echo htmlspecialchars(get_config_value('store.name', 'Store Name')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                    <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <?php
                                        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
                                        foreach ($currencies as $currency) {
                                            $selected = $currency === get_config_value('store.currency', 'USD') ? 'selected' : '';
                                            echo "<option value=\"$currency\" $selected>$currency</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Theme Color</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" name="theme_color" value="<?php echo get_config_value('store.theme_color', '#FFBABA'); ?>" 
                                               class="h-10 w-20 rounded border border-gray-300">
                                        <span class="text-sm text-gray-500"><?php echo get_config_value('store.theme_color', '#FFBABA'); ?></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Logo</label>
                                    <input type="file" name="store_logo" accept="image/*" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php if (get_config_value('store.logo')): ?>
                                    <img src="<?php echo htmlspecialchars(get_config_value('store.logo')); ?>" alt="Current logo" class="h-8 mt-2">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Product Information -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                Product Information
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                                    <input type="text" name="product_name" value="<?php echo htmlspecialchars(get_config_value('product.name', 'Product Name')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Price</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <?php echo get_config_value('store.currency', 'USD'); ?>
                                        </span>
                                        <input type="number" name="product_price" value="<?php echo get_config_value('product.price', 0.00); ?>" step="0.01" 
                                               class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Size</label>
                                    <input type="text" name="product_size" value="<?php echo htmlspecialchars(get_config_value('product.size', '')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                                    <input type="file" name="product_image" accept="image/*" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php if (get_config_value('product.image')): ?>
                                    <img src="<?php echo htmlspecialchars(get_config_value('product.image')); ?>" alt="Current product" class="h-20 mt-2 rounded-md object-cover">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="md:w-1/3 mt-6 md:mt-0">
                <div class="bg-white rounded-lg shadow-sm border p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Live Preview
                    </h2>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4 pb-4 border-b">
                            <?php if (get_config_value('store.logo')): ?>
                            <img src="<?php echo htmlspecialchars(get_config_value('store.logo')); ?>" alt="Store logo" class="h-8">
                            <?php endif; ?>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars(get_config_value('store.name', 'Store Name')); ?></span>
                        </div>
                        <div class="flex items-start space-x-4">
                            <?php if (get_config_value('product.image')): ?>
                            <img src="<?php echo htmlspecialchars(get_config_value('product.image')); ?>" alt="Product" 
                                 class="w-24 h-24 object-cover rounded-lg">
                            <?php endif; ?>
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars(get_config_value('product.name', 'Product Name')); ?></h3>
                                <p class="text-sm text-gray-500">Size: <?php echo htmlspecialchars(get_config_value('product.size', '')); ?></p>
                                <p class="font-medium text-gray-900 mt-2"><?php echo formatPrice(get_config_value('product.price', 0.00)); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated Links Section -->
        <div class="mt-6">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Generated Links
                    </h2>
                    <button type="button" onclick="showGenerateLinkModal()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Generate New Link
                    </button>
                </div>
                
                <div class="space-y-4">
                    <?php if (!empty($config['generated_links'])): ?>
                        <?php foreach ($config['generated_links'] as $linkId => $linkData): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($linkData['store']['name']); ?></h3>
                                        <p class="text-sm text-gray-500">Created: <?php echo $linkData['created_at']; ?></p>
                                        <div class="mt-2">
                                            <input type="text" readonly value="<?php echo htmlspecialchars(getBaseUrl() . '?store=' . $linkId); ?>" 
                                                   class="text-sm w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                        </div>
                                    </div>
                                    <form method="POST" class="ml-4">
                                        <input type="hidden" name="link_id" value="<?php echo htmlspecialchars($linkId); ?>">
                                        <button type="submit" name="delete_link" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No links generated yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Generate Link Modal -->
        <div id="generateLinkModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
                <form method="POST" class="p-6" enctype="multipart/form-data">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Generate New Store Link</h3>
                        <button type="button" onclick="hideGenerateLinkModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Store Information -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-4">Store Information</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                                    <input type="text" name="link_store_name" value="<?php echo htmlspecialchars(get_config_value('store.name')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                    <select name="link_currency" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <?php
                                        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
                                        foreach ($currencies as $currency) {
                                            $selected = $currency === get_config_value('store.currency') ? 'selected' : '';
                                            echo "<option value=\"$currency\" $selected>$currency</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Theme Color</label>
                                    <input type="color" name="link_theme_color" value="<?php echo get_config_value('store.theme_color'); ?>" 
                                           class="h-10 w-20 rounded border border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Logo</label>
                                    <input type="file" name="link_store_logo" accept="image/*" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>

                        <!-- Product Information -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-4">Product Information</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                                    <input type="text" name="link_product_name" value="<?php echo htmlspecialchars(get_config_value('product.name')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Price</label>
                                    <input type="number" name="link_product_price" value="<?php echo get_config_value('product.price'); ?>" step="0.01" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Size</label>
                                    <input type="text" name="link_product_size" value="<?php echo htmlspecialchars(get_config_value('product.size')); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                                    <input type="file" name="link_product_image" accept="image/*" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>

                        <!-- Tracking Settings -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-4">Tracking Settings</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel ID</label>
                                    <input type="text" name="link_fb_pixel" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">TikTok Pixel ID</label>
                                    <input type="text" name="link_tiktok_pixel" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideGenerateLinkModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="save_generated_link" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Generate Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview theme color changes
        document.querySelector('input[name="theme_color"]').addEventListener('input', function(e) {
            document.documentElement.style.setProperty('--theme-color', e.target.value);
        });

        // Modal functions
        function showGenerateLinkModal() {
            document.getElementById('generateLinkModal').classList.remove('hidden');
        }

        function hideGenerateLinkModal() {
            document.getElementById('generateLinkModal').classList.add('hidden');
        }
    </script>
</body>
</html> 