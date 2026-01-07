document.addEventListener('DOMContentLoaded', function () {
    const meetingPointInput = document.getElementById('meeting_point');
    const usePointsCheckbox = document.getElementById('use_points');
    const payBtn = document.getElementById('pay_btn');
    const pointsDeductionRow = document.getElementById('points_discount_row');
    const pointsCustomization = document.getElementById('points_customization');
    const pointsSlider = document.getElementById('points_slider');
    const pointsInput = document.getElementById('points_input');

    const grossRateValue = parseFloat(document.getElementById('gross_rate_value').value);
    const pointsAvailable = parseInt(document.getElementById('points_available').value);
    const pointsToRupiah = parseInt(document.getElementById('points_to_rupiah').value);
    const bookingCode = document.getElementById('booking_code_value').value;

    // Calculate max points that can be used
    // 1 point = 1 rupiah, so max points = grossRateValue - 1000 (leave at least 1000 to pay)
    const maxPointsByAmount = Math.max(0, Math.floor(grossRateValue - 1000));
    const maxPointsAllowed = Math.min(pointsAvailable, maxPointsByAmount);

    // Update slider max value
    pointsSlider.max = maxPointsAllowed;

    let currentTotal = grossRateValue;
    let pointsUsed = 0;
    let pointsUsedAmount = 0; // in rupiah
    let usePoints = false;
    let snapOrderId = null; // used to verify payment status on finalize

    // Meeting point validation
    meetingPointInput.addEventListener('change', validateForm);

    // Points checkbox
    usePointsCheckbox.addEventListener('change', function () {
        usePoints = this.checked;

        if (usePoints) {
            pointsCustomization.style.display = 'block';
            pointsDeductionRow.style.display = 'flex';
            updatePointsDisplay();
        } else {
            pointsCustomization.style.display = 'none';
            pointsDeductionRow.style.display = 'none';
            pointsUsed = 0;
            pointsUsedAmount = 0;
            currentTotal = grossRateValue;
            updateTotalDisplay();
        }
        validateForm();
    });

    // Points slider
    pointsSlider.addEventListener('input', function () {
        const pointsValue = parseInt(this.value);
        pointsInput.value = pointsValue;
        updatePointsFromValue(pointsValue);
    });

    // Points input
    pointsInput.addEventListener('input', function () {
        let pointsValue = parseInt(this.value) || 0;

        // Validate max points allowed
        if (pointsValue > maxPointsAllowed) {
            pointsValue = maxPointsAllowed;
            this.value = pointsValue;
        }

        // Validate negative
        if (pointsValue < 0) {
            pointsValue = 0;
            this.value = 0;
        }

        pointsSlider.value = pointsValue;
        updatePointsFromValue(pointsValue);
    });

    function updatePointsFromValue(pointsValue) {
        if (!usePoints) return;

        pointsUsed = pointsValue;
        const pointsInRupiah = pointsValue * 1;
        const minCharge = 1000; // leave at least IDR 1,000 to keep transaction valid
        const maxDeduction = Math.max(0, grossRateValue - minCharge);

        // Deduction is capped to ensure total is at least 1000
        pointsUsedAmount = Math.min(pointsInRupiah, maxDeduction);
        currentTotal = grossRateValue - pointsUsedAmount;

        document.getElementById('points_amount_text').textContent = pointsUsed;
        document.getElementById('points_rupiah_text').textContent = formatNumber(pointsUsedAmount);
        document.getElementById('display_points_discount').textContent =
            `- IDR ${formatNumber(pointsUsedAmount)}`;

        updateTotalDisplay();
        validateForm();
    }

    function updateTotalDisplay() {
        document.getElementById('total_amount').textContent =
            `IDR ${formatNumber(currentTotal)}`;
        document.getElementById('final_amount').value = currentTotal;

        // Calculate earned points (13% of final total amount after discount)
        const earnedPoints = Math.floor(currentTotal * 0.13);
        document.getElementById('points_earned_display').textContent = formatNumber(earnedPoints);
    }

    function validateForm() {
        const meetingPoint = meetingPointInput.value.trim();
        const isValid = meetingPoint.length > 0;

        payBtn.disabled = !isValid;
    }

    // Pay button click
    payBtn.addEventListener('click', async function () {
        const meetingPoint = meetingPointInput.value.trim();

        if (!meetingPoint) {
            alert.warning('Please enter a meeting point');
            return;
        }

        // Disable button
        payBtn.disabled = true;

        try {
            // Call process-payment endpoint
            const response = await fetch('/PROGNET/customer/orders/process-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_code: bookingCode,
                    meeting_point: meetingPoint,
                    use_points: usePoints,
                    points_used: pointsUsedAmount
                })
            });

            const rawText = await response.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (e) {
                data = null;
            }

            console.log('Payment response:', response.status, data || rawText);
            if (data && data.order_id) {
                snapOrderId = data.order_id;
            }

            if (!data || (!response.ok || !data.success)) {
                // If there's debug info, log it
                if (data && data.debug) {
                    console.error('Debug info:', data.debug);
                    if (data.debug.response && data.debug.response.error_messages) {
                        console.error('Midtrans error:', data.debug.response.error_messages);
                    }
                }
                alert.error((data && data.message) || 'Failed to process payment');
                payBtn.disabled = false;
                return;
            }

            // Get snap token and trigger payment
            if (window.snap) {
                snap.pay(data.token, {
                    onSuccess: async function (result) {
                        // Payment success: finalize booking server-side then redirect
                        try {
                            const orderIdToUse = result.order_id || snapOrderId;
                            if (!orderIdToUse) {
                                alert.error('Missing order id for verification');
                                payBtn.disabled = false;
                                return;
                            }

                            const finalizeResp = await fetch('/PROGNET/customer/orders/finalize-payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    booking_code: bookingCode,
                                    order_id: orderIdToUse,
                                    meeting_point: meetingPoint,
                                    use_points: usePoints,
                                    points_used: pointsUsedAmount
                                })
                            });
                            const finalizeText = await finalizeResp.text();
                            let finalizeData;
                            try { finalizeData = JSON.parse(finalizeText); } catch (e) { finalizeData = null; }

                            if (!finalizeData || !finalizeData.success) {
                                alert.error((finalizeData && finalizeData.message) || 'Failed to finalize booking');
                                payBtn.disabled = false;
                                return;
                            }

                            alert.success('Payment successful!');
                            setTimeout(() => {
                                window.location.href = finalizeData.redirect_url;
                            }, 1200);
                        } catch (err) {
                            console.error('Finalize error:', err);
                            alert.error('Failed to finalize booking');
                            payBtn.disabled = false;
                        }
                    },
                    onPending: function (result) {
                        // Payment pending; do not finalize
                        alert.warning('Payment is pending, please wait for confirmation');
                        setTimeout(() => {
                            window.location.href = '/PROGNET/customer/orders/payment.php';
                        }, 2000);
                    },
                    onError: function (result) {
                        // Payment error
                        alert.error('Payment failed, please try again');
                        payBtn.disabled = false;
                    },
                    onClose: function () {
                        // User closes payment modal; keep order_request intact so retry works
                        payBtn.disabled = false;
                    }
                });
            } else {
                alert.error('Payment gateway not loaded');
                payBtn.disabled = false;
            }

        } catch (err) {
            console.error('Network error details:', err);
            alert.error('Network error: ' + err.message);
            payBtn.disabled = false;
        }
    });

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Initial validation
    validateForm();
});
