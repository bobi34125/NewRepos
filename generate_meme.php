<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toptext']) && isset($_POST['bottomtext'])) {
    $toptext = $_POST['toptext'];
    $bottomtext = $_POST['bottomtext'];
    $imagePath = $_SESSION['selected_image'];
    
    $font = $_POST['font'];
    $fontSize = intval($_POST['font-size']);
    $fontColor = $_POST['font-color'];
    $textStyle = $_POST['text-style']; // Styles are space-separated

    // Map font options to file paths
    $fontFiles = [
        'arial' => 'fonts/arial.ttf',
        'arial_bold' => 'fonts/arial_bold.ttf',
        'arial_italic' => 'fonts/arial_italic.ttf',
        'arial_bold_italic' => 'fonts/arial_bold_italic.ttf'
    ];

    $fontPath = $fontFiles[$font] ?? $fontFiles['arial'];

    // Load the image
    $image = imagecreatefromjpeg($imagePath);
    if (!$image) {
        die("Failed to load image.");
    }
    
    // Allocate color
    list($r, $g, $b) = sscanf($fontColor, "#%02x%02x%02x");
    $textColor = imagecolorallocate($image, $r, $g, $b);
    
    // Ensure font size is reasonable
    if ($fontSize < 10) {
        $fontSize = 10;
    }
    
    // Function to add text with optional underline
    function addText($image, $text, $fontPath, $fontSize, $textColor, $x, $y, $styles) {
        // Add text
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);

        // Draw underline if required
        if (strpos($styles, 'underline') !== false) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $underlineY = $y + 2; // Adjust this value as needed
            imageline($image, $x, $underlineY, $x + $textWidth, $underlineY, $textColor);
        }
    }
    
    // Add text to the image (top and bottom text)
    // Top text
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $toptext);
    if ($bbox) {
        $textWidth = abs($bbox[4] - $bbox[0]);
        $textHeight = abs($bbox[5] - $bbox[1]);
        $x = (imagesx($image) - $textWidth) / 2;
        $y = $textHeight + 10; // Position from the top
        addText($image, $toptext, $fontPath, $fontSize, $textColor, $x, $y, $textStyle);
    } else {
        die("Failed to render top text.");
    }
    
    // Bottom text
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $bottomtext);
    if ($bbox) {
        $textWidth = abs($bbox[4] - $bbox[0]);
        $textHeight = abs($bbox[5] - $bbox[1]);
        $x = (imagesx($image) - $textWidth) / 2;
        $y = imagesy($image) - 10; // Position from the bottom
        addText($image, $bottomtext, $fontPath, $fontSize, $textColor, $x, $y, $textStyle);
    } else {
        die("Failed to render bottom text.");
    }

    // Save the image
    $outputPath = 'uploads/generated_meme_' . time() . '.jpg'; // Unique filename
    if (!imagejpeg($image, $outputPath)) {
        die("Failed to save image.");
    }
    imagedestroy($image);

    // Redirect to download page with the image path
    header("Location: download_meme.php?image=" . urlencode($outputPath));
    exit();
}
?>

