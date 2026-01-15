// Chart.js demo replacement for dashboard-demo.js
// Replace with real data as needed

document.addEventListener('DOMContentLoaded', function() {
    // Area Chart Example
    var areaCtx = document.getElementById('area-chart').getContext('2d');
    new Chart(areaCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Example Area',
                data: [65, 59, 80, 81, 56, 55, 40],
                fill: true,
                backgroundColor: 'rgba(102,126,234,0.2)',
                borderColor: 'rgba(102,126,234,1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Bar Chart Example
    var barCtx = document.getElementById('bar-chart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['2016', '2017', '2018', '2019', '2020', '2021'],
            datasets: [{
                label: 'Example Bar',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(16,185,129,0.7)'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Donut Chart Example
    var donutCtx = document.getElementById('donut-chart').getContext('2d');
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Red', 'Blue', 'Yellow'],
            datasets: [{
                label: 'Example Donut',
                data: [300, 50, 100],
                backgroundColor: [
                    'rgba(239,68,68,0.7)',
                    'rgba(59,130,246,0.7)',
                    'rgba(253,224,71,0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
