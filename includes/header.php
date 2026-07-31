<?php
if (!function_exists('asset_url')) require_once __DIR__ . '/functions.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$headerNavItems = isset($headerNavItems) && is_array($headerNavItems) ? $headerNavItems : array(
    array('href' => 'index.php', 'label' => 'Trang chủ'),
    array('href' => 'rooms.php', 'label' => 'Phòng'),
    array('href' => 'feedback.php', 'label' => 'Đánh giá'),
);
$assetPathPrefix = isset($assetPathPrefix) ? $assetPathPrefix : '';
$hideBrandText = isset($hideBrandText) ? (bool) $hideBrandText : false;
$skipHeader = isset($skipHeader) ? (bool) $skipHeader : false;
$brandHref = isset($brandHref) ? $brandHref : 'index.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'OASIS Homestay'); ?></title>
    <link rel="icon" href="<?php echo h(asset_url($assetPathPrefix . 'assets/logo/oasis-logo-short.png')); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo h(asset_url($assetPathPrefix . 'includes/site.css')); ?>">
</head>
<body>
<?php if (!$skipHeader) { ?><header class="site-header"><div class="container nav"><a class="brand" href="<?php echo h($brandHref); ?>" aria-label="OASIS Homestay"><img class="brand-mark" src="<?php echo h(asset_url($assetPathPrefix . 'assets/logo/oasis-logo.png')); ?>" alt="OASIS Homestay"></a><nav class="menu"><?php foreach ($headerNavItems as $item) { ?><a href="<?php echo h($item['href']); ?>"><?php echo h($item['label']); ?></a><?php } ?></nav></div></header><?php } ?>
