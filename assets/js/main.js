// HostelHub Client-side Interactions & Validations
document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Show/hide password toggle on the login form
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    if (passwordInput && togglePassword) {
        const eyeIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function () {
            const shouldShow = passwordInput.type === 'password';

            // Toggle visibility without changing the password value.
            passwordInput.type = shouldShow ? 'text' : 'password';

            togglePassword.setAttribute(
                'aria-label',
                shouldShow ? 'Hide password' : 'Show password'
            );

            togglePassword.setAttribute('aria-pressed', String(shouldShow));

            // Update the eye icon.
            if (eyeIcon) {
                eyeIcon.classList.toggle('bi-eye', !shouldShow);
                eyeIcon.classList.toggle('bi-eye-slash', shouldShow);
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {}
        }, 5000);
    });

    // Dynamic search table filter helper
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        const targetTableSelector = input.getAttribute('data-table-search');
        const targetTable = document.querySelector(targetTableSelector);
        if (!targetTable) return;

        input.addEventListener('input', function () {
            const query = input.value.toLowerCase().trim();
            const rows = targetTable.querySelectorAll('tbody tr:not(.no-search-results)');
            let visibleCount = 0;
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            let noResultRow = targetTable.querySelector('.no-search-results');
            if (visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    const colCount = targetTable.querySelectorAll('thead th').length || 6;
                    noResultRow = document.createElement('tr');
                    noResultRow.className = 'no-search-results text-center py-4 text-muted';
                    noResultRow.innerHTML = `<td colspan="${colCount}" class="py-4 text-muted"><i class="bi bi-search me-2"></i>No records matching "<strong>${query}</strong>"</td>`;
                    const tbody = targetTable.querySelector('tbody');
                    if (tbody) tbody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const strong = noResultRow.querySelector('strong');
                    if (strong) strong.textContent = query;
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        });
    });

    // Print button trigger
    const printBtns = document.querySelectorAll('[data-action="print"]');
    printBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    // Confirmation on destructive actions
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const msg = btn.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});
