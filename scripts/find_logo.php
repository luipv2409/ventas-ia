<?php
$c = file_get_contents(__DIR__ . '/../knowledge/ULAT/Universidad Latinoamericana (28_10_2026 1：00：04 p.m.).html');
$targetDir = __DIR__ . '/../assets/ulat';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// 1. Save Header Logo
preg_match('/--sf-img-\d+:\s*url\("data:image\/(png|jpeg|jpg);base64,([^"]+)"\)/i', $c, $mCss);
if (!empty($mCss[2])) {
    $bin = base64_decode($mCss[2]);
    file_put_contents($targetDir . '/logo.png', $bin);
    echo "Saved logo.png (size: " . strlen($bin) . " bytes)\n";
}

// 2. Find larger images for banners
preg_match_all('/data:image\/(jpeg|jpg|png);base64,([A-Za-z0-9+\/=\s]+)/', $c, $mAll);
echo "Total inline base64 images found: " . count($mAll[0]) . "\n";
$savedCount = 0;
foreach ($mAll[2] as $idx => $b64) {
    $cleanB64 = preg_replace('/\s+/', '', $b64);
    $bin = base64_decode($cleanB64);
    $info = @getimagesizefromstring($bin);
    if ($info && $info[0] >= 400 && $info[1] >= 200) {
        $savedCount++;
        $ext = ($info['mime'] === 'image/png') ? 'png' : 'jpg';
        $filename = "banner_{$savedCount}_{$info[0]}x{$info[1]}.{$ext}";
        file_put_contents($targetDir . '/' . $filename, $bin);
        echo "Saved {$filename} ({$info[0]}x{$info[1]}, " . round(strlen($bin)/1024) . " KB)\n";
    }
}

