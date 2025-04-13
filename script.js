document.addEventListener('DOMContentLoaded', function() {

    // Temperature Unit Toggle Handler
    const tempToggleCheckbox = document.getElementById('temp_unit_toggle');
    const hiddenTempInput = document.getElementById('temp_unit_hidden'); // Assumes hidden input exists in the form
    const labelF = document.querySelector('#fixed-temp-toggle .toggle-label:first-of-type');
    const labelC = document.querySelector('#fixed-temp-toggle .toggle-label:last-of-type');

    if (tempToggleCheckbox && hiddenTempInput && labelF && labelC) {
        // Function to update labels and hidden input based on checkbox state
        const updateTempUnit = () => {
            if (tempToggleCheckbox.checked) { // Celsius is selected
                labelC.classList.add('active');
                labelF.classList.remove('active');
                hiddenTempInput.value = 'C';
            } else { // Fahrenheit is selected
                labelF.classList.add('active');
                labelC.classList.remove('active');
                hiddenTempInput.value = 'F';
            }
        };

        // Add event listener
        tempToggleCheckbox.addEventListener('change', updateTempUnit);

        // Initial state update on page load (in case of back button / retained state)
        updateTempUnit();
    } else {
        console.warn("Temperature toggle elements not found.");
    }

    // Optional: Basic ZIP code validation feedback (visual only)
    const zipInput = document.getElementById('zip_code');
    if (zipInput) {
        zipInput.addEventListener('input', function() {
            const isValid = /^\d{5}$/.test(zipInput.value);
            // You could add/remove a class for visual feedback, e.g., changing border color
            // Example: zipInput.classList.toggle('is-invalid', zipInput.value.length > 0 && !isValid);
            // Note: This requires corresponding CSS for .is-invalid
        });
    }

    // Optional: Add loading indicator on form submission
    const form = document.querySelector('.location-form');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;
    const messagesContainer = document.getElementById('messages'); // Assuming a div with id="messages" for loading info

    if (form && submitButton && messagesContainer) {
        form.addEventListener('submit', function() {
            // Basic validation check
            if (zipInput && !/^\d{5}$/.test(zipInput.value)) {
                 // Prevent submission and show error (or rely on server-side)
                 // alert("Please enter a valid 5-digit US ZIP code.");
                 // event.preventDefault(); // Stop form submission if using client-side blocking
                 // For now, let server handle validation primarily.
            } else {
                 // Disable button and show loading message
                 submitButton.disabled = true;
                 submitButton.textContent = 'Fetching Weather...'; // Change button text

                 // Clear previous results/messages if needed
                 const resultsDiv = document.querySelector('.results');
                 if (resultsDiv) resultsDiv.innerHTML = ''; // Clear old results

                 // Display a loading message
                 messagesContainer.innerHTML = '<div class="message info">Fetching weather and suggestions... Please wait.</div>';
                 messagesContainer.style.display = 'block';
            }
        });
    }

});
