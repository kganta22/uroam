// ===== INVOICE FUNCTIONALITY =====
function viewInvoice(event, bookingCode, invoicePath, isAdmin = false) {
    // If invoice already exists, open it directly
    if (invoicePath) {
        window.open(invoicePath, '_blank');
        return;
    }

    // If no invoice, generate it first
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<span>GENERATING...</span>';
    button.disabled = true;

    const formData = new FormData();
    formData.append('booking_code', bookingCode);

    // Determine API endpoint based on whether it's admin or customer
    const apiEndpoint = isAdmin
        ? '/PROGNET/admin/api/generate-invoice.php'
        : '/PROGNET/customer/api/generate-invoice.php';

    fetch(apiEndpoint, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(text => {
            button.innerHTML = originalText;
            button.disabled = false;

            try {
                const data = JSON.parse(text);

                if (data.success) {
                    // Open the generated invoice
                    window.open(data.invoice_path, '_blank');

                    // Reload page after 1 second to update the table with new invoice path
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Failed to generate invoice: ' + data.message);
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                alert('Error: ' + text);
            }
        })
        .catch(error => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert('Error generating invoice. Please try again.');
            console.error('Fetch Error:', error);
        });
}

// ===== DATE FILTER FUNCTIONALITY =====
const tabs = document.querySelectorAll('.tab');
const pages = document.querySelectorAll('.page');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        pages.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.page).classList.add('active');
    });
});

// Close info box
const closeBtn = document.querySelector('.info-box .close');
if (closeBtn) {
    closeBtn.onclick = () => closeBtn.parentElement.style.display = 'none';
}

// Initialize Flatpickr for date range
const dateInput = document.getElementById('dateRange');
const dateStart = document.getElementById('date_start');
const dateEnd = document.getElementById('date_end');
const clearLink = document.querySelector('.bk-filter-block .bk-clear');

if (dateInput) {
    flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [
            dateStart.value || null,
            dateEnd.value || null
        ],
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                dateStart.value = instance.formatDate(selectedDates[0], "Y-m-d");
                dateEnd.value = instance.formatDate(selectedDates[1], "Y-m-d");
                if (clearLink) clearLink.style.display = 'inline';
                document.getElementById('financeFilterForm').submit();
            }
        },
        onClear: function () {
            dateStart.value = '';
            dateEnd.value = '';
            if (clearLink) clearLink.style.display = 'none';
            document.getElementById('financeFilterForm').submit();
        }
    });
}

// Clear button handler
function clearFinanceDate() {
    if (!dateInput || !dateStart || !dateEnd) return;
    dateInput._flatpickr.clear();
    dateStart.value = '';
    dateEnd.value = '';
    if (clearLink) clearLink.style.display = 'none';
    document.getElementById('financeFilterForm').submit();
}
