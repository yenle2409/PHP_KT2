<?php
session_start();
include 'connect.php';

// ===== LẤY DANH SÁCH ẢNH ĐÃ LIKE TỪ SESSION =====
$userLikedImages = $_SESSION['liked_images'] ?? [];

$uploadMessage = "";
$messageType = "";
$images = [];

// Hiển thị thông báo từ session (nếu có)
if (isset($_SESSION['upload_message'])) {
    $uploadMessage = $_SESSION['upload_message'];
    $messageType = $_SESSION['message_type'] ?? '';
    // Xóa session message sau khi đã lấy
    unset($_SESSION['upload_message']);
    unset($_SESSION['message_type']);
}

// Lấy danh sách ảnh từ database
// ======== PHÂN TRANG ========
$limit = 6; // Số ảnh mỗi trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng ảnh
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM images");
$totalImages = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalImages / $limit);

// Lấy ảnh cho trang hiện tại
$stmt = $conn->prepare("SELECT * FROM images ORDER BY upload_date DESC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$images = $result->fetch_all(MYSQLI_ASSOC);

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
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Thể Hiện Phong Cách Của Bạn</h1>
        <p>Chia sẻ những khoảnh khắc thời trang của bạn với cộng đồng. Phong cách đường phố, lookbook, cảm hứng phối đồ và nhiều hơn nữa.</p>
    </section>

    <section id="upload" class="upload-section">
        <h2><i class="fas fa-cloud-upload-alt"></i> Tải Lên Phong Cách Của Bạn</h2>

        <form method="POST" action="upload.php" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <div class="file-input-wrapper" id="fileDropArea">
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required id="fileInput">
                <label for="fileInput" class="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Chọn ảnh thời trang của bạn</span>
                    <small>Kéo thả file vào đây hoặc click để chọn</small>
                </label>
            </div>

            <div id="previewContainer" class="text-center mt-4" style="display: none;">
                <h5 class="mb-3">Xem trước ảnh</h5>
                <div class="crop-container mx-auto border rounded shadow-sm p-3 bg-light" style="max-width: 420px;border:2px solid black;">
                    <img id="previewImage" style="max-width: 100%; border-radius: 10px;">
                </div>
                <div class="mt-4 d-flex justify-content-center gap-3">
                    <button id="cropButton" class="btn btn-primary px-4">Cắt ảnh</button>
                    <button id="cancelButton" class="btn btn-secondary px-4" style="display: none;">Hủy</button>
                </div>
            </div>

            <div id="croppedPreviewContainer" class="container text-center mt-4" style="display: none;">
                <h5 class="mb-3">Xem trước ảnh</h5>
                <img id="croppedPreview" style="max-width: 300px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.15);">
            </div>

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
            
            <input type="hidden" name="crop_x" id="crop_x">
            <input type="hidden" name="crop_y" id="crop_y">
            <input type="hidden" name="crop_w" id="crop_w">
            <input type="hidden" name="crop_h" id="crop_h">

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
                            
                            <?php
                                // Kiểm tra xem user đã like ảnh này trong session chưa
                                $isLiked = isset($userLikedImages[$image['id']]);
                                $heartIcon = $isLiked ? 'fas fa-heart' : 'far fa-heart'; // fas = đặc, far = rỗng
                                $likedClass = $isLiked ? 'liked' : ''; // Thêm class 'liked' nếu đã like
                            ?>
                            <a href="like.php?id=<?= $image['id'] ?>" 
                               class="like-btn <?= $likedClass ?>" 
                               data-id="<?= $image['id'] ?>"> <i class="<?= $heartIcon ?>"></i> <span><?= $image['likes'] ?></span> </a>
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
    
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>#gallery" class="page-link">« Trước</a>
            <?php endif; ?>

            <?php
            $range = 2; // số trang hiển thị hai bên
            for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++):
            ?>
                <a href="?page=<?= $i ?>#gallery" class="page-link <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>#gallery" class="page-link">Tiếp »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="viewerOverlay" class="viewer-overlay">
        <span class="viewer-close">&times;</span>
        <img class="viewer-img" id="viewerImg">
        <div id="viewerCaption"></div>
    </div>
    <script>
        const overlayEl = document.getElementById("viewerOverlay");
        const overlayImg = document.getElementById("viewerImg");
        const overlayCaption = document.getElementById("viewerCaption");
        const closeBtnEl = document.querySelector(".viewer-close");

        // Gán sự kiện click cho từng ảnh
        document.querySelectorAll(".gallery-image").forEach(pic => {
        pic.addEventListener("click", () => {
            overlayEl.style.display = "flex";
            overlayImg.src = pic.src;
            overlayCaption.textContent = pic.alt || "";
        });
        });

        // Đóng popup
        closeBtnEl.addEventListener("click", () => {
        overlayEl.style.display = "none";
        });

        // Đóng khi click ngoài ảnh
        overlayEl.addEventListener("click", (e) => {
        if (e.target === overlayEl) overlayEl.style.display = "none";
        });
    </script>

    <footer class="site-footer">
        <p><b>FashionGallery</b> — Nơi phong cách gặp gỡ cộng đồng</p>
        <p class="footer-heart">
            <i class="fas fa-heart"></i> Được tạo ra với niềm đam mê thời trang
        </p>
    </footer>

    <div class="alert-modal" id="alertModal">
        <div class="modal-content">
            <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
            <div class="modal-icon" id="modalIcon"><i class="fas fa-exclamation-triangle"></i></div>
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
    
    // Set icon and style based on type
    if (type === 'error') {
        modalIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        modalIcon.className = 'modal-icon error';
        modalTitle.textContent = title;
    } else if (type === 'success') {
        modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        modalIcon.className = 'modal-icon success';
        modalTitle.textContent = title;
    }
    
    modalMessage.textContent = message;
    modal.classList.add('show');
    
    const closeModal = () => {
        modal.classList.remove('show');
        // If it's a success message, reload the page to show the new image
        if (type === 'success') {
            // Chuyển hướng về trang 1 để xem ảnh mới nhất
            setTimeout(() => {
                window.location.href = 'index.php?page=1#gallery';
            }, 300);
        }
    };
    
    modalButton.onclick = closeModal;
    document.getElementById('modalClose').onclick = closeModal;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') { 
            closeModal(); 
            document.removeEventListener('keydown', closeOnEscape); 
        }
    });
}

// ===== VALIDATION FUNCTIONS =====
function validateImageFormat(file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const maxSize = 2 * 1024 * 1024;
    
    if (!allowedTypes.includes(file.type)) {
        showAlert('❌ Định Dạng Không Hợp Lệ', 'Chỉ chấp nhận các định dạng ảnh:\n JPG/JPEG, PNG, GIF. \n Vui lòng chọn file ảnh khác!', 'error');
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
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
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

// ===== CROP ẢNH =====
cropButton.addEventListener('click', (e) => {
    e.preventDefault();
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({ maxWidth: 1000, maxHeight: 1000,fillColor: 'transparent'});
    croppedPreview.src = canvas.toDataURL("image/png");

    croppedPreviewContainer.style.display = 'block';
    previewContainer.style.display = 'none';

    // Lưu dữ liệu crop vào input ẩn
    const cropData = cropper.getData();
    document.getElementById('crop_x').value = Math.round(cropData.x);
    document.getElementById('crop_y').value = Math.round(cropData.y);
    document.getElementById('crop_w').value = Math.round(cropData.width);
    document.getElementById('crop_h').value = Math.round(cropData.height);
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
    
    // Check if there's a success message to show
    <?php if ($uploadMessage && $messageType === 'success'): ?>
        setTimeout(() => {
            showAlert('✅ Thành Công', '<?= addslashes($uploadMessage) ?>', 'success');
        }, 500);
    <?php elseif ($uploadMessage && $messageType === 'error'): ?>
        setTimeout(() => {
            showAlert('❌ Lỗi Xảy Ra', '<?= addslashes($uploadMessage) ?>', 'error');
        }, 500);
    <?php endif; ?>
    
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
        // Cập nhật giá trị crop lần cuối trước khi submit
        if (cropper) {
            const cropData = cropper.getData();
            document.getElementById('crop_x').value = Math.round(cropData.x);
            document.getElementById('crop_y').value = Math.round(cropData.y);
            document.getElementById('crop_w').value = Math.round(cropData.width);
            document.getElementById('crop_h').value = Math.round(cropData.height);
        }

        btnText.textContent = 'Đang tải lên...';
        submitBtn.disabled = true;
        submitBtn.querySelector('i').className = 'fas fa-spinner fa-spin';
    });

    // ===========================================
    // ===== BẮT ĐẦU CODE SỬA LỖI LIKE AJAX =====
    // ===========================================
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // <-- QUAN TRỌNG: Ngăn chặn trang reload!

            const link = this;
            const imageId = link.dataset.id;
            const icon = link.querySelector('i');
            const countSpan = link.querySelector('span');

            // Vô hiệu hóa tạm thời để tránh click đúp
            link.style.pointerEvents = 'none';

            fetch('like.php?id=' + imageId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Giúp xác định đây là AJAX request
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Lỗi mạng: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Cập nhật số like
                    countSpan.textContent = data.newCount;

                    // Cập nhật icon tim (đặc/rỗng)
                    if (data.liked) {
                        icon.className = 'fas fa-heart'; // Tim đặc
                        link.classList.add('liked');
                    } else {
                        icon.className = 'far fa-heart'; // Tim rỗng
                        link.classList.remove('liked');
                    }
                } else {
                    // Hiển thị lỗi nếu có
                    showAlert('Lỗi Like', data.message || 'Không thể like ảnh', 'error');
                }
            })
            .catch(error => {
                console.error('Lỗi khi thực hiện like:', error);
                showAlert('Lỗi', 'Đã xảy ra lỗi. Vui lòng thử lại.', 'error');
            })
            .finally(() => {
                // Kích hoạt lại nút sau khi hoàn tất
                link.style.pointerEvents = 'auto';
            });
        });
    });
    // =========================================
    // ===== KẾT THÚC CODE SỬA LỖI LIKE AJAX =====
    // =========================================

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