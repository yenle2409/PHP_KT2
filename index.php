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
    <!-- ===== HEADER SECTION ===== -->
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

    <!-- ===== HERO SECTION ===== -->
    <section class="hero">
        <h1>Thể Hiện Phong Cách Của Bạn</h1>
        <p>Chia sẻ những khoảnh khắc thời trang của bạn với cộng đồng. Phong cách đường phố, lookbook, cảm hứng phối đồ và nhiều hơn nữa.</p>
    </section>

    <!-- ===== UPLOAD SECTION ===== -->
    <section id="upload" class="upload-section">
        <h2><i class="fas fa-cloud-upload-alt"></i> Tải Lên Phong Cách Của Bạn</h2>
        
        <?php if ($uploadMessage): ?>
            <div class="message <?= strpos($uploadMessage, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($uploadMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <!-- FILE UPLOAD AREA -->
            <div class="file-input-wrapper" id="fileDropArea">
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required id="fileInput">
                <label for="fileInput" class="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Chọn ảnh thời trang của bạn</span>
                    <small>Kéo thả file vào đây hoặc click để chọn</small>
                </label>
            </div>

            <!-- IMAGE PREVIEW & CROP AREA -->
            <div id="previewContainer" class="text-center mt-4" style="display: none;">
                <h5 class="mb-3">Xem trước ảnh</h5>
                <div class="crop-container mx-auto border rounded shadow-sm p-3 bg-light" style="max-width: 420px;">
                    <img id="previewImage" style="max-width: 100%; border-radius: 10px;">
                </div>
                <div class="mt-4 d-flex justify-content-center gap-3">
                    <button id="cropButton" class="btn btn-primary px-4">Cắt ảnh</button>
                    <button id="cancelButton" class="btn btn-secondary px-4" style="display: none;">Hủy</button>
                </div>
            </div>

            <!-- CROPPED PREVIEW -->
            <div id="croppedPreviewContainer" class="container text-center mt-4" style="display: none;">
                <h5 class="mb-3">Xem trước ảnh</h5>
                <img id="croppedPreview" style="max-width: 300px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.15);">
            </div>

            <!-- FORM FIELDS -->
            <div class="form-group">
                <label for="desc"><i class="fas fa-pen"></i> Mô Tả Phong Cách</label>
                <input type="text" name="desc" id="desc" 
                       placeholder="Ví dụ: Phong cách denim mùa hè với giày sneaker cổ điển" 
                       value="<?= htmlspecialchars($_POST['desc'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="category"><i class="fas fa-tag"></i> Danh Mục Thời Trang</label>
                <select name="category" id="category">
                    <option value="Đường phố" <?= ($_POST['category'] ?? '') === 'Đường phố' ? 'selected' : '' ?>>👕 Phong Cách Đường Phố</option>
                    <option value="Công sở" <?= ($_POST['category'] ?? '') === 'Công sở' ? 'selected' : '' ?>>💼 Thời Trang Công Sở</option>
                    <option value="Cổ điển" <?= ($_POST['category'] ?? '') === 'Cổ điển' ? 'selected' : '' ?>>🎩 Phong Cách Cổ Điển</option>
                    <option value="Thường ngày" <?= ($_POST['category'] ?? '') === 'Thường ngày' ? 'selected' : '' ?>>👚 Thời Trang Thường Ngày</option>
                    <option value="Cao cấp" <?= ($_POST['category'] ?? '') === 'Cao cấp' ? 'selected' : '' ?>>✨ Thời Trang Cao Cấp</option>
                    <option value="Tối giản" <?= ($_POST['category'] ?? '') === 'Tối giản' ? 'selected' : '' ?>>⚫ Phong Cách Tối Giản</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality"><i class="fas fa-cog"></i> Chất Lượng Ảnh</label>
                <div class="quality-slider-container">
                    <input type="range" name="quality" id="quality" min="50" max="100" 
                           value="<?= htmlspecialchars($_POST['quality'] ?? '80') ?>" 
                           class="quality-slider"
                           oninput="updateQualityValue(this.value)">
                    <output id="qualityValue" class="quality-value">
                        <?= htmlspecialchars($_POST['quality'] ?? '80') ?>%
                    </output>
                </div>
                <div class="quality-labels">
                    <span>Kích thước nhỏ</span>
                    <span>Chất lượng tốt</span>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-upload"></i> 
                <span id="btnText">Tải Lên Ngay</span>
            </button>
        </form>
    </section>

    <!-- ===== GALLERY SECTION ===== -->
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
            <div class="empty-gallery">
                <i class="fas fa-camera"></i>
                <h3>Chưa có ảnh nào</h3>
                <p>Hãy là người đầu tiên chia sẻ phong cách của bạn!</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- ===== FOOTER SECTION ===== -->
    <footer class="site-footer">
        <p><b>FashionGallery</b> — Nơi phong cách gặp gỡ cộng đồng</p>
        <p class="footer-heart">
            <i class="fas fa-heart"></i> Được tạo ra với niềm đam mê thời trang
        </p>
    </footer>

    <!-- ===== ALERT MODAL ===== -->
    <div class="alert-modal" id="alertModal">
        <div class="modal-content">
            <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
            <div class="modal-icon error" id="modalIcon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="modal-title" id="modalTitle">Thông báo</h3>
            <p class="modal-message" id="modalMessage">Nội dung thông báo</p>
            <button class="modal-button" id="modalButton">OK</button>
        </div>
    </div>

    <script>
// ===== GLOBAL VARIABLES =====
let cropper;
const fileInput = document.getElementById('fileInput');
const fileDropArea = document.getElementById('fileDropArea');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const cropButton = document.getElementById('cropButton');
const croppedPreviewContainer = document.getElementById('croppedPreviewContainer');
const croppedPreview = document.getElementById('croppedPreview');
const uploadForm = document.getElementById('uploadForm');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');

// ===== MODAL FUNCTIONS =====
function showAlert(title, message, type = 'error') {
    const modal = document.getElementById('alertModal');
    const modalIcon = document.getElementById('modalIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalButton = document.getElementById('modalButton');
    
    modalIcon.innerHTML = type === 'error' ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-check-circle"></i>';
    modalIcon.className = `modal-icon ${type}`;
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modal.classList.add('show');
    
    const closeModal = () => modal.classList.remove('show');
    modalButton.onclick = closeModal;
    document.getElementById('modalClose').onclick = closeModal;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', closeOnEscape); }
    });
}

// ===== VALIDATION FUNCTIONS =====
function validateImageFormat(file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const maxSize = 2 * 1024 * 1024;
    
    if (!allowedTypes.includes(file.type)) {
        showAlert('❌ Định Dạng Không Hợp Lệ', 'Chỉ chấp nhận các định dạng ảnh:\n• JPG/JPEG\n• PNG\n• GIF\n\nVui lòng chọn file ảnh khác!', 'error');
        return false;
    }
    
    if (file.size > maxSize) {
        showAlert('📏 Kích Thước Quá Lớn', `File ảnh của bạn (${(file.size / 1024 / 1024).toFixed(1)}MB) vượt quá giới hạn cho phép!\n\nGiới hạn tối đa: 2MB\nVui lòng chọn ảnh nhỏ hơn.`, 'error');
        return false;
    }
    
    return true;
}

function resetFileInput() {
    fileInput.value = '';
    fileDropArea.querySelector('span').textContent = 'Chọn ảnh thời trang của bạn';
    fileDropArea.style.borderColor = 'rgba(255, 107, 149, 0.4)';
    fileDropArea.style.background = 'var(--gradient-soft)';
    previewContainer.style.display = 'none';
    croppedPreviewContainer.style.display = 'none';
}

function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0];
        
        if (!validateImageFormat(file)) {
            resetFileInput();
            return;
        }
        
        fileDropArea.querySelector('span').textContent = file.name;
        fileDropArea.style.borderColor = 'var(--accent)';
        fileDropArea.style.background = 'rgba(231, 185, 176, 0.1)';
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                croppedPreviewContainer.style.display = 'none';
                
                if (cropper) cropper.destroy();
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
            }
            reader.readAsDataURL(file);
        }
    }
}

// ===== CROP FUNCTIONALITY =====
cropButton.addEventListener('click', (e) => {
    e.preventDefault();
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({ maxWidth: 1000, maxHeight: 1000 });
    croppedPreview.src = canvas.toDataURL("image/jpeg", 0.9);
    croppedPreviewContainer.style.display = 'block';
    previewContainer.style.display = 'none';

    const cropData = cropper.getData();
    document.querySelector('form').insertAdjacentHTML('beforeend', `
        <input type="hidden" name="crop_x" value="${Math.round(cropData.x)}">
        <input type="hidden" name="crop_y" value="${Math.round(cropData.y)}">
        <input type="hidden" name="crop_w" value="${Math.round(cropData.width)}">
        <input type="hidden" name="crop_h" value="${Math.round(cropData.height)}">
    `);
});

// ===== UTILITY FUNCTIONS =====
function updateQualityValue(value) {
    const qualityValue = document.getElementById('qualityValue');
    qualityValue.textContent = value + '%';
    
    if (value >= 90) {
        qualityValue.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
    } else if (value >= 70) {
        qualityValue.style.background = 'linear-gradient(135deg, #FF9800, #F57C00)';
    } else {
        qualityValue.style.background = 'var(--gradient)';
    }
}

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', function() {
    updateQualityValue(document.getElementById('quality').value);
    
    // Drag & Drop Events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
    });
    
    fileDropArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        fileInput.files = files;
        handleFiles(files);
    });
    
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    uploadForm.addEventListener('submit', function() {
        btnText.textContent = 'Đang tải lên...';
        submitBtn.disabled = true;
        submitBtn.querySelector('i').className = 'fas fa-spinner fa-spin';
    });
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
    </script>
</body>
</html>