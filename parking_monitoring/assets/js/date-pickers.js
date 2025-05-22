document.addEventListener('DOMContentLoaded', function() {
    // Common flatpickr configuration
    const flatpickrDefaults = {
        enableTime: false,
        dateFormat: "Y-m-d",
        animate: true,
        locale: {
            firstDayOfWeek: 1
        }
    };
    
    // Rental date pickers (long-term)
    if (document.getElementById('rentalStartDate')) {
        const today = new Date();
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        
        // Start date picker
        const rentalStartPicker = flatpickr("#rentalStartDate", {
            ...flatpickrDefaults,
            defaultDate: today,
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                // Update end date minDate when start date changes
                rentalEndPicker.set('minDate', dateStr);
                
                // If end date is before new start date, update it
                if (rentalEndPicker.selectedDates[0] < selectedDates[0]) {
                    // Set end date to one month after new start date
                    const newEndDate = new Date(selectedDates[0]);
                    newEndDate.setMonth(newEndDate.getMonth() + 1);
                    rentalEndPicker.setDate(newEndDate);
                }
            }
        });
        
        // End date picker
        const rentalEndPicker = flatpickr("#rentalEndDate", {
            ...flatpickrDefaults,
            defaultDate: nextMonth,
            minDate: nextMonth
        });
    }
    
    // Reservation date/time pickers (short-term)
    if (document.getElementById('reservationStartTime')) {
        // Calculate reasonable defaults
        const now = new Date();
        const startDefault = new Date(now.getTime() + 30 * 60000); // 30 minutes from now
        const endDefault = new Date(now.getTime() + 120 * 60000); // 2 hours from now
        
        // Start time picker
        const reservationStartPicker = flatpickr("#reservationStartTime", {
            ...flatpickrDefaults,
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            defaultDate: startDefault,
            minuteIncrement: 15,
            allowInput: true,
            onChange: function(selectedDates, dateStr) {
                // Update end time minDate/minTime when start time changes
                reservationEndPicker.set('minDate', selectedDates[0]);
                
                // If end time is before or equal to new start time, update it
                if (reservationEndPicker.selectedDates[0] <= selectedDates[0]) {
                    // Set end time to 1.5 hours after new start time
                    const newEndTime = new Date(selectedDates[0]);
                    newEndTime.setMinutes(newEndTime.getMinutes() + 90);
                    reservationEndPicker.setDate(newEndTime);
                }
            }
        });
        
        // End time picker
        const reservationEndPicker = flatpickr("#reservationEndTime", {
            ...flatpickrDefaults,
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: endDefault,
            defaultDate: endDefault,
            minuteIncrement: 15,
            allowInput: true
        });
    }
});
