<?php
session_start();
require_once '../includes/config.php';

// Function to safely display text while preserving certain characters
function display_text($text) {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

// Check and fix the categories table structure
$tableExists = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=0");

if (!$tableExists) {
    // Create the table if it doesn't exist
    $sql = "CREATE TABLE `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `icon` varchar(50) NOT NULL,
        `status` enum('active','inactive') DEFAULT 'active',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
} else {
    // Fix the table structure if it exists
    $conn->query("ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    $conn->query("ALTER TABLE `categories` MODIFY `name` varchar(100) NOT NULL");
    $conn->query("ALTER TABLE `categories` MODIFY `icon` varchar(50) NOT NULL");
    $conn->query("ALTER TABLE `categories` MODIFY `status` enum('active','inactive') DEFAULT 'active'");
    $conn->query("ALTER TABLE `categories` MODIFY `created_at` timestamp DEFAULT CURRENT_TIMESTAMP");
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Handle form submissions first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $redirect = false;
        
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $icon = trim($_POST['icon']);
                
                // Validate input
                if (empty($name) || empty($icon)) {
                    $_SESSION['error_message'] = "Name and icon are required";
                } else {
                    // Check if category name already exists
                    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                    $stmt->bind_param("s", $name);
                    $stmt->execute();
                    if($stmt->get_result()->num_rows > 0) {
                        $_SESSION['error_message'] = "A category with this name already exists";
                    } else {
                    $stmt = $conn->prepare("INSERT INTO `categories` (`name`, `icon`) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $icon);
                    
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Category added successfully";
                } else {
                        $_SESSION['error_message'] = "Error adding category: " . $conn->error;
                        }
                    }
                }
                $redirect = true;
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $icon = trim($_POST['icon']);
                
                // Validate input
                if (empty($name) || empty($icon)) {
                    $_SESSION['error_message'] = "Name and icon are required";
                } else {
                    $stmt = $conn->prepare("UPDATE `categories` SET `name`=?, `icon`=? WHERE `id`=?");
                    $stmt->bind_param("ssi", $name, $icon, $id);
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Category updated successfully";
                } else {
                        $_SESSION['error_message'] = "Error updating category: " . $conn->error;
                    }
                }
                $redirect = true;
                break;

            case 'delete':
                $id = intval($_POST['id']);
                // Check if category has products
                $products = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id")->fetch_assoc()['count'];
                if ($products == 0) {
                    if($conn->query("DELETE FROM `categories` WHERE `id` = $id")) {
                        $_SESSION['success_message'] = "Category deleted successfully";
                    } else {
                        $_SESSION['error_message'] = "Error deleting category: " . $conn->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Cannot delete category with products";
                }
                $redirect = true;
                break;
        }
        
        if ($redirect) {
            header('Location: admin_categories.php');
            exit();
        }
    }
}

// Include header after handling form submission
require_once 'includes/admin_header.php';

// Display any messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Get categories with product counts
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Category Management</h1>
        <button type="button" class="btn btn-sunset" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-lg"></i> Add New Category
        </button>
    </div>
</div>

<!-- Add this before the table -->
<div class="icon-preview-modal modal" id="iconPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark">
            <div class="modal-header border-sunset">
                <h5 class="modal-title">Select an Icon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="search-box mb-3">
                    <input type="text" class="form-control bg-dark text-light" id="iconSearch" placeholder="Search icons...">
                </div>
                <div class="icon-grid">
                    <!-- Icons will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo display_text($category['name']); ?></td>
                        <td><span class="badge bg-info"><?php echo $category['product_count']; ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($category['product_count'] == 0): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark">
                                <div class="modal-header border-sunset">
                                    <h5 class="modal-title">Edit Category</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST" class="needs-validation" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Category Name</label>
                                            <input type="text" 
                                                   class="form-control bg-dark text-light" 
                                                   name="name" 
                                                   value="<?php echo display_text($category['name']); ?>"
                                                   required 
                                                   pattern="[A-Za-z0-9\s]+"
                                                   minlength="2"
                                                   maxlength="50"
                                                   placeholder="Enter category name">
                                            <div class="invalid-feedback">
                                                Please enter a valid category name (2-50 characters, letters and numbers only)
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Select Icon</label>
                                            <div class="icon-selection-container">
                                                <input type="text" 
                                                       class="form-control bg-dark text-light d-none" 
                                                       name="icon" 
                                                       id="editIconInput<?php echo $category['id']; ?>" 
                                                       required 
                                                       value="<?php echo display_text($category['icon'] ?? 'bi-tag'); ?>">
                                                
                                                <div class="search-box mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-dark border-sunset">
                                                            <i class="bi bi-search"></i>
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control bg-dark text-light" 
                                                               id="editIconSearch<?php echo $category['id']; ?>"
                                                               placeholder="Search icons...">
                                                    </div>
                                                </div>

                                                <div class="icon-grid-container">
                                                    <div class="icon-grid" id="editIconGrid<?php echo $category['id']; ?>">
                                                        <!-- Icons will be populated by JavaScript -->
                                                    </div>
                                                </div>

                                                <div class="selected-icon-preview mt-3 text-center">
                                                    <div class="preview-label text-muted small mb-2">Selected Icon</div>
                                                    <div class="preview-icon">
                                                        <i id="editIconPreview<?php echo $category['id']; ?>" class="bi <?php echo display_text($category['icon'] ?? 'bi-tag'); ?>"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-sunset">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sunset">
                                            <i class="bi bi-check-lg me-1"></i> Update Category
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-sunset">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-4">
                        <label class="form-label">Category Name</label>
                        <input type="text" 
                               class="form-control bg-dark text-light" 
                               name="name" 
                               required 
                               pattern="[A-Za-z0-9\s]+"
                               minlength="2"
                               maxlength="50"
                               placeholder="Enter category name">
                        <div class="invalid-feedback">
                            Please enter a valid category name (2-50 characters, letters and numbers only)
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Select Icon</label>
                        <div class="icon-selection-container">
                            <input type="text" 
                                   class="form-control bg-dark text-light d-none" 
                                   name="icon" 
                                   id="addIconInput" 
                                   required 
                                   value="bi-tag">
                            
                            <div class="search-box mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-sunset">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control bg-dark text-light" 
                                           id="iconSearchInput"
                                           placeholder="Search icons...">
                                </div>
                            </div>

                            <div class="icon-grid-container">
                                <div class="icon-grid" id="addCategoryIconGrid">
                                    <!-- Icons will be populated by JavaScript -->
                                </div>
                            </div>

                            <div class="selected-icon-preview mt-3 text-center">
                                <div class="preview-label text-muted small mb-2">Selected Icon</div>
                                <div class="preview-icon">
                                    <i id="addIconPreview" class="bi bi-tag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-sunset">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sunset">
                        <i class="bi bi-plus-lg me-1"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    const row = document.querySelector(`tr[data-category-id="${id}"]`);
    if (confirm('Are you sure you want to delete this category?')) {
        // Animate row removal
        row.style.animation = 'slideIn 0.3s reverse';
        row.addEventListener('animationend', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.append(form);
        form.submit();
        });
    }
}

const bootstrapIcons = [
    'bi-tag', 'bi-bag', 'bi-cart', 'bi-shop', 'bi-laptop', 'bi-phone', 'bi-tv',
    'bi-headphones', 'bi-camera', 'bi-printer', 'bi-watch', 'bi-speaker',
    'bi-controller', 'bi-keyboard', 'bi-mouse', 'bi-cpu', 'bi-gpu-card',
    'bi-memory', 'bi-disc', 'bi-display', 'bi-router', 'bi-wifi', 'bi-bluetooth',
    'bi-battery', 'bi-plug', 'bi-tools', 'bi-wrench', 'bi-screwdriver',
    'bi-hammer', 'bi-box', 'bi-gift', 'bi-basket', 'bi-bag-check', 'bi-cart-plus',
    'bi-star', 'bi-heart', 'bi-award', 'bi-trophy', 'bi-diamond', 'bi-gem',
    'bi-clock', 'bi-calendar', 'bi-alarm', 'bi-bell', 'bi-bookmark', 'bi-book',
    'bi-music-note', 'bi-film', 'bi-camera-video', 'bi-image', 'bi-file-earmark',
    'bi-folder', 'bi-house', 'bi-building', 'bi-truck', 'bi-car-front',
    'bi-bicycle', 'bi-airplane', 'bi-train-front', 'bi-bus-front', 'bi-rocket',
    'bi-compass', 'bi-map', 'bi-geo-alt', 'bi-pin-map', 'bi-globe',
    'bi-briefcase', 'bi-wallet', 'bi-credit-card', 'bi-cash', 'bi-bank',
    'bi-piggy-bank', 'bi-coin', 'bi-currency-dollar', 'bi-currency-euro',
    'bi-graph-up', 'bi-pie-chart', 'bi-bar-chart', 'bi-clipboard-data',
    'bi-person', 'bi-people', 'bi-person-workspace', 'bi-person-badge',
    'bi-shield', 'bi-lock', 'bi-key', 'bi-door-open', 'bi-window',
    'bi-cloud', 'bi-sun', 'bi-moon', 'bi-stars', 'bi-rainbow', 'bi-tree',
    'bi-flower1', 'bi-flower2', 'bi-flower3', 'bi-bug', 'bi-cup-hot',
    'bi-egg-fried', 'bi-cup', 'bi-egg', 'bi-basket2', 'bi-basket3'
];

let currentInputId = null;

function showIconPicker(inputId) {
    currentInputId = inputId;
    const modal = new bootstrap.Modal(document.getElementById('iconPreviewModal'));
    populateIcons();
    modal.show();
}

function populateIcons(searchTerm = '') {
    const iconGrid = document.querySelector('.icon-grid');
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => selectIcon(icon);
        
        // Check if this icon is currently selected
        const currentIcon = document.getElementById(currentInputId)?.value;
        if (currentIcon === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Animation utility functions
const animateElement = (element, animation, duration = 300) => {
    element.style.animation = 'none';
    element.offsetHeight; // Trigger reflow
    element.style.animation = `${animation} ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
};

const addLoadingState = (element) => {
    element.classList.add('loading');
    return () => element.classList.remove('loading');
};

// Enhance icon selection with animations
function selectIcon(icon) {
    const input = document.getElementById(currentInputId);
    const oldIcon = input.value;
    input.value = icon;
    
    // Update preview with animation
    const previewId = currentInputId.replace('Input', 'Preview');
    const preview = document.getElementById(previewId);
    
    // Animate icon change
    animateElement(preview, 'scaleIn');
    preview.className = `bi ${icon}`;
    
    // Animate selected state in grid
    const iconGrid = document.querySelector('.icon-grid');
    iconGrid.querySelectorAll('i').forEach(i => {
        if (i.classList.contains(oldIcon)) {
            i.classList.remove('selected');
            animateElement(i, 'fadeIn');
        }
        if (i.classList.contains(icon)) {
            i.classList.add('selected');
            animateElement(i, 'bounce');
        }
    });
    
    // Close modal with animation
    const modal = bootstrap.Modal.getInstance(document.getElementById('iconPreviewModal'));
    modal.hide();
}

// Enhance icon search with animations
document.getElementById('iconSearch').addEventListener('input', function(e) {
    const iconGrid = document.querySelector('.icon-grid');
    const removeLoading = addLoadingState(iconGrid);
    
    setTimeout(() => {
        populateIcons(e.target.value);
        removeLoading();
        
        // Animate new icons
        iconGrid.querySelectorAll('i').forEach((icon, index) => {
            icon.style.animationDelay = `${index * 0.05}s`;
            animateElement(icon, 'fadeIn');
        });
    }, 150);
});

// Enhance form submission with animations
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Animate invalid fields
            form.querySelectorAll(':invalid').forEach(field => {
                animateElement(field, 'shake');
            });
        } else {
            // Add loading state to submit button
            const submitBtn = form.querySelector('[type="submit"]');
            addLoadingState(submitBtn);
        }
        form.classList.add('was-validated');
    });
});

// Function to populate icons in the Add Category modal
function populateAddCategoryIcons(searchTerm = '') {
    const iconGrid = document.getElementById('addCategoryIconGrid');
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => {
            // Update hidden input
            document.getElementById('addIconInput').value = icon;
            
            // Update preview
            document.getElementById('addIconPreview').className = `bi ${icon}`;
            
            // Update selected state
            document.querySelectorAll('#addCategoryIconGrid i').forEach(el => {
                el.classList.remove('selected');
            });
            i.classList.add('selected');
        };
        
        // Check if this icon is currently selected
        if (document.getElementById('addIconInput').value === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Initialize icons when the modal is shown
document.getElementById('addCategoryModal').addEventListener('shown.bs.modal', function () {
    populateAddCategoryIcons();
});

// Search functionality for Add Category modal
document.getElementById('iconSearchInput').addEventListener('input', function(e) {
    populateAddCategoryIcons(e.target.value);
});

// Clear search when modal is hidden
document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('iconSearchInput').value = '';
    populateAddCategoryIcons();
});

// Function to populate icons in the Edit Category modal
function populateEditIcons(categoryId, searchTerm = '') {
    const iconGrid = document.getElementById(`editIconGrid${categoryId}`);
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => {
            // Update hidden input
            document.getElementById(`editIconInput${categoryId}`).value = icon;
            
            // Update preview
            document.getElementById(`editIconPreview${categoryId}`).className = `bi ${icon}`;
            
            // Update selected state
            document.querySelectorAll(`#editIconGrid${categoryId} i`).forEach(el => {
                el.classList.remove('selected');
            });
            i.classList.add('selected');
        };
        
        // Check if this icon is currently selected
        if (document.getElementById(`editIconInput${categoryId}`).value === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Initialize icons when edit modals are shown
document.querySelectorAll('[id^="editCategoryModal"]').forEach(modal => {
    modal.addEventListener('shown.bs.modal', function () {
        const categoryId = this.id.replace('editCategoryModal', '');
        populateEditIcons(categoryId);
        
        // Add search functionality
        const searchInput = document.getElementById(`editIconSearch${categoryId}`);
        searchInput.addEventListener('input', function() {
            populateEditIcons(categoryId, this.value);
        });
    });
    
    // Clear search when modal is hidden
    modal.addEventListener('hidden.bs.modal', function () {
        const categoryId = this.id.replace('editCategoryModal', '');
        document.getElementById(`editIconSearch${categoryId}`).value = '';
        populateEditIcons(categoryId);
    });
});

// Enhance modal interactions
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('show.bs.modal', function() {
        const content = this.querySelector('.modal-content');
        animateElement(content, 'scaleIn');
    });
    
    // Animate form fields on modal show
    modal.addEventListener('shown.bs.modal', function() {
        const fields = this.querySelectorAll('.form-control, .icon-grid i');
        fields.forEach((field, index) => {
            field.style.animationDelay = `${index * 0.1}s`;
            animateElement(field, 'fadeIn');
        });
    });
});

// Add smooth transitions for icon grid updates
function updateIconGrid(gridId, icons) {
    const grid = document.getElementById(gridId);
    const removeLoading = addLoadingState(grid);
    
    setTimeout(() => {
        grid.innerHTML = '';
        icons.forEach((icon, index) => {
            const i = document.createElement('i');
            i.className = `bi ${icon}`;
            i.style.animationDelay = `${index * 0.05}s`;
            i.onclick = () => selectIcon(icon);
            grid.appendChild(i);
            animateElement(i, 'fadeIn');
        });
        removeLoading();
    }, 150);
}

// Enhance icon preview interactions
document.querySelectorAll('.selected-icon-preview').forEach(preview => {
    preview.addEventListener('mouseenter', function() {
        const icon = this.querySelector('i');
        animateElement(icon, 'bounce');
    });
});

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate table rows on load
    document.querySelectorAll('tbody tr').forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        animateElement(row, 'slideIn');
    });
    
    // Animate alerts if present
    document.querySelectorAll('.alert').forEach(alert => {
        animateElement(alert, 'slideIn');
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            alert.style.animation = 'slideIn 0.3s reverse';
            alert.addEventListener('animationend', () => alert.remove());
        }, 5000);
    });
});
</script>

<style>
/* Animation Keyframes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Apply animations to elements */
.modal.fade .modal-dialog {
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal.fade.show .modal-dialog {
    transform: scale(1);
    opacity: 1;
}

.modal-content {
    animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.2);
}

.icon-grid i {
    animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation-fill-mode: both;
}

/* Stagger animation for icons */
.icon-grid i:nth-child(1n) { animation-delay: 0.05s; }
.icon-grid i:nth-child(2n) { animation-delay: 0.1s; }
.icon-grid i:nth-child(3n) { animation-delay: 0.15s; }
.icon-grid i:nth-child(4n) { animation-delay: 0.2s; }

.table tbody tr {
    animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation-fill-mode: both;
}

/* Stagger animation for table rows */
.table tbody tr:nth-child(1n) { animation-delay: 0.05s; }
.table tbody tr:nth-child(2n) { animation-delay: 0.1s; }
.table tbody tr:nth-child(3n) { animation-delay: 0.15s; }

.alert {
    animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-sunset {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-sunset:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
}

.btn-sunset:active {
    transform: translateY(1px);
}

.icon-grid i {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-grid i:hover {
    transform: scale(1.1) rotate(5deg);
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.icon-grid i.selected {
    animation: bounce 1s;
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.4);
}

.form-control {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.2);
}

.selected-icon-preview {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selected-icon-preview:hover {
    transform: scale(1.05);
    background: rgba(255, 123, 37, 0.1);
}

.selected-icon-preview .preview-icon i {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selected-icon-preview:hover .preview-icon i {
    transform: scale(1.1) rotate(5deg);
}

/* Smooth transitions for all interactive elements */
.btn,
.form-control,
.icon-grid i,
.table tr,
.modal-content,
.selected-icon-preview,
.search-box input {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Add animation to validation states */
.was-validated .form-control:valid {
    animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.was-validated .form-control:invalid {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
}

/* Loading state animations */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 200%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    animation: loading 1.5s infinite;
}

@keyframes loading {
    from { transform: translateX(-100%); }
    to { transform: translateX(100%); }
}

.modal-content {
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.2);
}

.modal-header {
    background: linear-gradient(to right, rgba(255, 123, 37, 0.1), rgba(255, 77, 109, 0.1));
    border-bottom: 2px solid var(--sunset-orange);
    padding: 1rem 1.5rem;
}

.modal-footer {
    background: rgba(26, 26, 46, 0.5);
    border-top: 2px solid var(--sunset-orange);
    padding: 1rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
    background: var(--sunset-darker);
}

.form-label {
    color: var(--sunset-light);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    background: rgba(22, 33, 62, 0.7) !important;
    border: 1px solid rgba(255, 123, 37, 0.2);
    color: var(--sunset-light) !important;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
    background: rgba(22, 33, 62, 0.9) !important;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.input-group .btn {
    padding: 0.75rem 1rem;
}

.border-sunset {
    border-color: var(--sunset-orange) !important;
}

.icon-preview {
    background: rgba(255, 123, 37, 0.05);
    transition: all 0.3s ease;
}

.icon-preview:hover {
    background: rgba(255, 123, 37, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.2);
}

.btn-sunset {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-sunset:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--sunset-light);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.invalid-feedback {
    color: var(--sunset-red);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.modal-content {
    border: 1px solid var(--sunset-purple);
}
.border-sunset {
    border-color: var(--sunset-purple) !important;
}
.form-control:focus, .form-select:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
}

.table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(26, 26, 46, 0.1);
    --bs-table-hover-bg: rgba(255, 77, 109, 0.15);
    border-color: rgba(255, 123, 37, 0.2) !important;
}

.table td, .table th, .table tr {
    background-color: rgba(22, 33, 62, 0.7) !important;
    color: var(--text-light) !important;
    border-color: rgba(255, 123, 37, 0.15) !important;
}

.table-hover tbody tr:hover td {
    background-color: rgba(40, 40, 72, 0.9) !important;
    box-shadow: 0 2px 8px rgba(255, 77, 109, 0.2);
}

.table thead th {
    background: linear-gradient(135deg, 
        rgba(255, 123, 37, 0.25), 
        rgba(255, 77, 109, 0.25)) !important;
    color: var(--sunset-orange) !important;
    border-bottom: 2px solid var(--sunset-pink) !important;
}

.overlay-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1060;
    width: 90%;
    max-width: 800px;
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 20px rgba(255, 123, 37, 0.3);
}

.modal-backdrop.fade.show {
    opacity: 0.8;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.icon-grid i {
    font-size: 24px;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
}

.modal {
    z-index: 1060 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

.icon-preview-modal .modal-content {
    background: var(--sunset-darker) !important;
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.3);
}

.icon-preview-modal .modal-header {
    background: rgba(255, 123, 37, 0.1);
    border-bottom: 2px solid var(--sunset-orange);
}

.icon-preview-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--sunset-orange) var(--sunset-darker);
}

.icon-preview-modal .modal-body::-webkit-scrollbar {
    width: 8px;
}

.icon-preview-modal .modal-body::-webkit-scrollbar-track {
    background: var(--sunset-darker);
}

.icon-preview-modal .modal-body::-webkit-scrollbar-thumb {
    background-color: var(--sunset-orange);
    border-radius: 4px;
}

.search-box .form-control {
    border: 1px solid var(--sunset-orange);
    border-radius: 20px;
    padding: 10px 15px;
    transition: all 0.3s ease;
}

.search-box .form-control:focus {
    box-shadow: 0 0 0 3px rgba(255, 123, 37, 0.25);
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 15px;
    padding: 15px;
}

.icon-grid i {
    font-size: 24px;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.icon-grid i.selected {
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.4);
}

.icon-selection-container {
    background: rgba(22, 33, 62, 0.7);
    border: 1px solid var(--sunset-orange);
    border-radius: 8px;
    padding: 1.25rem;
}

.icon-grid-container {
    max-height: 200px;
    overflow-y: auto;
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.2);
    margin: 0 -5px;
    padding: 5px;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    gap: 8px;
    padding: 5px;
}

.icon-grid i {
    font-size: 20px;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(255, 123, 37, 0.2);
}

.icon-grid i.selected {
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.3);
}

.selected-icon-preview {
    background: rgba(255, 123, 37, 0.05);
    border-radius: 6px;
    padding: 1rem;
}

.selected-icon-preview .preview-icon {
    font-size: 2rem;
    color: var(--sunset-orange);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.search-box .input-group-text {
    color: var(--sunset-orange);
    border-color: var(--sunset-orange);
}

.search-box .form-control {
    border-color: var(--sunset-orange);
}

.search-box .form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
}

/* Custom scrollbar for the icon grid */
.icon-grid-container::-webkit-scrollbar {
    width: 6px;
}

.icon-grid-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.icon-grid-container::-webkit-scrollbar-thumb {
    background: var(--sunset-orange);
    border-radius: 3px;
}

.icon-grid-container::-webkit-scrollbar-thumb:hover {
    background: var(--sunset-red);
}
</style>

<?php require_once 'includes/admin_footer.php'; ?> <?php
session_start();
require_once '../includes/config.php';

// Function to safely display text while preserving certain characters
function display_text($text) {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

// Check and fix the categories table structure
$tableExists = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=0");

if (!$tableExists) {
    // Create the table if it doesn't exist
    $sql = "CREATE TABLE `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `icon` varchar(50) NOT NULL,
        `status` enum('active','inactive') DEFAULT 'active',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
} else {
    // Fix the table structure if it exists
    $conn->query("ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    $conn->query("ALTER TABLE `categories` MODIFY `name` varchar(100) NOT NULL");
    $conn->query("ALTER TABLE `categories` MODIFY `icon` varchar(50) NOT NULL");
    $conn->query("ALTER TABLE `categories` MODIFY `status` enum('active','inactive') DEFAULT 'active'");
    $conn->query("ALTER TABLE `categories` MODIFY `created_at` timestamp DEFAULT CURRENT_TIMESTAMP");
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Handle form submissions first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $redirect = false;
        
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $icon = trim($_POST['icon']);
                
                // Validate input
                if (empty($name) || empty($icon)) {
                    $_SESSION['error_message'] = "Name and icon are required";
                } else {
                    // Check if category name already exists
                    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                    $stmt->bind_param("s", $name);
                    $stmt->execute();
                    if($stmt->get_result()->num_rows > 0) {
                        $_SESSION['error_message'] = "A category with this name already exists";
                    } else {
                    $stmt = $conn->prepare("INSERT INTO `categories` (`name`, `icon`) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $icon);
                    
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Category added successfully";
                } else {
                        $_SESSION['error_message'] = "Error adding category: " . $conn->error;
                        }
                    }
                }
                $redirect = true;
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $icon = trim($_POST['icon']);
                
                // Validate input
                if (empty($name) || empty($icon)) {
                    $_SESSION['error_message'] = "Name and icon are required";
                } else {
                    $stmt = $conn->prepare("UPDATE `categories` SET `name`=?, `icon`=? WHERE `id`=?");
                    $stmt->bind_param("ssi", $name, $icon, $id);
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Category updated successfully";
                } else {
                        $_SESSION['error_message'] = "Error updating category: " . $conn->error;
                    }
                }
                $redirect = true;
                break;

            case 'delete':
                $id = intval($_POST['id']);
                // Check if category has products
                $products = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id")->fetch_assoc()['count'];
                if ($products == 0) {
                    if($conn->query("DELETE FROM `categories` WHERE `id` = $id")) {
                        $_SESSION['success_message'] = "Category deleted successfully";
                    } else {
                        $_SESSION['error_message'] = "Error deleting category: " . $conn->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Cannot delete category with products";
                }
                $redirect = true;
                break;
        }
        
        if ($redirect) {
            header('Location: admin_categories.php');
            exit();
        }
    }
}

// Include header after handling form submission
require_once 'includes/admin_header.php';

// Display any messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Get categories with product counts
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Category Management</h1>
        <button type="button" class="btn btn-sunset" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-lg"></i> Add New Category
        </button>
    </div>
</div>

<!-- Add this before the table -->
<div class="icon-preview-modal modal" id="iconPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark">
            <div class="modal-header border-sunset">
                <h5 class="modal-title">Select an Icon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="search-box mb-3">
                    <input type="text" class="form-control bg-dark text-light" id="iconSearch" placeholder="Search icons...">
                </div>
                <div class="icon-grid">
                    <!-- Icons will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo display_text($category['name']); ?></td>
                        <td><span class="badge bg-info"><?php echo $category['product_count']; ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($category['product_count'] == 0): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark">
                                <div class="modal-header border-sunset">
                                    <h5 class="modal-title">Edit Category</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST" class="needs-validation" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Category Name</label>
                                            <input type="text" 
                                                   class="form-control bg-dark text-light" 
                                                   name="name" 
                                                   value="<?php echo display_text($category['name']); ?>"
                                                   required 
                                                   pattern="[A-Za-z0-9\s]+"
                                                   minlength="2"
                                                   maxlength="50"
                                                   placeholder="Enter category name">
                                            <div class="invalid-feedback">
                                                Please enter a valid category name (2-50 characters, letters and numbers only)
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Select Icon</label>
                                            <div class="icon-selection-container">
                                                <input type="text" 
                                                       class="form-control bg-dark text-light d-none" 
                                                       name="icon" 
                                                       id="editIconInput<?php echo $category['id']; ?>" 
                                                       required 
                                                       value="<?php echo display_text($category['icon'] ?? 'bi-tag'); ?>">
                                                
                                                <div class="search-box mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-dark border-sunset">
                                                            <i class="bi bi-search"></i>
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control bg-dark text-light" 
                                                               id="editIconSearch<?php echo $category['id']; ?>"
                                                               placeholder="Search icons...">
                                                    </div>
                                                </div>

                                                <div class="icon-grid-container">
                                                    <div class="icon-grid" id="editIconGrid<?php echo $category['id']; ?>">
                                                        <!-- Icons will be populated by JavaScript -->
                                                    </div>
                                                </div>

                                                <div class="selected-icon-preview mt-3 text-center">
                                                    <div class="preview-label text-muted small mb-2">Selected Icon</div>
                                                    <div class="preview-icon">
                                                        <i id="editIconPreview<?php echo $category['id']; ?>" class="bi <?php echo display_text($category['icon'] ?? 'bi-tag'); ?>"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-sunset">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sunset">
                                            <i class="bi bi-check-lg me-1"></i> Update Category
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-sunset">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-4">
                        <label class="form-label">Category Name</label>
                        <input type="text" 
                               class="form-control bg-dark text-light" 
                               name="name" 
                               required 
                               pattern="[A-Za-z0-9\s]+"
                               minlength="2"
                               maxlength="50"
                               placeholder="Enter category name">
                        <div class="invalid-feedback">
                            Please enter a valid category name (2-50 characters, letters and numbers only)
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Select Icon</label>
                        <div class="icon-selection-container">
                            <input type="text" 
                                   class="form-control bg-dark text-light d-none" 
                                   name="icon" 
                                   id="addIconInput" 
                                   required 
                                   value="bi-tag">
                            
                            <div class="search-box mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-sunset">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control bg-dark text-light" 
                                           id="iconSearchInput"
                                           placeholder="Search icons...">
                                </div>
                            </div>

                            <div class="icon-grid-container">
                                <div class="icon-grid" id="addCategoryIconGrid">
                                    <!-- Icons will be populated by JavaScript -->
                                </div>
                            </div>

                            <div class="selected-icon-preview mt-3 text-center">
                                <div class="preview-label text-muted small mb-2">Selected Icon</div>
                                <div class="preview-icon">
                                    <i id="addIconPreview" class="bi bi-tag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-sunset">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sunset">
                        <i class="bi bi-plus-lg me-1"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    const row = document.querySelector(`tr[data-category-id="${id}"]`);
    if (confirm('Are you sure you want to delete this category?')) {
        // Animate row removal
        row.style.animation = 'slideIn 0.3s reverse';
        row.addEventListener('animationend', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.append(form);
        form.submit();
        });
    }
}

const bootstrapIcons = [
    'bi-tag', 'bi-bag', 'bi-cart', 'bi-shop', 'bi-laptop', 'bi-phone', 'bi-tv',
    'bi-headphones', 'bi-camera', 'bi-printer', 'bi-watch', 'bi-speaker',
    'bi-controller', 'bi-keyboard', 'bi-mouse', 'bi-cpu', 'bi-gpu-card',
    'bi-memory', 'bi-disc', 'bi-display', 'bi-router', 'bi-wifi', 'bi-bluetooth',
    'bi-battery', 'bi-plug', 'bi-tools', 'bi-wrench', 'bi-screwdriver',
    'bi-hammer', 'bi-box', 'bi-gift', 'bi-basket', 'bi-bag-check', 'bi-cart-plus',
    'bi-star', 'bi-heart', 'bi-award', 'bi-trophy', 'bi-diamond', 'bi-gem',
    'bi-clock', 'bi-calendar', 'bi-alarm', 'bi-bell', 'bi-bookmark', 'bi-book',
    'bi-music-note', 'bi-film', 'bi-camera-video', 'bi-image', 'bi-file-earmark',
    'bi-folder', 'bi-house', 'bi-building', 'bi-truck', 'bi-car-front',
    'bi-bicycle', 'bi-airplane', 'bi-train-front', 'bi-bus-front', 'bi-rocket',
    'bi-compass', 'bi-map', 'bi-geo-alt', 'bi-pin-map', 'bi-globe',
    'bi-briefcase', 'bi-wallet', 'bi-credit-card', 'bi-cash', 'bi-bank',
    'bi-piggy-bank', 'bi-coin', 'bi-currency-dollar', 'bi-currency-euro',
    'bi-graph-up', 'bi-pie-chart', 'bi-bar-chart', 'bi-clipboard-data',
    'bi-person', 'bi-people', 'bi-person-workspace', 'bi-person-badge',
    'bi-shield', 'bi-lock', 'bi-key', 'bi-door-open', 'bi-window',
    'bi-cloud', 'bi-sun', 'bi-moon', 'bi-stars', 'bi-rainbow', 'bi-tree',
    'bi-flower1', 'bi-flower2', 'bi-flower3', 'bi-bug', 'bi-cup-hot',
    'bi-egg-fried', 'bi-cup', 'bi-egg', 'bi-basket2', 'bi-basket3'
];

let currentInputId = null;

function showIconPicker(inputId) {
    currentInputId = inputId;
    const modal = new bootstrap.Modal(document.getElementById('iconPreviewModal'));
    populateIcons();
    modal.show();
}

function populateIcons(searchTerm = '') {
    const iconGrid = document.querySelector('.icon-grid');
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => selectIcon(icon);
        
        // Check if this icon is currently selected
        const currentIcon = document.getElementById(currentInputId)?.value;
        if (currentIcon === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Animation utility functions
const animateElement = (element, animation, duration = 300) => {
    element.style.animation = 'none';
    element.offsetHeight; // Trigger reflow
    element.style.animation = `${animation} ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
};

const addLoadingState = (element) => {
    element.classList.add('loading');
    return () => element.classList.remove('loading');
};

// Enhance icon selection with animations
function selectIcon(icon) {
    const input = document.getElementById(currentInputId);
    const oldIcon = input.value;
    input.value = icon;
    
    // Update preview with animation
    const previewId = currentInputId.replace('Input', 'Preview');
    const preview = document.getElementById(previewId);
    
    // Animate icon change
    animateElement(preview, 'scaleIn');
    preview.className = `bi ${icon}`;
    
    // Animate selected state in grid
    const iconGrid = document.querySelector('.icon-grid');
    iconGrid.querySelectorAll('i').forEach(i => {
        if (i.classList.contains(oldIcon)) {
            i.classList.remove('selected');
            animateElement(i, 'fadeIn');
        }
        if (i.classList.contains(icon)) {
            i.classList.add('selected');
            animateElement(i, 'bounce');
        }
    });
    
    // Close modal with animation
    const modal = bootstrap.Modal.getInstance(document.getElementById('iconPreviewModal'));
    modal.hide();
}

// Enhance icon search with animations
document.getElementById('iconSearch').addEventListener('input', function(e) {
    const iconGrid = document.querySelector('.icon-grid');
    const removeLoading = addLoadingState(iconGrid);
    
    setTimeout(() => {
        populateIcons(e.target.value);
        removeLoading();
        
        // Animate new icons
        iconGrid.querySelectorAll('i').forEach((icon, index) => {
            icon.style.animationDelay = `${index * 0.05}s`;
            animateElement(icon, 'fadeIn');
        });
    }, 150);
});

// Enhance form submission with animations
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Animate invalid fields
            form.querySelectorAll(':invalid').forEach(field => {
                animateElement(field, 'shake');
            });
        } else {
            // Add loading state to submit button
            const submitBtn = form.querySelector('[type="submit"]');
            addLoadingState(submitBtn);
        }
        form.classList.add('was-validated');
    });
});

// Function to populate icons in the Add Category modal
function populateAddCategoryIcons(searchTerm = '') {
    const iconGrid = document.getElementById('addCategoryIconGrid');
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => {
            // Update hidden input
            document.getElementById('addIconInput').value = icon;
            
            // Update preview
            document.getElementById('addIconPreview').className = `bi ${icon}`;
            
            // Update selected state
            document.querySelectorAll('#addCategoryIconGrid i').forEach(el => {
                el.classList.remove('selected');
            });
            i.classList.add('selected');
        };
        
        // Check if this icon is currently selected
        if (document.getElementById('addIconInput').value === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Initialize icons when the modal is shown
document.getElementById('addCategoryModal').addEventListener('shown.bs.modal', function () {
    populateAddCategoryIcons();
});

// Search functionality for Add Category modal
document.getElementById('iconSearchInput').addEventListener('input', function(e) {
    populateAddCategoryIcons(e.target.value);
});

// Clear search when modal is hidden
document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('iconSearchInput').value = '';
    populateAddCategoryIcons();
});

// Function to populate icons in the Edit Category modal
function populateEditIcons(categoryId, searchTerm = '') {
    const iconGrid = document.getElementById(`editIconGrid${categoryId}`);
    iconGrid.innerHTML = '';
    
    const filteredIcons = searchTerm 
        ? bootstrapIcons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()))
        : bootstrapIcons;
    
    filteredIcons.forEach(icon => {
        const i = document.createElement('i');
        i.className = `bi ${icon}`;
        i.onclick = () => {
            // Update hidden input
            document.getElementById(`editIconInput${categoryId}`).value = icon;
            
            // Update preview
            document.getElementById(`editIconPreview${categoryId}`).className = `bi ${icon}`;
            
            // Update selected state
            document.querySelectorAll(`#editIconGrid${categoryId} i`).forEach(el => {
                el.classList.remove('selected');
            });
            i.classList.add('selected');
        };
        
        // Check if this icon is currently selected
        if (document.getElementById(`editIconInput${categoryId}`).value === icon) {
            i.classList.add('selected');
        }
        
        iconGrid.appendChild(i);
    });
}

// Initialize icons when edit modals are shown
document.querySelectorAll('[id^="editCategoryModal"]').forEach(modal => {
    modal.addEventListener('shown.bs.modal', function () {
        const categoryId = this.id.replace('editCategoryModal', '');
        populateEditIcons(categoryId);
        
        // Add search functionality
        const searchInput = document.getElementById(`editIconSearch${categoryId}`);
        searchInput.addEventListener('input', function() {
            populateEditIcons(categoryId, this.value);
        });
    });
    
    // Clear search when modal is hidden
    modal.addEventListener('hidden.bs.modal', function () {
        const categoryId = this.id.replace('editCategoryModal', '');
        document.getElementById(`editIconSearch${categoryId}`).value = '';
        populateEditIcons(categoryId);
    });
});

// Enhance modal interactions
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('show.bs.modal', function() {
        const content = this.querySelector('.modal-content');
        animateElement(content, 'scaleIn');
    });
    
    // Animate form fields on modal show
    modal.addEventListener('shown.bs.modal', function() {
        const fields = this.querySelectorAll('.form-control, .icon-grid i');
        fields.forEach((field, index) => {
            field.style.animationDelay = `${index * 0.1}s`;
            animateElement(field, 'fadeIn');
        });
    });
});

// Add smooth transitions for icon grid updates
function updateIconGrid(gridId, icons) {
    const grid = document.getElementById(gridId);
    const removeLoading = addLoadingState(grid);
    
    setTimeout(() => {
        grid.innerHTML = '';
        icons.forEach((icon, index) => {
            const i = document.createElement('i');
            i.className = `bi ${icon}`;
            i.style.animationDelay = `${index * 0.05}s`;
            i.onclick = () => selectIcon(icon);
            grid.appendChild(i);
            animateElement(i, 'fadeIn');
        });
        removeLoading();
    }, 150);
}

// Enhance icon preview interactions
document.querySelectorAll('.selected-icon-preview').forEach(preview => {
    preview.addEventListener('mouseenter', function() {
        const icon = this.querySelector('i');
        animateElement(icon, 'bounce');
    });
});

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate table rows on load
    document.querySelectorAll('tbody tr').forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        animateElement(row, 'slideIn');
    });
    
    // Animate alerts if present
    document.querySelectorAll('.alert').forEach(alert => {
        animateElement(alert, 'slideIn');
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            alert.style.animation = 'slideIn 0.3s reverse';
            alert.addEventListener('animationend', () => alert.remove());
        }, 5000);
    });
});
</script>

<style>
/* Animation Keyframes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Apply animations to elements */
.modal.fade .modal-dialog {
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal.fade.show .modal-dialog {
    transform: scale(1);
    opacity: 1;
}

.modal-content {
    animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.2);
}

.icon-grid i {
    animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation-fill-mode: both;
}

/* Stagger animation for icons */
.icon-grid i:nth-child(1n) { animation-delay: 0.05s; }
.icon-grid i:nth-child(2n) { animation-delay: 0.1s; }
.icon-grid i:nth-child(3n) { animation-delay: 0.15s; }
.icon-grid i:nth-child(4n) { animation-delay: 0.2s; }

.table tbody tr {
    animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation-fill-mode: both;
}

/* Stagger animation for table rows */
.table tbody tr:nth-child(1n) { animation-delay: 0.05s; }
.table tbody tr:nth-child(2n) { animation-delay: 0.1s; }
.table tbody tr:nth-child(3n) { animation-delay: 0.15s; }

.alert {
    animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-sunset {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-sunset:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
}

.btn-sunset:active {
    transform: translateY(1px);
}

.icon-grid i {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-grid i:hover {
    transform: scale(1.1) rotate(5deg);
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.icon-grid i.selected {
    animation: bounce 1s;
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.4);
}

.form-control {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.2);
}

.selected-icon-preview {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selected-icon-preview:hover {
    transform: scale(1.05);
    background: rgba(255, 123, 37, 0.1);
}

.selected-icon-preview .preview-icon i {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selected-icon-preview:hover .preview-icon i {
    transform: scale(1.1) rotate(5deg);
}

/* Smooth transitions for all interactive elements */
.btn,
.form-control,
.icon-grid i,
.table tr,
.modal-content,
.selected-icon-preview,
.search-box input {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Add animation to validation states */
.was-validated .form-control:valid {
    animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.was-validated .form-control:invalid {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
}

/* Loading state animations */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 200%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    animation: loading 1.5s infinite;
}

@keyframes loading {
    from { transform: translateX(-100%); }
    to { transform: translateX(100%); }
}

.modal-content {
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.2);
}

.modal-header {
    background: linear-gradient(to right, rgba(255, 123, 37, 0.1), rgba(255, 77, 109, 0.1));
    border-bottom: 2px solid var(--sunset-orange);
    padding: 1rem 1.5rem;
}

.modal-footer {
    background: rgba(26, 26, 46, 0.5);
    border-top: 2px solid var(--sunset-orange);
    padding: 1rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
    background: var(--sunset-darker);
}

.form-label {
    color: var(--sunset-light);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    background: rgba(22, 33, 62, 0.7) !important;
    border: 1px solid rgba(255, 123, 37, 0.2);
    color: var(--sunset-light) !important;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
    background: rgba(22, 33, 62, 0.9) !important;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.input-group .btn {
    padding: 0.75rem 1rem;
}

.border-sunset {
    border-color: var(--sunset-orange) !important;
}

.icon-preview {
    background: rgba(255, 123, 37, 0.05);
    transition: all 0.3s ease;
}

.icon-preview:hover {
    background: rgba(255, 123, 37, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.2);
}

.btn-sunset {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-sunset:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--sunset-light);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.invalid-feedback {
    color: var(--sunset-red);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.modal-content {
    border: 1px solid var(--sunset-purple);
}
.border-sunset {
    border-color: var(--sunset-purple) !important;
}
.form-control:focus, .form-select:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
}

.table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(26, 26, 46, 0.1);
    --bs-table-hover-bg: rgba(255, 77, 109, 0.15);
    border-color: rgba(255, 123, 37, 0.2) !important;
}

.table td, .table th, .table tr {
    background-color: rgba(22, 33, 62, 0.7) !important;
    color: var(--text-light) !important;
    border-color: rgba(255, 123, 37, 0.15) !important;
}

.table-hover tbody tr:hover td {
    background-color: rgba(40, 40, 72, 0.9) !important;
    box-shadow: 0 2px 8px rgba(255, 77, 109, 0.2);
}

.table thead th {
    background: linear-gradient(135deg, 
        rgba(255, 123, 37, 0.25), 
        rgba(255, 77, 109, 0.25)) !important;
    color: var(--sunset-orange) !important;
    border-bottom: 2px solid var(--sunset-pink) !important;
}

.overlay-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1060;
    width: 90%;
    max-width: 800px;
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 20px rgba(255, 123, 37, 0.3);
}

.modal-backdrop.fade.show {
    opacity: 0.8;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.icon-grid i {
    font-size: 24px;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
}

.modal {
    z-index: 1060 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

.icon-preview-modal .modal-content {
    background: var(--sunset-darker) !important;
    border: 2px solid var(--sunset-orange);
    box-shadow: 0 0 30px rgba(255, 123, 37, 0.3);
}

.icon-preview-modal .modal-header {
    background: rgba(255, 123, 37, 0.1);
    border-bottom: 2px solid var(--sunset-orange);
}

.icon-preview-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--sunset-orange) var(--sunset-darker);
}

.icon-preview-modal .modal-body::-webkit-scrollbar {
    width: 8px;
}

.icon-preview-modal .modal-body::-webkit-scrollbar-track {
    background: var(--sunset-darker);
}

.icon-preview-modal .modal-body::-webkit-scrollbar-thumb {
    background-color: var(--sunset-orange);
    border-radius: 4px;
}

.search-box .form-control {
    border: 1px solid var(--sunset-orange);
    border-radius: 20px;
    padding: 10px 15px;
    transition: all 0.3s ease;
}

.search-box .form-control:focus {
    box-shadow: 0 0 0 3px rgba(255, 123, 37, 0.25);
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 15px;
    padding: 15px;
}

.icon-grid i {
    font-size: 24px;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.icon-grid i.selected {
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.4);
}

.icon-selection-container {
    background: rgba(22, 33, 62, 0.7);
    border: 1px solid var(--sunset-orange);
    border-radius: 8px;
    padding: 1.25rem;
}

.icon-grid-container {
    max-height: 200px;
    overflow-y: auto;
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.2);
    margin: 0 -5px;
    padding: 5px;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    gap: 8px;
    padding: 5px;
}

.icon-grid i {
    font-size: 20px;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--sunset-text);
    background: rgba(255, 123, 37, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.icon-grid i:hover {
    background: var(--sunset-orange);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(255, 123, 37, 0.2);
}

.icon-grid i.selected {
    background: var(--sunset-orange);
    color: white;
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.3);
}

.selected-icon-preview {
    background: rgba(255, 123, 37, 0.05);
    border-radius: 6px;
    padding: 1rem;
}

.selected-icon-preview .preview-icon {
    font-size: 2rem;
    color: var(--sunset-orange);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.search-box .input-group-text {
    color: var(--sunset-orange);
    border-color: var(--sunset-orange);
}

.search-box .form-control {
    border-color: var(--sunset-orange);
}

.search-box .form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
}

/* Custom scrollbar for the icon grid */
.icon-grid-container::-webkit-scrollbar {
    width: 6px;
}

.icon-grid-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.icon-grid-container::-webkit-scrollbar-thumb {
    background: var(--sunset-orange);
    border-radius: 3px;
}

.icon-grid-container::-webkit-scrollbar-thumb:hover {
    background: var(--sunset-red);
}
</style>

<?php require_once 'includes/admin_footer.php'; ?> 