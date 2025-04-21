// Main JavaScript file for Pet Care Platform

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Service booking form handling
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const servicePrice = parseFloat(document.getElementById('servicePrice').value);
            const bookingDate = document.getElementById('bookingDate').value;
            const startTime = document.getElementById('startTime').value;
            
            if (!validateBookingDateTime(bookingDate, startTime)) {
                e.preventDefault();
                alert('Please select a future date and time for your booking.');
            }
        });
    }
    
    // Service price calculator
    const calculatePrice = function() {
        const basePrice = parseFloat(document.getElementById('basePrice').value) || 0;
        const duration = parseInt(document.getElementById('duration').value) || 0;
        const totalPrice = basePrice * (duration / 60);
        
        document.getElementById('calculatedPrice').textContent = '$' + totalPrice.toFixed(2);
    };
    
    const durationInput = document.getElementById('duration');
    if (durationInput) {
        durationInput.addEventListener('input', calculatePrice);
        calculatePrice(); // Calculate initial price
    }
    
    // Date and time validation
    function validateBookingDateTime(date, time) {
        const now = new Date();
        const bookingDateTime = new Date(date + 'T' + time);
        return bookingDateTime > now;
    }
    
    // Rating system
    const ratingInputs = document.querySelectorAll('.rating-input');
    if (ratingInputs.length > 0) {
        ratingInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const ratingValue = this.value;
                const stars = this.parentElement.querySelectorAll('.rating-star');
                
                stars.forEach(function(star, index) {
                    if (index < ratingValue) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            });
        });
    }
    
    // Service availability checker
    const checkAvailability = function(providerId, date) {
        fetch(`${APP_URL}/services/check_availability.php?provider_id=${providerId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                const availabilityContainer = document.getElementById('availabilityTimes');
                availabilityContainer.innerHTML = '';
                
                if (data.available_slots && data.available_slots.length > 0) {
                    data.available_slots.forEach(slot => {
                        const timeBtn = document.createElement('button');
                        timeBtn.type = 'button';
                        timeBtn.className = 'btn btn-outline-primary me-2 mb-2 time-slot-btn';
                        timeBtn.textContent = slot.start_time;
                        timeBtn.dataset.startTime = slot.start_time;
                        timeBtn.dataset.endTime = slot.end_time;
                        
                        timeBtn.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot-btn').forEach(btn => {
                                btn.classList.remove('active');
                            });
                            this.classList.add('active');
                            
                            document.getElementById('startTime').value = this.dataset.startTime;
                            document.getElementById('endTime').value = this.dataset.endTime;
                        });
                        
                        availabilityContainer.appendChild(timeBtn);
                    });
                } else {
                    availabilityContainer.innerHTML = '<p class="text-danger">No available slots on this date.</p>';
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
            });
    };
    
    const dateInput = document.getElementById('bookingDate');
    const providerId = document.getElementById('providerId');
    
    if (dateInput && providerId) {
        dateInput.addEventListener('change', function() {
            checkAvailability(providerId.value, this.value);
        });
        
        // Check initial availability if date is pre-selected
        if (dateInput.value) {
            checkAvailability(providerId.value, dateInput.value);
        }
    }
});