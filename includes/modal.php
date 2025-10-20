<!-- Modal Component -->
<div id="customModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 animate-fadeIn">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl transform transition-all animate-slideUp">
        <!-- Modal Header -->
        <div id="modalHeader" class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800"></h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
            <div id="modalIcon" class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"></div>
            <p id="modalMessage" class="text-gray-700 text-center mb-6"></p>
        </div>
        
        <!-- Modal Footer -->
        <div id="modalFooter" class="px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3">
            <!-- Buttons will be injected here -->
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(20px);
        }
        to { 
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out;
    }
    
    .animate-slideUp {
        animation: slideUp 0.3s ease-out;
    }
    
    .toast {
        animation: slideUp 0.3s ease-out;
    }
    
    .toast-exit {
        animation: fadeIn 0.2s ease-out reverse;
    }
</style>

<script>
// Modal Functions
function showModal(options) {
    const modal = document.getElementById('customModal');
    const title = document.getElementById('modalTitle');
    const icon = document.getElementById('modalIcon');
    const message = document.getElementById('modalMessage');
    const footer = document.getElementById('modalFooter');
    
    // Set title
    title.textContent = options.title || 'Notification';
    
    // Set icon
    let iconHtml = '';
    let iconClass = '';
    
    switch(options.type) {
        case 'success':
            iconClass = 'bg-green-100';
            iconHtml = '<i class="fas fa-check-circle text-4xl text-green-500"></i>';
            break;
        case 'error':
            iconClass = 'bg-red-100';
            iconHtml = '<i class="fas fa-times-circle text-4xl text-red-500"></i>';
            break;
        case 'warning':
            iconClass = 'bg-yellow-100';
            iconHtml = '<i class="fas fa-exclamation-triangle text-4xl text-yellow-500"></i>';
            break;
        case 'info':
            iconClass = 'bg-blue-100';
            iconHtml = '<i class="fas fa-info-circle text-4xl text-blue-500"></i>';
            break;
        case 'question':
            iconClass = 'bg-purple-100';
            iconHtml = '<i class="fas fa-question-circle text-4xl text-purple-500"></i>';
            break;
        default:
            iconClass = 'bg-gray-100';
            iconHtml = '<i class="fas fa-bell text-4xl text-gray-500"></i>';
    }
    
    icon.className = `w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center ${iconClass}`;
    icon.innerHTML = iconHtml;
    
    // Set message
    message.textContent = options.message || '';
    
    // Set buttons
    footer.innerHTML = '';
    
    if (options.confirmText) {
        const confirmBtn = document.createElement('button');
        confirmBtn.textContent = options.confirmText;
        confirmBtn.className = 'flex-1 bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300';
        confirmBtn.onclick = () => {
            if (options.onConfirm) options.onConfirm();
            closeModal();
        };
        footer.appendChild(confirmBtn);
    }
    
    if (options.cancelText) {
        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = options.cancelText;
        cancelBtn.className = 'flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 rounded-lg transition duration-300';
        cancelBtn.onclick = () => {
            if (options.onCancel) options.onCancel();
            closeModal();
        };
        footer.appendChild(cancelBtn);
    }
    
    if (!options.confirmText && !options.cancelText) {
        const okBtn = document.createElement('button');
        okBtn.textContent = 'OK';
        okBtn.className = 'w-full bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300';
        okBtn.onclick = closeModal;
        footer.appendChild(okBtn);
    }
    
    // Show modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('customModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Toast Functions
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    
    let bgClass = 'bg-blue-500';
    let icon = 'fa-info-circle';
    
    switch(type) {
        case 'success':
            bgClass = 'bg-green-500';
            icon = 'fa-check-circle';
            break;
        case 'error':
            bgClass = 'bg-red-500';
            icon = 'fa-times-circle';
            break;
        case 'warning':
            bgClass = 'bg-yellow-500';
            icon = 'fa-exclamation-triangle';
            break;
    }
    
    toast.className = `toast ${bgClass} text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px]`;
    toast.innerHTML = `
        <i class="fas ${icon} text-xl"></i>
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 200);
    }, duration);
}

// Shorthand functions
function showSuccess(message, callback) {
    showModal({
        type: 'success',
        title: 'Berhasil!',
        message: message,
        confirmText: 'OK',
        onConfirm: callback
    });
}

function showError(message, callback) {
    showModal({
        type: 'error',
        title: 'Error!',
        message: message,
        confirmText: 'OK',
        onConfirm: callback
    });
}

function showConfirm(message, onConfirm, onCancel) {
    showModal({
        type: 'question',
        title: 'Konfirmasi',
        message: message,
        confirmText: 'Ya',
        cancelText: 'Batal',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
}

function showInfo(message) {
    showModal({
        type: 'info',
        title: 'Informasi',
        message: message,
        confirmText: 'OK'
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('customModal');
    if (e.target === modal) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>   