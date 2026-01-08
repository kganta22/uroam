// Book.php Booking System JavaScript

// Initialize with product prices data
let selectedDate = null;
let adultCount = 1;
let childCount = 0;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    updatePriceDisplay();

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements
    document.querySelectorAll('.options-section, .price-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Update quantity
function updateQuantity(type, change) {
    if (type === 'adult') {
        adultCount = Math.max(0, adultCount + change);
        document.getElementById('adultQuantity').textContent = adultCount;
    } else {
        childCount = Math.max(0, childCount + change);
        document.getElementById('childQuantity').textContent = childCount;
    }
    updatePriceDisplay();
}

// Update price display
function updatePriceDisplay() {
    const optionSelect = document.getElementById('optionType');
    const selectedOption = optionSelect.options[optionSelect.selectedIndex];
    const optionType = selectedOption.dataset.type;
    const adultPrice = parseFloat(selectedOption.dataset.adultPrice);
    const childPrice = parseFloat(selectedOption.dataset.childPrice);

    // Update option type display
    document.getElementById('selectedOptionType').textContent = optionType.charAt(0).toUpperCase() + optionType.slice(1);

    // Build price items
    let priceItemsHtml = '';
    let total = 0;

    if (adultCount > 0) {
        const adultTotal = adultCount * adultPrice;
        total += adultTotal;
        priceItemsHtml += `
            <div class="price-row">
                <span>${adultCount} x Adult</span>
                <span>Rp ${formatNumber(adultTotal)}</span>
            </div>
        `;
    }

    if (childCount > 0) {
        const childTotal = childCount * childPrice;
        total += childTotal;
        priceItemsHtml += `
            <div class="price-row">
                <span>${childCount} x Children</span>
                <span>Rp ${formatNumber(childTotal)}</span>
            </div>
        `;
    }

    document.getElementById('priceItems').innerHTML = priceItemsHtml;
    document.getElementById('totalAmount').textContent = 'Rp ' + formatNumber(total);

    // Update summary
    const totalPax = adultCount + childCount;
    document.getElementById('summaryOption').textContent = `${optionType.charAt(0).toUpperCase() + optionType.slice(1)} - ${totalPax} pax`;

    // Enable/disable continue button based on passenger count and date selection
    const continueBtn = document.querySelector('.continue-btn');
    if ((adultCount === 0 && childCount === 0) || !selectedDate) {
        continueBtn.disabled = true;
        continueBtn.style.opacity = '0.5';
        continueBtn.style.cursor = 'not-allowed';
    } else {
        continueBtn.disabled = false;
        continueBtn.style.opacity = '1';
        continueBtn.style.cursor = 'pointer';
    }
}

// Format number with dots
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Option type change
document.addEventListener('DOMContentLoaded', function () {
    const optionSelect = document.getElementById('optionType');
    if (optionSelect) {
        optionSelect.addEventListener('change', updatePriceDisplay);
    }
});

// Date modal functions
function openDateModal() {
    const dateInput = document.getElementById('activityDate');
    // Set minimum date to today (current date when opening modal)
    const today = new Date();
    const minDate = today.toISOString().split('T')[0];
    dateInput.setAttribute('min', minDate);
    dateInput.value = ''; // Clear previous value
    document.getElementById('dateModal').style.display = 'flex';
}

function closeDateModal() {
    document.getElementById('dateModal').style.display = 'none';
}

function confirmDate() {
    const dateInput = document.getElementById('activityDate');
    const timeInput = document.getElementById('activityTime');

    // Validate date input
    if (!dateInput.value) {
        alert.warning('Please select a date');
        return;
    }

    // Validate time input
    if (!timeInput.value) {
        alert.warning('Please select a time');
        return;
    }

    const selectedDateObj = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDateObj.setHours(0, 0, 0, 0);

    // Validate that selected date is not in the past
    if (selectedDateObj < today) {
        alert.warning('Please select a date that is today or later');
        return;
    }

    // Format as YYYY-MM-DD HH:MM for PHP compatibility
    const formatted = `${selectedDateObj.getFullYear()}-${String(selectedDateObj.getMonth() + 1).padStart(2, '0')}-${String(selectedDateObj.getDate()).padStart(2, '0')} ${timeInput.value}`;
    selectedDate = formatted;
    
    // Display format: MM-DD-YYYY HH:MM for user
    const displayFormat = `${String(selectedDateObj.getMonth() + 1).padStart(2, '0')}-${String(selectedDateObj.getDate()).padStart(2, '0')}-${selectedDateObj.getFullYear()} ${timeInput.value}`;
    document.getElementById('summaryDate').textContent = displayFormat;
    closeDateModal();
    updatePriceDisplay(); // Update button state after date selection
}

document.addEventListener('DOMContentLoaded', function () {
    const selectDateBtn = document.getElementById('selectDateBtn');
    if (selectDateBtn) {
        selectDateBtn.addEventListener('click', openDateModal);
    }
});

// Proceed to booking
function proceedToBooking() {
    if (adultCount === 0 && childCount === 0) {
        alert.warning('Please select at least one passenger');
        return;
    }

    if (!selectedDate) {
        alert.warning('Please select an activity date');
        return;
    }

    // Show confirmation modal with booking summary
    const optionSelect = document.getElementById('optionType');
    const selectedOption = optionSelect.options[optionSelect.selectedIndex];
    const optionType = selectedOption.dataset.type;
    const adultPrice = parseFloat(selectedOption.dataset.adultPrice);
    const childPrice = parseFloat(selectedOption.dataset.childPrice);

    // Calculate total
    let total = (adultCount * adultPrice) + (childCount * childPrice);

    // Build passenger string
    let passengerText = '';
    if (adultCount > 0) passengerText += `${adultCount} Adult`;
    if (childCount > 0) {
        if (passengerText) passengerText += ' + ';
        passengerText += `${childCount} Child`;
    }

    // Update confirmation modal
    document.getElementById('confirmOptionType').textContent = optionType.charAt(0).toUpperCase() + optionType.slice(1);
    document.getElementById('confirmPassengers').textContent = passengerText;
    document.getElementById('confirmDate').textContent = selectedDate;
    document.getElementById('confirmTotalPrice').textContent = 'Rp ' + formatNumber(total);

    // Show modal
    document.getElementById('confirmationModal').style.display = 'flex';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
}

async function confirmBooking() {
    const optionSelect = document.getElementById('optionType');
    const selectedOptionId = optionSelect.value;
    const productId = document.querySelector('[data-product-id]')?.getAttribute('data-product-id');

    const payload = {
        product_id: Number(productId),
        price_id: Number(selectedOptionId),
        adult_count: adultCount,
        child_count: childCount,
        activity_date: selectedDate
    };

    const confirmBtn = document.querySelector('.btn-confirm');
    if (confirmBtn) confirmBtn.disabled = true;

    try {
        const res = await fetch('/PROGNET/customer/orders/create-request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
            alert.error(data.message || 'Failed to create order request');
            if (confirmBtn) confirmBtn.disabled = false;
            return;
        }

        // Success: show alert and redirect to reviews
        alert.success('Your booking has been created successfully!');
        setTimeout(() => {
            window.location.href = data.redirect || '/PROGNET/customer/orders/reviews.php';
        }, 1500);
    } catch (err) {
        alert.error('Network error, please try again');
        if (confirmBtn) confirmBtn.disabled = false;
    }
}

// Close modal when clicking outside
window.onclick = function (event) {
    const dateModal = document.getElementById('dateModal');
    if (event.target === dateModal) {
        closeDateModal();
    }
}
