/* ============================================
   PharmaTrust - Complete JavaScript File
   ============================================ */

$(document).ready(function () {

    /* ---------- Auto-hide Alerts ---------- */
    setTimeout(function () {
        $('.alert').fadeOut('slow', function () {
            $(this).remove();
        });
    }, 5000);

    /* ---------- Confirm Delete Actions ---------- */
    $(document).on('click', '.delete-confirm, a[onclick*="return confirm"]', function (e) {
        const confirmMessage = $(this).data('confirm') || 'Are you sure you want to delete this item?';

        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });

    /* ---------- Enable Tooltips ---------- */
    $('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip();

    /* ---------- Table Search ---------- */
    $('.search-input').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        const tableId = $(this).data('table');

        if (tableId) {
            $('#' + tableId + ' tbody tr').filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        }
    });

    /* ---------- Print ---------- */
    $('.print-btn').on('click', function () {
        window.print();
    });

    /* ---------- Modal Reset ---------- */
    $('.modal').on('hidden.bs.modal', function () {
        const form = $(this).find('form')[0];
        if (form) form.reset();
        $(this).find('.alert').remove();
    });

});

/* ============================================
   EXPORT CSV
   ============================================ */

$(document).on('click', '.export-csv', function () {
    const tableId = $(this).data('table');

    if (!tableId) {
        alert('Please specify a table ID');
        return;
    }

    let csv = [];
    $('#' + tableId + ' tr').each(function () {
        let row = [];

        $(this).find('th, td').each(function () {
            const text = $(this).text().replace(/"/g, '""').trim();
            row.push('"' + text + '"');
        });

        csv.push(row.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'export_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();

    URL.revokeObjectURL(url);
});

/* ============================================
   UTILITY FUNCTIONS
   ============================================ */

function formatCurrency(amount) {
    if (isNaN(amount)) return 'GH₵0.00';
    return 'GH₵' + parseFloat(amount).toFixed(2);
}

function isValidDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

function calculateAge(dob) {
    const birthDate = new Date(dob);
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age;
}

function showLoading(message = 'Loading...') {
    return `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">${message}</span>
            </div>
            <p class="mt-2 text-muted">${message}</p>
        </div>
    `;
}

/* ---------- Toast System ---------- */

function showToast(type, message) {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const toast = $(`
        <div class="toast align-items-center border-0"
             style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;background:${colors[type]};color:white;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${icons[type]} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    $('body').append(toast);

    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();

    setTimeout(() => toast.remove(), 5000);
}

function handleAjaxError(xhr, status, error) {
    console.error('AJAX Error:', status, error);

    let message = 'An error occurred. Please try again.';

    try {
        const response = JSON.parse(xhr.responseText);
        if (response.message) message = response.message;
    } catch (e) {}

    showToast('error', message);
}

/* ============================================
   SALES FUNCTIONS
   ============================================ */

function updateSaleTotal() {
    let total = 0;

    $('.item-row').each(function () {
        const select = $(this).find('.medication-select');
        const qty = $(this).find('.quantity-input');

        if (select.val() && qty.val()) {
            const price = parseFloat(select.find('option:selected').data('price')) || 0;
            total += price * (parseInt(qty.val()) || 0);
        }
    });

    $('#saleTotal').text(formatCurrency(total));
}

function addSaleItemRow() {
    const container = $('#itemsContainer');
    const index = container.children('.item-row').length;

    const row = container.children('.item-row:first').clone();

    row.find('select').val('').attr('name', `items[${index}][medication_id]`);
    row.find('input').val('').attr('name', `items[${index}][quantity]`);

    container.append(row);
    updateSaleTotal();
}

/* ============================================
   EVENT HANDLERS
   ============================================ */

$(document).on('click', '#addItemBtn', addSaleItemRow);

$(document).on('input change', '.medication-select, .quantity-input', updateSaleTotal);

$(document).on('click', '.remove-item', function () {
    const row = $(this).closest('.item-row');

    if (row.siblings('.item-row').length > 0) {
        row.remove();
        updateSaleTotal();
    } else {
        showToast('warning', 'At least one item is required!');
    }
});

/* ---------- Medication Stock ---------- */

$(document).on('change', '.medication-select', function () {
    const select = $(this);
    const stock = parseInt(select.find('option:selected').data('stock')) || 0;

    const qtyInput = select.closest('.item-row').find('.quantity-input');
    qtyInput.attr('max', stock);

    let warning = select.closest('.item-row').find('.stock-warning');

    if (!warning.length) {
        warning = $('<small class="stock-warning d-block mt-1"></small>');
        select.closest('.col-md-6').append(warning);
    }

    if (stock === 0) {
        warning.text('⚠️ Out of stock!').addClass('text-danger');
    } else {
        warning.text('Available stock: ' + stock).removeClass('text-danger');
    }
});

/* ============================================
   FORM VALIDATION
   ============================================ */

$(document).on('submit', 'form', function (e) {
    let valid = true;

    $(this).find('.quantity-input').each(function () {
        const max = parseInt($(this).attr('max'));
        const val = parseInt($(this).val());

        if (max && val > max) {
            alert(`Quantity cannot exceed stock (${max})`);
            valid = false;
            return false;
        }
    });

    if (!valid) e.preventDefault();
});

    /* ---------- Animated Counters & Stagger Reveal ---------- */
    function animateCounters() {
        $('.stat-value').each(function () {
            const el = $(this);
            const raw = el.data('target') || el.text();
            const target = parseInt(String(raw).replace(/[^0-9]/g, '')) || 0;
            let current = 0;
            const duration = 1200;
            const stepTime = 30;
            const step = Math.ceil(target / (duration / stepTime) || 1);

            if (target <= 0) return el.text(target);

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    el.text(target);
                    el.addClass('animate');
                    clearInterval(timer);
                } else {
                    el.text(current);
                }
            }, stepTime);
        });
    }

    function staggerReveal() {
        $('.stagger-parent').each(function () {
            const parent = $(this);
            if (parent.hasClass('visible')) return;
            parent.addClass('visible');
            parent.children().each(function (i) {
                const child = $(this);
                setTimeout(() => child.addClass('float-up'), i * 80);
            });
        });
    }

    // Run the animations shortly after load
    setTimeout(() => {
        animateCounters();
        staggerReveal();
    }, 200);

    /* ---------- Back to top button ---------- */
    const back = $('<button id="backToTop" class="btn btn-primary" style="position:fixed;right:20px;bottom:24px;z-index:9998;display:none;">\u2191</button>');
    $('body').append(back);
    $(window).on('scroll', function () {
        if ($(window).scrollTop() > 300) back.fadeIn(); else back.fadeOut();
    });
    back.on('click', function () { $('html,body').animate({scrollTop:0}, 400); });

    /* ---------- Smooth anchor scrolling ---------- */
    $(document).on('click', 'a[href^="#"]', function (e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 400);
        }
    });

/* ============================================
   STOCK INDICATORS
   ============================================ */

function updateStockIndicators() {
    $('.stock-level').each(function () {
        const stock = parseInt($(this).data('stock'));
        const reorder = parseInt($(this).data('reorder')) || 10;

        if (stock <= reorder) {
            $(this)
                .removeClass('text-success text-warning')
                .addClass('text-danger')
                .html('<i class="fas fa-exclamation-circle"></i> Low Stock');
        } else if (stock <= reorder * 2) {
            $(this)
                .removeClass('text-success text-danger')
                .addClass('text-warning')
                .html('<i class="fas fa-clock"></i> Medium Stock');
        } else {
            $(this)
                .removeClass('text-warning text-danger')
                .addClass('text-success')
                .html('<i class="fas fa-check-circle"></i> In Stock');
        }
    });
}

$(document).ready(updateStockIndicators);

/* ============================================
   EXPORT PDF (PRINT)
   ============================================ */

$(document).on('click', '.export-pdf', function () {
    const target = $(this).data('element') || 'body';

    const content = $(target).html();
    const original = document.body.innerHTML;

    document.body.innerHTML = content;
    window.print();
    document.body.innerHTML = original;

    location.reload();
});