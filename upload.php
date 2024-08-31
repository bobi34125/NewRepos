<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadfile'])) {
    $upload_dir = 'uploads/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            die("Failed to create upload directory.");
        }
    }

    if ($_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['uploadfile']['tmp_name'];
        $file_name = basename($_FILES['uploadfile']['name']);
        $upload_file = $upload_dir . $file_name;

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            echo "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
            exit;
        }

        if (move_uploaded_file($file_tmp, $upload_file)) {
            $_SESSION['selected_image'] = $upload_file;
            header('Location: index.php');
            exit;
        } else {
            echo "Failed to move uploaded file.";
        }
    } else {
        echo "Upload error: " . $_FILES['uploadfile']['error'];
    }
}
?>
