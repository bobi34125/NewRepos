<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=meme_generator', 'root', ''); // Update with your DB credentials

// Default image if no session image is set
if (!isset($_SESSION['selected_image'])) {
    $_SESSION['selected_image'] = 'assets/images/default.jpg'; // Default image
}

// Fetch pre-added images from the database
$meme_images = [];
$stmt = $pdo->query('SELECT * FROM meme_images');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $meme_images[] = $row;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadfile'])) {
    if ($_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        
        // Check if the directory exists, create it if it doesn't
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                die("Failed to create upload directory.");
            }
        }

        $file_tmp = $_FILES['uploadfile']['tmp_name'];
        $file_name = basename($_FILES['uploadfile']['name']);
        $upload_file = $upload_dir . $file_name;

        // Move the uploaded file
        if (move_uploaded_file($file_tmp, $upload_file)) {
            $_SESSION['selected_image'] = $upload_file;
        } else {
            echo "Failed to move uploaded file.";
        }
    } else {
        echo "Upload error: " . $_FILES['uploadfile']['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meme Generator</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Open+Sans:wght@300;600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
            margin-top: 20px;
            color: #333;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"], .form-group select, .form-group input[type="number"], .form-group input[type="color"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
 #color-preview {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 1px solid #ddd;
            margin-left: 10px;
            vertical-align: middle;
        }
        .form-group input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .meme-preview, .meme-gallery img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: block;
            margin: 20px auto;
        }
        .meme-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .meme-gallery img {
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
            border-radius: 8px;
        }
        .meme-gallery img:hover {
            border-color: #007bff;
        }
        .upload-button, .generate-button {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .upload-button img {
            height: 24px;
            margin-right: 10px;
        }
        .upload-button:hover {
            background-color: #218838;
        }
        .generate-button {
            background: #007bff;
        }
        .generate-button:hover {
            background-color: #0056b3;
        }
        .text-style-options {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        .text-style-options .icon {
            font-size: 24px;
            cursor: pointer;
            color: #007bff;
            transition: color 0.3s;
        }
        .text-style-options .icon.active {
            color: #0056b3;
        }
        #canvas-container {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 800px;
        }
        #meme-canvas {
            position: relative;
            width: 100%;
            max-width: 800px;
        }
        #image-preview {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Meme Generator</h1>
    
    <div class="container">
        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="uploadfile" id="file-input" style="display: none;">
            <button type="button" class="upload-button" id="upload-button">
                <img src="assets/images/upload-icon.png" alt="Upload Icon">
                Upload Your Image
            </button>
        </form>
        
        <!-- Selected Image and Canvas for Real-Time Preview -->
        <h2>Selected Image</h2>
        <div id="canvas-container">
            <canvas id="meme-canvas"></canvas>
        </div>

        <!-- Pre-Added Meme Images -->
        <h2>Pre-Added Memes</h2>
        <div class="meme-gallery">
            <?php foreach ($meme_images as $image): ?>
                <img src="<?php echo $image['image_path']; ?>" alt="<?php echo $image['image_name']; ?>" data-image="<?php echo $image['image_path']; ?>" class="meme-thumbnail" onclick="selectImage(this)">
            <?php endforeach; ?>
        </div>

        <!-- Add Text to Image -->
        <h2>Add Text to Image</h2>
        <form method="POST" action="generate_meme.php">
            <div class="form-group">
                <label for="font">Font</label>
                <select name="font" id="font">
                    <option value="Arial">Arial</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Courier New">Courier New</option>
                    <!-- Add more font options as needed -->
                </select>
            </div>
            <div class="form-group">
                <label for="font-size">Font Size</label>
                <input type="number" name="font-size" id="font-size" placeholder="Font Size (e.g., 20)" min="10" max="100" value="20">
            </div>
            <div class="form-group">
                <label for="font-color">Font Color</label>
                <input type="color" name="font-color" id="font-color" value="#000000">
                <div id="color-preview"></div>
            </div>
            <div class="form-group text-style-options">
                <i class="fas fa-bold icon" id="bold-icon" data-style="bold"></i>
                <i class="fas fa-italic icon" id="italic-icon" data-style="italic"></i>
                <i class="fas fa-underline icon" id="underline-icon" data-style="underline"></i>
            </div>
            <div class="form-group">
                <label for="toptext">Top Text</label>
                <input type="text" name="toptext" id="toptext" placeholder="Enter top text">
            </div>
            <div class="form-group">
                <label for="bottomtext">Bottom Text</label>
                <input type="text" name="bottomtext" id="bottomtext" placeholder="Enter bottom text">
            </div>
            <div class="form-group">
                <input type="submit" value="Generate Meme" class="generate-button">
            </div>
            <input type="hidden" name="secretimage" id="secretimage" value="<?php echo $_SESSION['selected_image']; ?>">
        </form>

        <canvas id="meme-canvas"></canvas>
    </div>

    <script>
       document.getElementById('upload-button').addEventListener('click', function() {
    document.getElementById('file-input').click();
});

const fileInput = document.getElementById('file-input');
fileInput.addEventListener('change', function(event) {
    const reader = new FileReader();
    reader.onload = function(event) {
        const imageSrc = event.target.result;
        const canvas = document.getElementById('meme-canvas');
        const ctx = canvas.getContext('2d');
        const image = new Image();
        image.src = imageSrc;
        image.onload = function() {
            canvas.width = image.width;
            canvas.height = image.height;
            ctx.drawImage(image, 0, 0);
            document.getElementById('secretimage').value = imageSrc;
            updateText(); // Update text on image change
        };
    };
    reader.readAsDataURL(fileInput.files[0]);
});

function selectImage(img) {
    const imageSrc = img.getAttribute('data-image');
    const canvas = document.getElementById('meme-canvas');
    const ctx = canvas.getContext('2d');
    const image = new Image();
    image.src = imageSrc;
    image.onload = function() {
        canvas.width = image.width;
        canvas.height = image.height;
        ctx.drawImage(image, 0, 0);
        document.getElementById('secretimage').value = imageSrc;
        updateText(); // Update text on image change
    };
}

const topTextInput = document.getElementById('toptext');
const bottomTextInput = document.getElementById('bottomtext');
const fontSelect = document.getElementById('font');
const fontSizeInput = document.getElementById('font-size');
const fontColorInput = document.getElementById('font-color');
const canvas = document.getElementById('meme-canvas');
const ctx = canvas.getContext('2d');

function updateText() {
    const image = new Image();
    image.src = document.getElementById('secretimage').value;
    image.onload = function() {
        canvas.width = image.width;
        canvas.height = image.height;
        ctx.drawImage(image, 0, 0);

        const fontSize = fontSizeInput.value + 'px';
        const font = fontSelect.value;
        const fontColor = fontColorInput.value;

        let fontStyle = '';

        // Check for active styling buttons
        if (boldIcon.classList.contains('active')) fontStyle += 'bold ';
        if (italicIcon.classList.contains('active')) fontStyle += 'italic ';

        ctx.font = `${fontStyle}${fontSize} ${font}`;
        ctx.fillStyle = fontColor;
        ctx.textAlign = 'center';

        // Draw top text
        if (topTextInput.value) {
            const topText = topTextInput.value;
            const topTextWidth = ctx.measureText(topText).width;
            ctx.fillText(topText, canvas.width / 2, 50);

            // Draw underline if needed for top text
            if (underlineIcon.classList.contains('active')) {
                ctx.beginPath();
                ctx.moveTo((canvas.width - topTextWidth) / 2, 52); // Adjust position to be below the text
                ctx.lineTo((canvas.width + topTextWidth) / 2, 52);
                ctx.strokeStyle = fontColor;
                ctx.lineWidth = 2; // Adjust the thickness of the underline here
                ctx.stroke();
            }
        }

        // Draw bottom text
        if (bottomTextInput.value) {
            const bottomText = bottomTextInput.value;
            const bottomTextWidth = ctx.measureText(bottomText).width;
            ctx.fillText(bottomText, canvas.width / 2, canvas.height - 20);

            // Draw underline if needed for bottom text
            if (underlineIcon.classList.contains('active')) {
                ctx.beginPath();
                ctx.moveTo((canvas.width - bottomTextWidth) / 2, canvas.height - 18); // Adjust position to be below the text
                ctx.lineTo((canvas.width + bottomTextWidth) / 2, canvas.height - 18);
                ctx.strokeStyle = fontColor;
                ctx.lineWidth = 2; // Adjust the thickness of the underline here
                ctx.stroke();
            }
        }
    };
}

topTextInput.addEventListener('input', updateText);
bottomTextInput.addEventListener('input', updateText);
fontSelect.addEventListener('change', updateText);
fontSizeInput.addEventListener('input', updateText);
fontColorInput.addEventListener('input', updateText);

const boldIcon = document.getElementById('bold-icon');
const italicIcon = document.getElementById('italic-icon');
const underlineIcon = document.getElementById('underline-icon');

function toggleTextStyle(icon, style) {
    icon.classList.toggle('active');
    updateText(); // Update the canvas after toggling style
}

boldIcon.addEventListener('click', () => toggleTextStyle(boldIcon, 'bold'));
italicIcon.addEventListener('click', () => toggleTextStyle(italicIcon, 'italic'));
underlineIcon.addEventListener('click', () => toggleTextStyle(underlineIcon, 'underline'));

    </script>
</body>
</html>
