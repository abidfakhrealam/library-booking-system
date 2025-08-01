// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            let fade = new bootstrap.Alert(alert);
            fade.close();
        });
    }, 5000);
    
    // Form validation
    let forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Booking time selection
    let timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(function(slot) {
        slot.addEventListener('click', function() {
            timeSlots.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('slot_id').value = this.dataset.slotId;
        });
    });
});

// AJAX functions
function checkCubicleAvailability(cubicleId, startTime, endTime) {
    return fetch(`/api/cubicle.php?id=${cubicleId}&start=${startTime}&end=${endTime}`)
        .then(response => response.json());
}

function submitBooking(formData) {
    return fetch('/api/booking.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json());
}