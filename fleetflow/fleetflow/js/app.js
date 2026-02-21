// FleetFlow - Main JS

// Modal handling
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(a => {
        setTimeout(() => {
            a.style.opacity = '0';
            a.style.transition = 'opacity 0.5s';
            setTimeout(() => a.remove(), 500);
        }, 4000);
    });
});

// Confirm delete
function confirmDelete(msg) {
    return confirm(msg || 'Are you sure you want to delete this record?');
}

// Filter table rows
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Validate cargo weight on trip form
document.addEventListener('DOMContentLoaded', function() {
    const vehicleSelect = document.getElementById('vehicle_id');
    const cargoInput = document.getElementById('cargo_weight');
    const submitBtn = document.getElementById('trip-submit');
    
    if (vehicleSelect && cargoInput) {
        function validateLoad() {
            const maxCap = parseFloat(vehicleSelect.selectedOptions[0]?.dataset.capacity || 999999);
            const cargo = parseFloat(cargoInput.value || 0);
            const warning = document.getElementById('capacity-warning');
            if (cargo > maxCap) {
                if (warning) warning.style.display = 'flex';
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (warning) warning.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        }
        vehicleSelect.addEventListener('change', validateLoad);
        cargoInput.addEventListener('input', validateLoad);
    }
});
