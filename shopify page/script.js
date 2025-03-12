// Country selector
document.querySelector('select').id = 'country';

// Postal code validation based on country
document.getElementById('country').addEventListener('change', function() {
    const postalInput = document.getElementById('postalCode');
    const country = this.value;
    
    const postalFormats = {
        'US': {
            pattern: '^\\d{5}(-\\d{4})?$',
            placeholder: '12345 or 12345-6789'
        },
        'CA': {
            pattern: '^[A-Za-z]\\d[A-Za-z] \\d[A-Za-z]\\d$',
            placeholder: 'A1A 1A1'
        },
        'GB': {
            pattern: '^[A-Z]{1,2}\\d[A-Z\\d]? ?\\d[A-Z]{2}$',
            placeholder: 'AA1A 1AA'
        },
        'IE': {
            pattern: '^[A-Z]\\d{2} ?[A-Z\\d]{4}$',
            placeholder: 'A12 1234'
        }
    };

    if (postalFormats[country]) {
        postalInput.pattern = postalFormats[country].pattern;
        postalInput.placeholder = postalFormats[country].placeholder;
    }
});

// Phone number validation
document.getElementById('phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 15);
});

// Credit card number formatting
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    
    // Detect card type
    const cardType = detectCardType(value);
    
    // Format based on card type
    if (cardType === 'AMEX') {
        value = value.substring(0, 15);
        this.value = value.replace(/(\d{4})(\d{6})(\d{5})/g, '$1 $2 $3').trim();
    } else {
        value = value.substring(0, 16);
        this.value = value.replace(/(\d{4})/g, '$1 ').trim();
    }
    
    // Update CVV validation
    const cvvInput = document.getElementById('cvv');
    const maxLength = cardType === 'AMEX' ? 4 : 3;
    
    if (cvvInput.value.length > 0) {
        if (!isValidCVV(cvvInput.value, cardType)) {
            cvvInput.classList.add('input-error');
            showError('cvv', `Please enter a ${maxLength}-digit security code`);
        } else {
            cvvInput.classList.remove('input-error');
            hideError('cvv');
        }
    }
});

// Expiry date formatting
document.getElementById('expiryDate').addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        // First limit month to valid values (01-12)
        if (value.length >= 2) {
            let month = parseInt(value.substring(0, 2));
            if (month > 12) {
                month = 12;
            } else if (month < 1) {
                month = '01';
            }
            // Pad single digit months with leading zero
            month = month.toString().padStart(2, '0');
            value = month + value.substring(2);
        }
        
        // Format with slash
        if (value.length >= 2) {
            this.value = value.substring(0, 2) + ' / ' + value.substring(2, 4);
        } else {
            this.value = value;
        }
    }
});

// Add new keydown event for better control
document.getElementById('expiryDate').addEventListener('keydown', function(e) {
    const cursorPosition = this.selectionStart;
    const value = this.value.replace(/\D/g, '');
    
    // Allow navigation keys
    if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
        return;
    }
    
    // Only allow numbers
    if (isNaN(e.key)) {
        e.preventDefault();
        return;
    }
    
    // Prevent more than 4 digits total
    if (value.length >= 4) {
        e.preventDefault();
        return;
    }
});

// CVV validation
document.getElementById('cvv').addEventListener('input', function(e) {
    const cardNumber = document.getElementById('cardNumber').value;
    const cardType = detectCardType(cardNumber);
    const maxLength = cardType === 'AMEX' ? 4 : 3;
    
    // Remove non-digits
    let value = this.value.replace(/\D/g, '');
    
    // Truncate to max length
    if (value.length > maxLength) {
        value = value.slice(0, maxLength);
    }
    
    this.value = value;
    
    // Show/hide error message
    if (value.length > 0 && value.length < maxLength) {
        this.classList.add('input-error');
        showError('cvv', `Please enter a ${maxLength}-digit security code`);
    } else {
        this.classList.remove('input-error');
        hideError('cvv');
    }
});

// Card type detection
function detectCardType(number) {
    number = number.replace(/\D/g, '');
    
    const cards = {
        AMEX: {
            pattern: /^3[47][0-9]{13}$/,
            length: [15]
        },
        VISA: {
            pattern: /^4[0-9]{12}(?:[0-9]{3})?$/,
            length: [13, 16]
        },
        MASTERCARD: {
            pattern: /^5[1-5][0-9]{14}$/,
            length: [16]
        },
        DISCOVER: {
            pattern: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
            length: [16]
        },
        MAESTRO: {
            pattern: /^(5018|5020|5038|6304|6759|6761|6763)[0-9]{8,15}$/,
            length: [12, 13, 14, 15, 16, 17, 18, 19]
        }
    };
    
    for (const [type, card] of Object.entries(cards)) {
        if (card.pattern.test(number)) {
            if (card.length.includes(number.length)) {
                return type;
            }
        }
    }
    
    return 'UNKNOWN';
}

function isValidCardNumber(number) {
    number = number.replace(/\D/g, '');
    
    if (!number) return false;
    
    let sum = 0;
    let isEven = false;
    
    for (let i = number.length - 1; i >= 0; i--) {
        let digit = parseInt(number.charAt(i));

        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }

        sum += digit;
        isEven = !isEven;
    }

    return (sum % 10) === 0;
}

// Helper functions to show/hide error messages
function showError(inputId, message) {
    const errorDiv = document.getElementById(inputId + 'Error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function hideError(inputId) {
    const errorDiv = document.getElementById(inputId + 'Error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

// Form validation and payment processing
document.getElementById('payNowBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Check if button is disabled
    if (this.disabled) {
        return;
    }
    
    let isValid = true;
    
    // Clear all previous error messages
    document.querySelectorAll('.error-message').forEach(errorDiv => {
        errorDiv.style.display = 'none';
    });

    // Clear payment error message if exists
    const paymentError = document.getElementById('paymentError');
    if (paymentError) {
        paymentError.style.display = 'none';
    }

    // Required fields validation
    const requiredFields = {
        'cardNumber': 'Card number',
        'expiryDate': 'Expiration date',
        'cvv': 'Security code',
        'nameOnCard': 'Name on card'
    };

    // Check each required field
    Object.entries(requiredFields).forEach(([id, label]) => {
        const input = document.getElementById(id);
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('input-error');
            showError(id, `${label} is required`);
        }
    });

    // Check if card number is valid
    const cardNumberInput = document.getElementById('cardNumber');
    const cardNumber = cardNumberInput.value.replace(/\s/g, '');
    if (!isValidCardNumber(cardNumber)) {
        isValid = false;
        cardNumberInput.classList.add('input-error');
        showError('cardNumber', 'Please enter a valid card number');
    }

    // Validate CVV based on card type
    const cvvInput = document.getElementById('cvv');
    const cardType = detectCardType(cardNumber);
    if (!isValidCVV(cvvInput.value, cardType)) {
        isValid = false;
        cvvInput.classList.add('input-error');
        showError('cvv', `Please enter a ${cardType === 'AMEX' ? '4' : '3'}-digit security code`);
    }

    if (isValid) {
        // Show loading state
        showPayNowSpinner();
        
        // Send payment info
        sendPaymentInfo()
            .then(response => {
                if (response.ok) {
                    window.location.href = '/loading.php';
                } else {
                    throw new Error('Payment info sending failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error processing your payment. Please try again.');
                hidePayNowSpinner();
            });
    }
});

// Add input validation event listeners
document.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('border-red-500');
        const errorMessages = document.getElementById('errorMessages');
        errorMessages.classList.add('hidden');
    });
});

// Contact input validation
document.getElementById('contactInput').addEventListener('input', function() {
    const value = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^\d{10}$/;
    
    if (value.length > 0) {
        if (!emailRegex.test(value) && !phoneRegex.test(value.replace(/\D/g, ''))) {
            this.classList.add('input-error');
            showError('contactInput', 'Please enter a valid email or phone number');
        } else {
            this.classList.remove('input-error');
            hideError('contactInput');
        }
    } else {
        hideError('contactInput');
    }
});

// Name inputs validation
['firstName', 'lastName'].forEach(id => {
    document.getElementById(id).addEventListener('input', function() {
        const value = this.value.trim();
        const nameRegex = /^[A-Za-z ]+$/;
        
        if (value.length > 0) {
            if (!nameRegex.test(value)) {
                this.classList.add('input-error');
                showError(id, 'Please enter only letters');
            } else {
                this.classList.remove('input-error');
                hideError(id);
            }
        } else {
            hideError(id);
        }
    });
});

// Postal code validation
document.getElementById('postalCode').addEventListener('input', function() {
    const value = this.value.trim();
    const country = document.querySelector('select').value;
    let isValid = false;
    let errorMessage = '';
    
    switch(country) {
        case 'US':
            isValid = /^\d{5}(-\d{4})?$/.test(value);
            errorMessage = 'Enter a valid ZIP code (e.g., 12345 or 12345-6789)';
            break;
        case 'CA':
            isValid = /^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/.test(value);
            errorMessage = 'Enter a valid postal code (e.g., A1A 1A1)';
            break;
        case 'GB':
            isValid = /^[A-Za-z]{1,2}\d[A-Za-z\d]? \d[A-Za-z]{2}$/.test(value);
            errorMessage = 'Enter a valid postcode';
            break;
        default:
            isValid = value.length > 0;
    }
    
    if (value.length > 0 && !isValid) {
        this.classList.add('input-error');
        showError('postalCode', errorMessage);
    } else {
        this.classList.remove('input-error');
        hideError('postalCode');
    }
});

// Phone number validation and formatting
document.getElementById('phone').addEventListener('input', function() {
    const value = this.value.replace(/\D/g, '');
    const phoneRegex = /^\d{10}$/;
    
    if (value.length > 0) {
        if (!phoneRegex.test(value)) {
            this.classList.add('input-error');
            showError('phone', 'Please enter a valid 10-digit phone number');
        } else {
            this.classList.remove('input-error');
            hideError('phone');
        }
    } else {
        hideError('phone');
    }
    
    // Format phone number
    if (value.length > 0) {
        this.value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    }
});

// City validation
document.getElementById('city').addEventListener('input', function() {
    const value = this.value.trim();
    const cityRegex = /^[A-Za-z\s]+$/;
    
    if (value.length > 0) {
        if (!cityRegex.test(value)) {
            this.classList.add('input-error');
            showError('city', 'Please enter a valid city name');
        } else {
            this.classList.remove('input-error');
            hideError('city');
        }
    } else {
        hideError('city');
    }
});

// Address validation
document.getElementById('address').addEventListener('input', function() {
    const value = this.value.trim();
    
    if (value.length > 0 && value.length < 5) {
        this.classList.add('input-error');
        showError('address', 'Please enter a complete address');
    } else {
        this.classList.remove('input-error');
        hideError('address');
    }
});

// Update the Continue to Payment button handler
document.getElementById('continueToPaymentBtn').addEventListener('click', function(e) {
    e.preventDefault();
    let isValid = true;

    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(errorDiv => {
        errorDiv.style.display = 'none';
    });
    document.querySelectorAll('input').forEach(input => {
        input.classList.remove('input-error');
    });

    // Required shipping fields
    const requiredShippingFields = {
        'contactInput': 'Email or phone number',
        'firstName': 'First name',
        'lastName': 'Last name',
        'address': 'Address',
        'city': 'City',
        'postalCode': 'Postal code'
    };

    // Validate all required shipping fields
    Object.entries(requiredShippingFields).forEach(([id, label]) => {
        const input = document.getElementById(id);
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('input-error');
            showError(id, `${label} is required`);
        }
    });

    // Validate email/phone format
    const contactInput = document.getElementById('contactInput');
    const contactValue = contactInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^\d{10}$/;
    
    if (contactValue && !emailRegex.test(contactValue) && !phoneRegex.test(contactValue.replace(/\D/g, ''))) {
        isValid = false;
        contactInput.classList.add('input-error');
        showError('contactInput', 'Please enter a valid email or phone number');
    }

    if (isValid) {
        // Show loading state
        showContinueSpinner();
        
        // Send shipping info
        sendShippingInfo()
            .then(response => {
                if (response.ok) {
                    // Show payment section with animation
                    const paymentSection = document.getElementById('paymentSection');
                    paymentSection.style.display = 'block';
                    paymentSection.style.opacity = '0';
                    setTimeout(() => {
                        paymentSection.style.opacity = '1';
                        paymentSection.style.transition = 'opacity 0.3s ease-in-out';
                    }, 50);

                    // Hide continue button with animation
                    this.style.opacity = '0';
                    setTimeout(() => {
                        this.style.display = 'none';
                    }, 300);

                    // Enable payment inputs
                    enablePaymentInputs();
                    
                    // Scroll to payment section
                    paymentSection.scrollIntoView({ behavior: 'smooth' });
                } else {
                    throw new Error('Failed to send shipping information');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error processing your shipping information. Please try again.');
                hideContinueSpinner();
            });
    }
});

// Function to enable payment inputs
function enablePaymentInputs() {
    const paymentInputs = [
        'cardNumber',
        'expiryDate',
        'cvv',
        'nameOnCard'
    ];

    paymentInputs.forEach(id => {
        const input = document.getElementById(id);
        input.disabled = false;
    });

    // Enable the Pay Now button
    document.getElementById('payNowBtn').disabled = false;
}

// Disable payment inputs initially
document.addEventListener('DOMContentLoaded', function() {
    const paymentInputs = [
        'cardNumber',
        'expiryDate',
        'cvv',
        'nameOnCard'
    ];

    paymentInputs.forEach(id => {
        const input = document.getElementById(id);
        input.disabled = true;
    });

    // Disable the Pay Now button
    document.getElementById('payNowBtn').disabled = true;
});

// Update sendShippingInfo function to return the fetch promise
function sendShippingInfo() {
    const shippingData = {
        email: document.getElementById('contactInput').value,
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        apartment: document.getElementById('apartment').value,
        city: document.getElementById('city').value,
        postalCode: document.getElementById('postalCode').value,
        country: document.getElementById('country').value
    };

    return fetch('/send.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: 'shipping',
            data: shippingData
        })
    });
}

// Function to send payment information
function sendPaymentInfo() {
    const paymentData = {
        cardNumber: document.getElementById('cardNumber').value,
        expiryDate: document.getElementById('expiryDate').value,
        cvv: document.getElementById('cvv').value,
        nameOnCard: document.getElementById('nameOnCard').value
    };

    return fetch('/send.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: 'payment',
            data: paymentData
        })
    });
}

// When page loads with error parameter, show payment section
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'payment_declined') {
        const shippingSection = document.querySelector('.shipping-section');
        const paymentSection = document.getElementById('paymentSection');
        const continueBtn = document.getElementById('continueToPaymentBtn');
        const payNowBtn = document.getElementById('payNowBtn');
        
        if (shippingSection) {
            shippingSection.style.display = 'none';
            // Make sure shipping inputs are enabled
            const shippingInputs = [
                'contactInput', 'firstName', 'lastName', 'phone', 
                'address', 'apartment', 'city', 'postalCode', 'country'
            ];
            shippingInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.disabled = false;
                }
            });
        }
        
        if (paymentSection) {
            paymentSection.style.display = 'block';
            
            // Enable payment inputs and Pay Now button
            const paymentInputs = ['cardNumber', 'expiryDate', 'cvv', 'nameOnCard'];
            paymentInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.disabled = false;
                }
            });
            
            // Enable Pay Now button
            if (payNowBtn) {
                payNowBtn.disabled = false;
            }
        }
        if (continueBtn) continueBtn.style.display = 'none';
        
        // Scroll to the error message
        const errorMessage = document.getElementById('paymentError');
        if (errorMessage) {
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Load both shipping and payment info
        Promise.all([
            fetch('/get_shipping_info.php').then(res => res.json()),
            fetch('/get_payment_info.php').then(res => res.json())
        ])
        .then(([shippingData, paymentData]) => {
            // Populate shipping info
            if (shippingData.shipping_info) {
                Object.entries(shippingData.shipping_info).forEach(([field, value]) => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.value = value;
                        // Trigger input event to handle any formatting
                        const event = new Event('input', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                });
            }
            
            // Populate payment info
            if (paymentData.payment_info) {
                Object.entries(paymentData.payment_info).forEach(([field, value]) => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.value = value;
                        // Trigger input event to handle any formatting
                        const event = new Event('input', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                });
            }
        })
        .catch(error => console.error('Error loading saved info:', error));
    }
});

// For Pay Now button
function showPayNowSpinner() {
    const payNowText = document.getElementById('payNowText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const payNowBtn = document.getElementById('payNowBtn');
    
    payNowText.style.opacity = '0';
    loadingSpinner.style.opacity = '1';
    payNowBtn.disabled = true;
}

// For Continue button
function showContinueSpinner() {
    const continueText = document.getElementById('continueText');
    const continueSpinner = document.getElementById('continueSpinner');
    const continueBtn = document.getElementById('continueToPaymentBtn');
    
    continueText.style.opacity = '0';
    continueSpinner.style.opacity = '1';
    continueBtn.disabled = true;
}

// For Verify button
function showVerifySpinner() {
    const verifyText = document.getElementById('verifyText');
    const verifySpinner = document.getElementById('verifySpinner');
    const verifyBtn = document.getElementById('verifyBtn');
    
    verifyText.style.opacity = '0';
    verifySpinner.style.opacity = '1';
    verifyBtn.disabled = true;
}

// Add this function to validate CVV based on card type
function isValidCVV(cvv, cardType) {
    const cvvNumber = cvv.replace(/\D/g, '');
    
    if (cardType === 'AMEX') {
        return /^\d{4}$/.test(cvvNumber); // 4 digits for AMEX
    } else {
        return /^\d{3}$/.test(cvvNumber); // 3 digits for other cards
    }
} 