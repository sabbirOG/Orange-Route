<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $deletePassword = $_POST['delete_password'] ?? '';
    
    if (empty($deletePassword)) {
        $error = 'Please enter your password to delete your account';
    } elseif (!password_verify($deletePassword, $user['password_hash'])) {
        $error = 'Password is incorrect';
    } else {
        try {
            // Delete user's profile picture if exists
            if (!empty($user['profile_picture'])) {
                $picturePath = __DIR__ . '/../../uploads/profile_pictures/' . $user['profile_picture'];
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
            }
            
            // Delete user account
            OrangeRoute\Database::query("DELETE FROM users WHERE id = ?", [$user['id']]);
            
            // Logout and redirect
            OrangeRoute\Session::destroy();
            header('Location: login.php?deleted=1');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to delete account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title = 'Profile'; $backHref = 'map.php'; $rightActionHtml = '<a href="../api/logout.php" style="color: var(--danger); text-decoration: none; font-weight: 600;">Logout</a>'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container" style="padding-bottom: 100px;">
        <?php if ($error): ?>
            <div class="alert alert-error" style="display: flex; align-items: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Username updated successfully!</div>
        <?php endif; ?>
        
        <div class="card text-center">
            <?php if ($user['profile_picture']): ?>
                <img src="<?= e($user['profile_picture']) ?>" alt="Profile" class="profile-avatar" id="profileAvatar">
            <?php else: ?>
                <div class="avatar-placeholder" id="avatarPlaceholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                    </svg>
                </div>
            <?php endif; ?>
            
            <h3 class="mt-2"><?= e($user['username']) ?></h3>
            <p class="text-muted">
                <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'driver' ? 'success' : 'primary') ?>">
                    <?= ucfirst(e($user['role'])) ?>
                </span>
            </p>
            
            <label for="fileInput" class="btn btn-sm" style="background: var(--primary); color: white; cursor: pointer; margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; justify-content: center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="12" cy="12" r="3"></circle></svg>
                Change Photo
            </label>
            <input type="file" id="fileInput" accept="image/*" style="display: none;">
            
            <?php if ($user['profile_picture']): ?>
                <button id="removePhoto" class="btn btn-sm" style="background: var(--danger); color: white; margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; justify-content: center; margin-left: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Remove
                </button>
            <?php endif; ?>
        </div>
        
        <?php if ($user['role'] !== 'driver'): ?>
        <div class="card">
            <h3>Personal Information</h3>
            
            <div style="margin-bottom: 16px;">
                <p><strong>Username:</strong> <?= e($user['username']) ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <p><strong>Department:</strong> <?= $user['department'] ? e($user['department']) : '<span class="text-muted">Not set</span>' ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <p><strong>Session:</strong> <?= $user['session'] ? e($user['session']) : '<span class="text-muted">Not set</span>' ?></p>
            </div>
            
            <button onclick="location.href='edit_profile.php'" class="btn btn-sm" style="background: var(--primary); color: white; margin-top: 8px;">
                EDIT
            </button>
        </div>
        <?php else: ?>
        <div class="card">
            <h3>Personal Information</h3>
            
            <div style="margin-bottom: 16px;">
                <p><strong>Username:</strong> <?= e($user['username']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Account Settings</h3>
            
            <div class="theme-toggle" onclick="location.href='change_password.php'" style="cursor: pointer;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <div>
                        <strong>Change Password</strong><br>
                        <span class="text-muted" style="font-size: 13px;">Update your password</span>
                    </div>
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            
            <div class="theme-toggle">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg id="themeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <div>
                        <strong id="themeLabel">Dark Mode</strong><br>
                        <span class="text-muted" style="font-size: 13px;" id="themeDescription">Switch to dark theme</span>
                    </div>
                </div>
                <div class="toggle-switch" id="darkModeToggle"></div>
            </div>
        </div>
        
        <div class="card">
            <h3>Account Info</h3>
            <p><strong>Role:</strong> <?= e(ucfirst($user['role'])) ?></p>
            <p><strong>Status:</strong> <span class="badge badge-success">Active</span></p>
            <p><strong>Member since:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
        </div>
        
        <div class="card" style="border: 1px solid #dc3545;">
            <h3 style="color: #dc3545; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
                Delete Account
            </h3>
            <p style="color: var(--text-light); font-size: 14px; margin-bottom: 12px;">
                Once you delete your account, there is no going back. This will permanently delete your account and remove all your data.
            </p>
            <button type="button" onclick="showDeleteModal()" class="btn" style="background: #dc3545; color: white; width: 100%;">
                Delete My Account
            </button>
        </div>
        
        <a href="../api/logout.php" class="btn" style="background: #f44336; color: white;">Logout</a>
    </div>
    
    <!-- Delete Account Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div class="card" style="margin: 20px; max-width: 400px; animation: slideUp 0.3s ease;">
            <h3 style="color: #dc3545; margin-bottom: 12px;">Confirm Account Deletion</h3>
            <p style="color: var(--text-light); font-size: 14px; margin-bottom: 16px;">
                Are you absolutely sure? This action cannot be undone. All your data including routes, notifications, and profile information will be permanently deleted.
            </p>
            
            <form method="POST">
                <input type="hidden" name="action" value="delete_account">
                <div class="form-group">
                    <label>Enter your password to confirm</label>
                    <div style="position: relative;">
                        <input type="password" id="delete_password" name="delete_password" required placeholder="Enter your password" style="padding-right: 60px;">
                        <button type="button" id="delete_toggle" onclick="togglePassword('delete_password', 'delete_toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                            Show
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="button" onclick="hideDeleteModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn" style="flex: 1; background: #dc3545; color: white;">Yes, Delete My Account</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    
    <?php $active = 'profile'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script>
        // Profile picture upload
        document.getElementById('fileInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Check file size (500KB max)
            if (file.size > 500 * 1024) {
                alert('File too large. Maximum size is 500KB. Your file: ' + Math.round(file.size / 1024) + 'KB');
                e.target.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('profile_picture', file);
            
            try {
                const response = await fetch('/OrangeRoute/public/api/upload_profile_picture.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Server response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('JSON parse error:', err);
                    alert('Server error: ' + text.substring(0, 100));
                    return;
                }
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Upload failed');
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            }
        });
        
        // Remove profile picture
        const removePhotoBtn = document.getElementById('removePhoto');
        if (removePhotoBtn) {
            removePhotoBtn.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to remove your profile picture?')) {
                    return;
                }
                
                try {
                    const response = await fetch('/OrangeRoute/public/api/remove_profile_picture.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    const text = await response.text();
                    console.log('Remove response:', text);
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        console.error('JSON parse error:', err);
                        alert('Server error: ' + text.substring(0, 100));
                        return;
                    }
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Remove failed');
                    }
                } catch (error) {
                    console.error('Remove error:', error);
                    alert('Remove failed: ' + error.message);
                }
            });
        }
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const themeLabel = document.getElementById('themeLabel');
        const themeDescription = document.getElementById('themeDescription');
        const themeIcon = document.getElementById('themeIcon');
        const isDark = localStorage.getItem('darkMode') === 'true';
        
        function updateThemeText(isDarkMode) {
            if (isDarkMode) {
                themeLabel.textContent = 'Light Mode';
                themeDescription.textContent = 'Switch to light theme';
                themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
            } else {
                themeLabel.textContent = 'Dark Mode';
                themeDescription.textContent = 'Switch to dark theme';
                themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            }
        }
        
        if (isDark) {
            darkModeToggle.classList.add('active');
            document.body.classList.add('dark-mode');
            updateThemeText(true);
        }
        
        darkModeToggle.addEventListener('click', function() {
            const isActive = this.classList.toggle('active');
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', isActive);
            updateThemeText(isActive);
        });
        
        // Apply dark mode on page load
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
        
        function togglePassword(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
        
        function showDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'flex';
        }
        
        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'none';
            document.getElementById('delete_password').value = '';
        }
        
        // Close modal on backdrop click
        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
        
    </script>
</body>
</html>
