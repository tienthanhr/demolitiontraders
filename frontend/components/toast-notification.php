<!-- Toast Notification Container -->
<div id="toast-container"></div>

<!-- Confirm Modal -->
<div id="confirm-modal-overlay" style="display: none;">
    <div id="confirm-modal">
        <div class="confirm-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3 id="confirm-title">Confirm Action</h3>
        <p id="confirm-message">Are you sure?</p>
        <div class="confirm-buttons">
            <button class="btn-cancel" onclick="window.confirmModalResolve(false)">Cancel</button>
            <button class="btn-confirm" onclick="window.confirmModalResolve(true)">Confirm</button>
        </div>
    </div>
</div>

<style>
#toast-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 400px;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
    position: relative;
    overflow: hidden;
}

.toast::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.toast.success::before {
    background: #4CAF50;
}

.toast.error::before {
    background: #f44336;
}

.toast.warning::before {
    background: #ff9800;
}

.toast.info::before {
    background: #2196F3;
}

.toast-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 14px;
}

.toast.success .toast-icon {
    background: #E8F5E9;
    color: #4CAF50;
}

.toast.error .toast-icon {
    background: #FFEBEE;
    color: #f44336;
}

.toast.warning .toast-icon {
    background: #FFF3E0;
    color: #ff9800;
}

.toast.info .toast-icon {
    background: #E3F2FD;
    color: #2196F3;
}

.toast-content {
    flex: 1;
    color: #333;
    font-size: 14px;
    line-height: 1.5;
}

.toast-close {
    flex-shrink: 0;
    background: none;
    border: none;
    color: #999;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
}

.toast-close:hover {
    background: #f5f5f5;
    color: #666;
}

/* Confirm Modal Styles */
#confirm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
    animation: fadeIn 0.2s ease-out;
}

#confirm-modal {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    animation: scaleIn 0.3s ease-out;
    text-align: center;
}

.confirm-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 20px;
    background: #FFF3E0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #ff9800;
}

#confirm-title {
    margin: 0 0 15px;
    font-size: 22px;
    color: #333;
    font-weight: 600;
}

#confirm-message {
    margin: 0 0 25px;
    font-size: 15px;
    color: #666;
    line-height: 1.6;
}

.confirm-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.confirm-buttons button {
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
}

.btn-cancel {
    background: #f5f5f5;
    color: #666;
}

.btn-cancel:hover {
    background: #e0e0e0;
}

.btn-confirm {
    background: #2f3192;
    color: white;
}

.btn-confirm:hover {
    background: #1f2166;
    transform: translateY(-1px);
}

.btn-confirm.danger {
    background: #f44336;
}

.btn-confirm.danger:hover {
    background: #d32f2f;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.toast.removing {
    animation: slideOut 0.3s ease-in forwards;
}

@media (max-width: 480px) {
    #toast-container {
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .toast {
        min-width: auto;
    }
    
    #confirm-modal {
        padding: 25px 20px;
    }
    
    .confirm-buttons {
        flex-direction: column;
    }
    
    .confirm-buttons button {
        width: 100%;
    }
}
</style>

<script>
// Toast Notification System
window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon based on type
    const icons = {
        success: '<i class="fas fa-check"></i>',
        error: '<i class="fas fa-times"></i>',
        warning: '<i class="fas fa-exclamation"></i>',
        info: '<i class="fas fa-info"></i>'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-content">${message}</div>
        <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    return toast;
};

// Convenient shortcuts
window.showSuccess = (msg, duration) => showToast(msg, 'success', duration);
window.showError = (msg, duration) => showToast(msg, 'error', duration);
window.showWarning = (msg, duration) => showToast(msg, 'warning', duration);
window.showInfo = (msg, duration) => showToast(msg, 'info', duration);

// Confirm Modal System
window.showConfirm = function(message, title = 'Confirm Action', isDanger = false) {
    return new Promise((resolve) => {
        const overlay = document.getElementById('confirm-modal-overlay');
        const titleEl = document.getElementById('confirm-title');
        const messageEl = document.getElementById('confirm-message');
        const confirmBtn = overlay.querySelector('.btn-confirm');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Set button style based on danger flag
        if (isDanger) {
            confirmBtn.classList.add('danger');
        } else {
            confirmBtn.classList.remove('danger');
        }
        
        overlay.style.display = 'flex';
        
        // Store resolve function globally
        window.confirmModalResolve = (result) => {
            overlay.style.display = 'none';
            resolve(result);
        };
        
        // Close on overlay click
        overlay.onclick = (e) => {
            if (e.target === overlay) {
                window.confirmModalResolve(false);
            }
        };
        
        // Handle ESC key
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                window.confirmModalResolve(false);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
};

// Override native alert to use toast notifications
(function() {
    const originalAlert = window.alert;
    window.alert = function(message) {
        // Determine type based on message content
        let type = 'info';
        const msg = String(message);
        
        if (msg.includes('success') || msg.includes('Success') || msg.includes('✓') || 
            msg.includes('added') || msg.includes('updated') || msg.includes('deleted') ||
            msg.includes('Thank you') || msg.includes('cleared')) {
            type = 'success';
        } else if (msg.includes('error') || msg.includes('Error') || msg.includes('failed') || 
                   msg.includes('Failed') || msg.includes('✗') || msg.includes('denied')) {
            type = 'error';
        } else if (msg.includes('warning') || msg.includes('Warning') || msg.includes('Please')) {
            type = 'warning';
        }
        
        showToast(message, type);
    };
    
    // Override native confirm to use modal
    const originalConfirm = window.confirm;
    window.confirm = function(message) {
        // For synchronous compatibility, we need to use the modal
        // This is a workaround - ideally code should use showConfirm directly
        return showConfirm(message, 'Confirm', true);
    };
    
    // Preserve originals for debugging
    window.originalAlert = originalAlert;
    window.originalConfirm = originalConfirm;
})();
</script>
