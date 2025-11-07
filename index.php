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
    $category = $_POST["category"] ?? "Khác";
    $quality = (int)($_POST["quality"] ?? 80);

    $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
 
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $uploadMessage = "⚠️ Lỗi upload file!";
    } elseif ($file["size"] > 2 * 1024 * 1024) {
        $uploadMessage = "⚠️ Kích thước ảnh vượt quá 2MB!";
    } elseif (!in_array($file["type"], $allowedTypes)) {
        $uploadMessage = "⚠️ Chỉ chấp nhận định dạng JPG, PNG hoặc GIF!";
    } else {
        // Kiểm tra GD extension
        $hasGD = extension_loaded('gd');
        
        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetPath = "uploads/" . $fileName;
        $tempPath = $file["tmp_name"];

        if (!$hasGD) {
            // Nếu không có GD, chỉ copy file
            if (move_uploaded_file($tempPath, $targetPath)) {
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $uploadMessage = "✅ Tải ảnh thành công!";
                    echo "<script>setTimeout(() => window.location.href = 'index.php', 1000);</script>";
                } else {
                    $uploadMessage = "⚠️ Lỗi lưu thông tin ảnh!";
                }
            } else {
                $uploadMessage = "⚠️ Lỗi di chuyển file!";
            }
        } else {
            // Có GD, xử lý resize và compress
            include 'compress.php';
            include 'resize.php';
            $crop = [
                'x' => (int)($_POST['crop_x'] ?? 0),
                'y' => (int)($_POST['crop_y'] ?? 0),
                'w' => (int)($_POST['crop_w'] ?? 0),
                'h' => (int)($_POST['crop_h'] ?? 0)
            ];
            if (resizeImage($tempPath, $targetPath, 800, 800, $crop)) {
                compressImage($targetPath, $targetPath, $quality);
                
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $uploadMessage = "✅ Tải ảnh thành công!";
                    echo "<script>setTimeout(() => window.location.href = 'index.php', 1000);</script>";
                } else {
                    $uploadMessage = "⚠️ Lỗi lưu thông tin ảnh!";
                }
            } else {
                $uploadMessage = "⚠️ Lỗi xử lý ảnh!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashionGallery — Chia Sẻ Phong Cách Thời Trang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <div class="logo">FashionGallery</div>
            <nav class="nav-links">
                <a href="#gallery">Thư Viện</a>
                <a href="#upload">Tải Lên</a>
                <a href="#trending">Xu Hướng</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Thể Hiện Phong Cách Của Bạn</h1>
        <p>Chia sẻ những khoảnh khắc thời trang của bạn với cộng đồng. Phong cách đường phố, lookbook, cảm hứng phối đồ và nhiều hơn nữa.</p>
    </section>

<section id="upload" class="upload-section">
    <h2><i class="fas fa-cloud-upload-alt"></i> Tải Lên Phong Cách Của Bạn</h2>
    
    <?php if ($uploadMessage): ?>
        <div class="message <?= strpos($uploadMessage, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($uploadMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
        <!-- IMPROVED FILE UPLOAD -->
        <div class="file-input-wrapper" id="fileDropArea">
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required id="fileInput">
            <label for="fileInput" class="file-input-label">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Chọn ảnh thời trang của bạn</span>
                <small>Kéo thả file vào đây hoặc click để chọn</small>
            </label>
        </div>

        <!-- IMAGE PREVIEW -->
        <div id="previewContainer" class="text-center mt-4" style="display: none;">
            <h5 class="mb-3">Xem trước ảnh</h5>

            <!-- Vùng crop -->
            <div class="crop-container mx-auto border rounded shadow-sm p-3 bg-light" style="max-width: 420px;">
                <img id="previewImage" style="max-width: 100%; border-radius: 10px;">
            </div>

            <!-- Nút thao tác -->
            <div class="mt-4 d-flex justify-content-center gap-3">
                <button id="cropButton" class="btn btn-primary px-4">
                    Cắt ảnh
                </button>
                <button id="cancelButton" class="btn btn-secondary px-4" style="display: none;">
                    Hủy
                </button>
            </div>
        </div>

        <!-- Khu vực hiển thị sau khi cắt -->
        <div id="croppedPreviewContainer" class="container text-center mt-4" style="display: none;">
            <h5 class="mb-3">Xem trước ảnh</h5>
            <img id="croppedPreview" style="max-width: 300px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.15);">
        </div>
        <!-- IMPROVED FORM GROUPS -->
        <div class="form-group">
            <label for="desc">
                <i class="fas fa-pen"></i> Mô Tả Phong Cách
            </label>
            <input type="text" name="desc" id="desc" 
                   placeholder="Ví dụ: Phong cách denim mùa hè với giày sneaker cổ điển" 
                   value="<?= htmlspecialchars($_POST['desc'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="category">
                <i class="fas fa-tag"></i> Danh Mục Thời Trang
            </label>
            <select name="category" id="category">
                <option value="Đường phố" <?= ($_POST['category'] ?? '') === 'Đường phố' ? 'selected' : '' ?>>👕 Phong Cách Đường Phố</option>
                <option value="Công sở" <?= ($_POST['category'] ?? '') === 'Công sở' ? 'selected' : '' ?>>💼 Thời Trang Công Sở</option>
                <option value="Cổ điển" <?= ($_POST['category'] ?? '') === 'Cổ điển' ? 'selected' : '' ?>>🎩 Phong Cách Cổ Điển</option>
                <option value="Thường ngày" <?= ($_POST['category'] ?? '') === 'Thường ngày' ? 'selected' : '' ?>>👚 Thời Trang Thường Ngày</option>
                <option value="Cao cấp" <?= ($_POST['category'] ?? '') === 'Cao cấp' ? 'selected' : '' ?>>✨ Thời Trang Cao Cấp</option>
                <option value="Tối giản" <?= ($_POST['category'] ?? '') === 'Tối giản' ? 'selected' : '' ?>>⚫ Phong Cách Tối Giản</option>
            </select>
        </div>

        <!-- IMPROVED QUALITY SLIDER -->
        <div class="form-group">
            <label for="quality">
                <i class="fas fa-cog"></i> Chất Lượng Ảnh
                <small style="font-weight: normal; color: var(--muted);">(50-100)</small>
            </label>
            <div class="quality-slider-container">
                <input type="range" name="quality" id="quality" min="50" max="100" 
                       value="<?= htmlspecialchars($_POST['quality'] ?? '80') ?>" 
                       class="quality-slider"
                       oninput="updateQualityValue(this.value)">
                <output id="qualityValue" class="quality-value">
                    <?= htmlspecialchars($_POST['quality'] ?? '80') ?>%
                </output>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--muted); margin-top: 0.5rem;">
                <span>Kích thước nhỏ</span>
                <span>Chất lượng tốt</span>
            </div>
        </div>

        <!-- IMPROVED SUBMIT BUTTON -->
        <button type="submit" class="btn-primary" id="submitBtn">
            <i class="fas fa-upload"></i> 
            <span id="btnText">Tải Lên Ngay</span>
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
                        <div class="item-caption"><?= htmlspecialchars($image['caption'] ?: 'Phong cách thời trang') ?></div>
                        <div class="item-category">#<?= htmlspecialchars($image['category']) ?></div>
                        <div class="item-stats">
                            <span><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($image['upload_date'])) ?></span>
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
                <h3 style="margin-bottom: 0.5rem;">Chưa có ảnh nào</h3>
                <p>Hãy là người đầu tiên chia sẻ phong cách của bạn!</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p> <b>FashionGallery</b> — Nơi phong cách gặp gỡ cộng đồng</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.7;">
            <i class="fas fa-heart" style="color: var(--accent);"></i> 
            Được tạo ra với niềm đam mê thời trang
        </p>
    </footer>

    <script>
// CẢI THIỆN UPLOAD EXPERIENCE
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const fileDropArea = document.getElementById('fileDropArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    
    // Hiển thị chất lượng ban đầu
    updateQualityValue(document.getElementById('quality').value);
    
    // DRAG & DROP FUNCTIONALITY
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        fileDropArea.classList.add('dragover');
    }
    
    function unhighlight() {
        fileDropArea.classList.remove('dragover');
    }
    
    // Handle dropped files
    fileDropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFiles(files);
    }
    
    // Handle file selection
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            const fileName = file.name;
            
            // Update label
            fileDropArea.querySelector('span').textContent = fileName;
            fileDropArea.style.borderColor = 'var(--accent)';
            fileDropArea.style.background = 'rgba(231, 185, 176, 0.1)';
            
            // Show preview
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Form submission
    uploadForm.addEventListener('submit', function() {
        btnText.textContent = 'Đang tải lên...';
        submitBtn.disabled = true;
        submitBtn.querySelector('i').className = 'fas fa-spinner fa-spin';
    });
});

// Update quality value display
function updateQualityValue(value) {
    const qualityValue = document.getElementById('qualityValue');
    qualityValue.textContent = value + '%';
    
    // Change color based on value
    if (value >= 90) {
        qualityValue.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
    } else if (value >= 70) {
        qualityValue.style.background = 'linear-gradient(135deg, #FF9800, #F57C00)';
    } else {
        qualityValue.style.background = 'var(--gradient)';
    }
}

// Smooth scroll for navigation
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
let cropper;
const imageInput = document.getElementById('fileInput');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const cropButton = document.getElementById('cropButton');
const cancelButton = document.getElementById('cancelButton');
const croppedPreviewContainer = document.getElementById('croppedPreviewContainer');
const croppedPreview = document.getElementById('croppedPreview');

// Khi chọn file ảnh
imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert("Vui lòng chọn file ảnh hợp lệ!");
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        previewImage.src = reader.result;
        previewContainer.style.display = 'block';
        croppedPreviewContainer.style.display = 'none';

        // Hủy cropper cũ nếu có
        if (cropper) cropper.destroy();

        // Cho phép cắt ảnh tự do (aspectRatio: NaN)
        cropper = new Cropper(previewImage, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 0.9,
            background: false,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
        });
    };
    reader.readAsDataURL(file);
});

// Khi bấm "Cắt ảnh"
cropButton.addEventListener('click', (e) => {
    e.preventDefault();
    if (!cropper) return;

    // Không giới hạn kích thước crop (dựa theo vùng người dùng chọn)
    const canvas = cropper.getCroppedCanvas({
        maxWidth: 1000,
        maxHeight: 1000,
    });

    // Hiển thị ảnh sau khi cắt
    croppedPreview.src = canvas.toDataURL("image/jpeg", 0.9);
    croppedPreviewContainer.style.display = 'block';
    previewContainer.style.display = 'none'; // Ẩn vùng preview ban đầu

    // Lưu dữ liệu crop (để gửi PHP xử lý)
    const cropData = cropper.getData();
    document.querySelector('form').insertAdjacentHTML('beforeend', `
        <input type="hidden" name="crop_x" value="${Math.round(cropData.x)}">
        <input type="hidden" name="crop_y" value="${Math.round(cropData.y)}">
        <input type="hidden" name="crop_w" value="${Math.round(cropData.width)}">
        <input type="hidden" name="crop_h" value="${Math.round(cropData.height)}">
    `);
});

</script>
</body>
</html>