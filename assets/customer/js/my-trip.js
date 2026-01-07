// Review Modal Functions
function openReviewModal(bookingCode) {
    document.getElementById(`review-modal-${bookingCode}`).classList.add('active');
}

function closeReviewModal(bookingCode) {
    document.getElementById(`review-modal-${bookingCode}`).classList.remove('active');
}

function setRating(element, bookingCode) {
    const rating = element.dataset.rating;
    document.getElementById(`rating-value-${bookingCode}`).value = rating;

    // Update star styling
    const container = document.getElementById(`rating-${bookingCode}`);
    const stars = container.querySelectorAll('.review-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function submitReview(event, bookingCode) {
    event.preventDefault();
    const rating = document.getElementById(`rating-value-${bookingCode}`).value;
    const message = document.getElementById(`message-${bookingCode}`).value;

    if (!message.trim()) {
        alert.error('Please write a review message');
        return;
    }

    // Send to backend
    fetch('/PROGNET/customer/api/submit-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            booking_code: bookingCode,
            rating: rating,
            message: message
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert.success('Review submitted successfully!');
                closeReviewModal(bookingCode);
                setTimeout(() => location.reload(), 1500);
            } else {
                alert.error(data.message || 'Failed to submit review');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert.error('Error submitting review');
        });
}

// Close modal when clicking outside
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('review-modal')) {
        event.target.classList.remove('active');
    }
});
