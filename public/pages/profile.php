<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <?php $title = 'Profile'; $backHref = 'map.php'; $rightActionHtml = '<a href="../api/logout.php" style="color: var(--danger); text-decoration: none; font-weight: 600;">Logout</a>'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
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
            
            <h3 class="mt-2"><?= e($user['email']) ?></h3>
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
        </div>
        
        <div class="card">
            <h3>Settings</h3>
            
            <div class="theme-toggle">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <div><strong>Dark Mode</strong><br>
                    <span class="text-muted" style="font-size: 13px;">Switch to dark theme</span></div>
                </div>
                <div class="toggle-switch" id="darkModeToggle"></div>
            </div>
        </div>
        
        <div class="card">
            <h3>Account Info</h3>
            <p><strong>Email:</strong> <?= e($user['email']) ?></p>
            <p><strong>Role:</strong> <?= e(ucfirst($user['role'])) ?></p>
            <p><strong>Status:</strong> 
                <?php if ($user['email_verified']): ?>
                    <span class="badge badge-success">Verified</span>
                <?php else: ?>
                    <span class="badge" style="background: #FF9800;">Not Verified</span>
                <?php endif; ?>
            </p>
            <p><strong>Member since:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
        </div>
        
        <a href="../api/logout.php" class="btn" style="background: #f44336; color: white;">Logout</a>
    </div>
    
    <?php $active = 'profile'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script>
        // Profile picture upload
        document.getElementById('fileInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('profile_picture', file);
            
            try {
                const response = await fetch('/api/upload_profile_picture.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Upload failed');
                }
            } catch (error) {
                alert('Upload failed: ' + error.message);
            }
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const isDark = localStorage.getItem('darkMode') === 'true';
        
        if (isDark) {
            darkModeToggle.classList.add('active');
            document.body.classList.add('dark-mode');
        }
        
        darkModeToggle.addEventListener('click', function() {
            const isActive = this.classList.toggle('active');
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', isActive);
        });
        
        // Apply dark mode on page load
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
