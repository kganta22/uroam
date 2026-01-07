// Initialize Flatpickr for date range
const dateInput = document.getElementById('dateRange');
const dateStart = document.getElementById('date_start');
const dateEnd = document.getElementById('date_end');
const clearLink = document.querySelector('.an-date-block .bk-clear');

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
            document.getElementById('filterForm').submit();
        }
    },
    onClear: function () {
        dateStart.value = '';
        dateEnd.value = '';
        if (clearLink) clearLink.style.display = 'none';
        document.getElementById('filterForm').submit();
    }
});

// Clear button handler
function clearAnalyticsDate() {
    if (!dateInput || !dateStart || !dateEnd) return;
    dateInput._flatpickr.clear();
    dateStart.value = '';
    dateEnd.value = '';
    if (clearLink) clearLink.style.display = 'none';
    document.getElementById('filterForm').submit();
}

// Revenue + Tickets (Line Chart)
const analytics = window.analyticsData || {};

const revenueCtx = document.getElementById('revenueChart');
const marketCtx = document.getElementById('marketChart');

const line = analytics.line || {};
const lineLabels = line.labels && line.labels.length ? line.labels : [];
const lineRevenue = line.revenue && line.revenue.length ? line.revenue : [];
const lineTickets = line.tickets && line.tickets.length ? line.tickets : [];

if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [
                {
                    label: 'Revenue',
                    data: lineRevenue,
                    borderColor: '#2563eb',
                    backgroundColor: 'transparent',
                    tension: 0.3
                },
                {
                    label: 'Tickets',
                    data: lineTickets,
                    borderColor: '#f97316',
                    backgroundColor: 'transparent',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Market Revenue (Bar Chart)
const market = analytics.market || {};
const marketLabels = market.labels && market.labels.length ? market.labels : [];
const marketRevenue = market.revenue && market.revenue.length ? market.revenue : [];

if (marketCtx) {
    new Chart(marketCtx, {
        type: 'bar',
        data: {
            labels: marketLabels,
            datasets: [
                {
                    label: 'Revenue',
                    data: marketRevenue,
                    backgroundColor: '#1d9bf0'
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}
