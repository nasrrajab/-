document.addEventListener('DOMContentLoaded', () => {
    // 1. Modal Trigger Functions
    const modalBackdrops = document.querySelectorAll('.modal-backdrop');

    modalBackdrops.forEach(backdrop => {
        // Close modal if clicking outside content
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal(backdrop);
            }
        });

        // Find close buttons inside modal
        const closeBtns = backdrop.querySelectorAll('[data-close-modal]');
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => closeModal(backdrop));
        });
    });

    // 2. Show PHP alert messages using native alert()
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const message = alert.textContent.trim();
        if (message) {
            window.alert(message);
        }
        alert.style.display = 'none'; // hide the HTML box after showing
    });

    // 3. Theme Toggle removed – site uses a single static light theme.

    // 4. Global Interceptor for data-confirm forms
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.hasAttribute('data-confirm')) {
            const message = form.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                e.preventDefault(); // user clicked Cancel
            }
        }
    });

    // 5. Global Interceptor for data-confirm links
    document.addEventListener('click', (e) => {
        if (!e.target || typeof e.target.closest !== 'function') return;
        const link = e.target.closest('a[data-confirm]');
        if (link) {
            const message = link.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                e.preventDefault(); // user clicked Cancel
            }
        }
    });
});

// Helper open modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden'; // Lock background scroll
    }
}

// Helper close modal
function closeModal(modalElement) {
    if (typeof modalElement === 'string') {
        modalElement = document.getElementById(modalElement);
    }
    if (modalElement) {
        modalElement.classList.remove('open');
        document.body.style.overflow = ''; // Unlock scroll
    }
}