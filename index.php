<?php
include 'connect.php';

$uploadMessage = "";
$images = [];

// Lấy danh sách ảnh từ database
$result = $conn->query("SELECT * FROM images ORDER BY upload_date DESC");
if ($result) {
    $images = $result->fetch_all(MYSQLI_ASSOC);
}

// Xử lý upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];
    $desc = $_POST["desc"] ?? "";
    $category = $_POST["category"] ?? "Other";
    $quality = (int)($_POST["quality"] ?? 80);

    $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $uploadMessage = "⚠️ Lỗi upload file!";
    } elseif ($file["size"] > 2 * 1024 * 1024) {
        $uploadMessage = "⚠️ Kích thước ảnh vượt quá 2MB!";
    } elseif (!in_array($file["type"], $allowedTypes)) {
        $uploadMessage = "⚠️ Chỉ chấp nhận định dạng JPG, PNG hoặc GIF!";
    } else {
        include 'compress.php';
        include 'resize.php';
        
        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetPath = "uploads/" . $fileName;
        $tempPath = $file["tmp_name"];

        // Resize trước, sau đó compress
        if (resizeImage($tempPath, $targetPath, 800, 800)) {
            compressImage($targetPath, $targetPath, $quality);
            
            // Lưu vào database
            $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
            $stmt->bind_param('sss', $fileName, $desc, $category);
            
            if ($stmt->execute()) {
                $uploadMessage = "✅ Tải ảnh thành công!";
                // Refresh để hiển thị ảnh mới
                echo "<script>setTimeout(() => window.location.href = 'index.php', 1000);</script>";
            } else {
                $uploadMessage = "⚠️ Lỗi lưu thông tin ảnh!";
            }
        } else {
            $uploadMessage = "⚠️ Lỗi xử lý ảnh!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>fashionGallery — Share Your Fashion Moments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <div class="logo">fashionGallery</div>
            <nav class="nav-links">
                <a href="#gallery">Gallery</a>
                <a href="#upload">Upload</a>
                <a href="#trending">Trending</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Show Your Style</h1>
        <p>Share your fashion moments with the world. Street style, lookbooks, outfit inspiration and more.</p>
    </section>

    <section id="upload" class="upload-section">
        <h2><i class="fas fa-cloud-upload-alt"></i> Upload Your Look</h2>
        
        <?php if ($uploadMessage): ?>
            <div class="message <?= strpos($uploadMessage, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($uploadMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="file-input-wrapper">
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required id="fileInput">
                <label for="fileInput" class="file-input-label">
                    <i class="fas fa-images"></i>
                    <span>Choose your fashion image</span>
                    <small>Max 2MB • JPG, PNG, GIF</small>
                </label>
            </div>

            <div class="form-group">
                <label for="desc">Style Description</label>
                <input type="text" name="desc" id="desc" placeholder="e.g., Summer denim look with vintage sneakers" value="<?= htmlspecialchars($_POST['desc'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="category">Fashion Category</label>
                <select name="category" id="category">
                    <option value="Street" <?= ($_POST['category'] ?? '') === 'Street' ? 'selected' : '' ?>>Street Style</option>
                    <option value="Formal" <?= ($_POST['category'] ?? '') === 'Formal' ? 'selected' : '' ?>>Formal Wear</option>
                    <option value="Vintage" <?= ($_POST['category'] ?? '') === 'Vintage' ? 'selected' : '' ?>>Vintage</option>
                    <option value="Casual" <?= ($_POST['category'] ?? '') === 'Casual' ? 'selected' : '' ?>>Casual</option>
                    <option value="High Fashion" <?= ($_POST['category'] ?? '') === 'High Fashion' ? 'selected' : '' ?>>High Fashion</option>
                    <option value="Minimalist" <?= ($_POST['category'] ?? '') === 'Minimalist' ? 'selected' : '' ?>>Minimalist</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality">Image Quality (50-100)</label>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <input type="range" name="quality" id="quality" min="50" max="100" value="<?= htmlspecialchars($_POST['quality'] ?? '80') ?>" 
                           oninput="document.getElementById('qualityValue').textContent = this.value">
                    <output id="qualityValue" style="background: var(--accent); color: white; padding: 0.25rem 0.5rem; border-radius: 5px; min-width: 40px; text-align: center;"><?= htmlspecialchars($_POST['quality'] ?? '80') ?></output>%
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-upload"></i> Upload Look
            </button>
        </form>
    </section>

    <main id="gallery" class="gallery">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <div class="gallery-item">
                    <img src="uploads/<?= htmlspecialchars($image['filename']) ?>" 
                         alt="<?= htmlspecialchars($image['caption']) ?>" 
                         class="gallery-image"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMWExYTFhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iI2ZmZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4='">
                    <div class="item-meta">
                        <div class="item-caption"><?= htmlspecialchars($image['caption'] ?: 'Fashion Look') ?></div>
                        <div class="item-category">#<?= htmlspecialchars($image['category']) ?></div>
                        <div class="item-stats">
                            <span><i class="far fa-clock"></i> <?= date('M j, Y', strtotime($image['upload_date'])) ?></span>
                            <a href="like.php?id=<?= $image['id'] ?>" class="like-btn">
                                <i class="far fa-heart"></i> <?= $image['likes'] ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: var(--muted);">
                <i class="fas fa-camera" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3 style="margin-bottom: 0.5rem;">No fashion looks yet</h3>
                <p>Be the first to share your style!</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>© 2024 <b>fashionGallery</b> — Where style meets community</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.7;">
            <i class="fas fa-heart" style="color: var(--accent);"></i> 
            Made with passion for fashion
        </p>
    </footer>

    <script>
    // File input preview
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const label = this.nextElementSibling;
        const fileName = this.files[0]?.name || 'Choose your fashion image';
        label.querySelector('span').textContent = fileName;
        
        // Thêm hiệu ứng khi có file được chọn
        if (this.files.length > 0) {
            label.style.borderColor = 'var(--accent)';
            label.style.background = 'rgba(255, 51, 102, 0.1)';
        }
    });

    // Smooth scroll cho navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Thêm hiệu ứng loading khi submit form
    document.querySelector('form').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        btn.disabled = true;
    });
    </script>
</body>
</html>