document.querySelectorAll(".bk-detail-btn").forEach(btn => {
    btn.addEventListener("click", function () {

        const detailBox = this.parentElement.nextElementSibling;
        const rejectBox = detailBox.nextElementSibling;

        // tutup reject jika terbuka
        if (rejectBox) rejectBox.style.display = "none";

        if (detailBox.style.display === "block") {
            detailBox.style.display = "none";
            this.textContent = "Show details";
        } else {
            detailBox.style.display = "block";
            this.textContent = "Hide details";
        }
    });
});

document.querySelectorAll(".bk-reject-btn").forEach(btn => {
    btn.addEventListener("click", function () {

        const detailBox = this.parentElement.nextElementSibling;
        const rejectBox = detailBox.nextElementSibling;

        // tutup detail
        detailBox.style.display = "none";

        // toggle reject
        rejectBox.style.display =
            rejectBox.style.display === "block" ? "none" : "block";
    });
});

document.querySelectorAll(".bk-rejected-view").forEach(btn => {
    btn.addEventListener("click", function () {

        const detailBox = this.parentElement.nextElementSibling;
        const rejectInfo = detailBox.nextElementSibling;

        // tutup detail
        detailBox.style.display = "none";

        // toggle reject info
        rejectInfo.style.display =
            rejectInfo.style.display === "block" ? "none" : "block";
    });
});

function initDateRangePicker(rangeInputId, startInputId, endInputId) {
    const startEl = document.getElementById(startInputId);
    const endEl = document.getElementById(endInputId);

    const startVal = startEl.value;
    const endVal = endEl.value;

    flatpickr(rangeInputId, {
        mode: "range",

        dateFormat: "Y-m-d",

        altInput: true,
        altFormat: "M j, Y",

        defaultDate: (startVal && endVal) ? [startVal, endVal] : null,

        onClose(selectedDates) {
            if (selectedDates.length !== 2) return;

            const formatLocalDate = d => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, "0");
                const day = String(d.getDate()).padStart(2, "0");
                return `${year}-${month}-${day}`;
            };

            startEl.value = formatLocalDate(selectedDates[0]);
            endEl.value = formatLocalDate(selectedDates[1]);

            document.getElementById("filterForm").submit();
        }
    });
}



document.addEventListener("DOMContentLoaded", function () {
    initDateRangePicker(
        "#purchaseRange",
        "purchase_start",
        "purchase_end"
    );

    initDateRangePicker(
        "#activityRange",
        "activity_start",
        "activity_end"
    );
});

function clearDateFilter(type) {
    document.getElementById(type + "_start").value = "";
    document.getElementById(type + "_end").value = "";

    document.getElementById("filterForm").submit();
}
