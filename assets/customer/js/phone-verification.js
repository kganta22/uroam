document.addEventListener('DOMContentLoaded', () => {
    const resendButton = document.getElementById('resend-button');
    let countdownEl = document.getElementById('countdown');
    const otpInput = document.getElementById('otp');
    let remaining = 60;
    let timerId;

    function tick() {
        if (!countdownEl || !resendButton) return;
        if (remaining <= 0) {
            resendButton.disabled = false;
            resendButton.textContent = 'Resend verification code';
            countdownEl.textContent = '0';
            return;
        }
        countdownEl.textContent = remaining;
        remaining -= 1;
        timerId = setTimeout(tick, 1000);
    }

    if (resendButton) {
        resendButton.addEventListener('click', () => {
            if (resendButton.disabled) return;
            // TODO: hook to resenFd endpoint
            if (timerId) clearTimeout(timerId);
            resendButton.disabled = true;
            remaining = 60;
            resendButton.innerHTML = 'Resend verification code in <span id="countdown">60</span> seconds';
            const newCountdown = resendButton.querySelector('#countdown');
            if (newCountdown) {
                countdownEl = newCountdown;
            }
            tick();
        });
    }

    if (otpInput) {
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D+/g, '').slice(0, 8);
        });
        otpInput.focus();
    }

    tick();
});
