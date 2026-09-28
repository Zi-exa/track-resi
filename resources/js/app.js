const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');
const toggle = document.querySelector('[data-menu-toggle]');

function closeSidebar() {
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('open');
}

toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
    backdrop?.classList.toggle('open');
});
backdrop?.addEventListener('click', closeSidebar);
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeSidebar();
});

const scanInput = document.querySelector('[data-scan-form] input[name="tracking_number"]');
if (scanInput && document.querySelector('.alert-success')) {
    scanInput.value = '';
    scanInput.focus();
}
