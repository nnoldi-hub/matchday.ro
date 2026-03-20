<?php
/**
 * PWA Icon Generator
 * Generates PNG icons in various sizes from the base SVG
 * 
 * Usage: Run this script once to generate all icons
 * php generate-icons.php
 * 
 * Requirements: GD library with PNG support
 */

// Icon sizes needed for PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

$iconsDir = __DIR__ . '/assets/images/icons/';

// Check if Imagick is available (better for SVG)
if (extension_loaded('imagick')) {
    echo "Using Imagick for SVG conversion...\n";
    
    foreach ($sizes as $size) {
        $imagick = new Imagick();
        $imagick->readImage($iconsDir . 'icon.svg');
        $imagick->setImageFormat('png');
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        $imagick->writeImage($iconsDir . "icon-{$size}x{$size}.png");
        $imagick->destroy();
        echo "Generated: icon-{$size}x{$size}.png\n";
    }
    
    echo "\nAll icons generated successfully!\n";
    exit(0);
}

// Fallback: Generate simple GD-based icons
if (!extension_loaded('gd')) {
    die("Error: GD or Imagick extension is required.\n");
}

echo "Using GD to generate placeholder icons...\n";

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Enable alpha blending
    imagealphablending($image, false);
    imagesavealpha($image, true);
    
    // Colors
    $bgDark = imagecolorallocate($image, 26, 26, 46);
    $accent = imagecolorallocate($image, 233, 69, 96);
    $white = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $size, $size, $bgDark);
    
    // Draw rounded corners effect (simple circle in center)
    $centerX = $size / 2;
    $centerY = $size / 2;
    $radius = $size * 0.35;
    
    // Draw football/soccer ball outline
    imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $accent);
    imagesetthickness($image, max(1, $size / 30));
    
    // Draw center dot
    $dotRadius = $size * 0.08;
    imagefilledellipse($image, $centerX, $centerY, $dotRadius * 2, $dotRadius * 2, $accent);
    
    // Draw "MD" text if size allows
    if ($size >= 96) {
        $fontSize = $size / 8;
        $text = "MD";
        $textWidth = imagefontwidth(5) * strlen($text);
        $textX = $centerX - ($textWidth / 2);
        $textY = $size - ($size * 0.15);
        imagestring($image, 5, $textX, $textY, $text, $white);
    }
    
    // Save PNG
    $filename = $iconsDir . "icon-{$size}x{$size}.png";
    imagepng($image, $filename, 9);
    imagedestroy($image);
    
    echo "Generated: icon-{$size}x{$size}.png\n";
}

echo "\nPlaceholder icons generated. For better quality, use an image editor to export the SVG.\n";
echo "SVG source: assets/images/icons/icon.svg\n";
