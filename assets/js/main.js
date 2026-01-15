// ============================================
// COTTAGE BROWSING FUNCTIONALITY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Image gallery functionality
    initImageGallery();
    
    // Price range slider (if needed later)
    initPriceSlider();
    
    // Form validation
    initFilterValidation();
});

function initImageGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('mainImage');
    
    if (thumbnails.length > 0 && mainImage) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Update main image
                mainImage.src = this.src;
                
                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Set first thumbnail as active initially
        if (thumbnails.length > 0) {
            thumbnails[0].classList.add('active');
        }
    }
}

function initPriceSlider() {
    const priceSlider = document.getElementById('priceSlider');
    const priceValue = document.getElementById('priceValue');
    
    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
            const value = this.value;
            priceValue.textContent = '₱' + value.toLocaleString();
            
            // Update hidden input for form submission
            document.getElementById('maxPrice').value = value;
        });
    }
}

function initFilterValidation() {
    const filterForm = document.querySelector('.filter-form');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const maxPrice = this.querySelector('input[name="max_price"]');
            const minCapacity = this.querySelector('input[name="min_capacity"]');
            
            // Validate max price
            if (maxPrice.value && !isValidPrice(maxPrice.value)) {
                e.preventDefault();
                alert('Please enter a valid price (numbers only)');
                maxPrice.focus();
                return false;
            }
            
            // Validate min capacity
            if (minCapacity.value && !isValidCapacity(minCapacity.value)) {
                e.preventDefault();
                alert('Please enter a valid number of guests (1-20)');
                minCapacity.focus();
                return false;
            }
        });
    }
}

function isValidPrice(price) {
    const num = parseFloat(price);
    return !isNaN(num) && num >= 0 && num <= 100000;
}

function isValidCapacity(capacity) {
    const num = parseInt(capacity);
    return !isNaN(num) && num >= 1 && num <= 20;
}

// Favorite cottage functionality
function toggleFavorite(cottageId) {
    if (!isLoggedIn()) {
        alert('Please login to save favorites');
        return;
    }
    
    fetch(`../api/favorite.php?cottage_id=${cottageId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        const heartIcon = document.querySelector(`.favorite-btn[data-cottage="${cottageId}"] i`);
        if (data.success) {
            if (data.action === 'added') {
                heartIcon.className = 'fas fa-heart';
                showToast('Added to favorites!', 'success');
            } else {
                heartIcon.className = 'far fa-heart';
                showToast('Removed from favorites', 'info');
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Check if user is logged in (simplified)
function isLoggedIn() {
    // This should check session/cookie
    // For now, return true if we have user data in session
    return true; // Update this with actual session check
}

// Share cottage functionality
function shareCottage(cottageName, cottageId) {
    const url = `${window.location.origin}/user/cottage-details.php?id=${cottageId}`;
    const text = `Check out ${cottageName} at our resort!`;
    
    if (navigator.share) {
        navigator.share({
            title: cottageName,
            text: text,
            url: url
        })
        .then(() => console.log('Shared successfully'))
        .catch(error => console.log('Error sharing:', error));
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(`${text}\n${url}`)
            .then(() => showToast('Link copied to clipboard!', 'success'))
            .catch(err => {
                console.error('Failed to copy: ', err);
                showToast('Failed to copy link', 'error');
            });
    }
}


// ============================================
// MODULE 4: DATE UTILITIES
// ============================================

/**
 * Format date to YYYY-MM-DD
 */
function formatDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Calculate days between two dates
 */
function calculateDaysBetween(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const timeDiff = end.getTime() - start.getTime();
    return Math.ceil(timeDiff / (1000 * 3600 * 24));
}

/**
 * Check if a date is in the past
 */
function isPastDate(date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkDate = new Date(date);
    checkDate.setHours(0, 0, 0, 0);
    return checkDate < today;
}

/**
 * Validate date range
 */
function validateDateRange(checkIn, checkOut) {
    if (!checkIn || !checkOut) {
        return { valid: false, message: 'Both dates are required' };
    }
    
    if (isPastDate(checkIn)) {
        return { valid: false, message: 'Check-in date cannot be in the past' };
    }
    
    if (checkIn >= checkOut) {
        return { valid: false, message: 'Check-out date must be after check-in date' };
    }
    
    const daysBetween = calculateDaysBetween(checkIn, checkOut);
    if (daysBetween < 1) {
        return { valid: false, message: 'Minimum stay is 1 night' };
    }
    
    if (daysBetween > 30) {
        return { valid: false, message: 'Maximum stay is 30 nights' };
    }
    
    return { valid: true, days: daysBetween };
}

/**
 * Generate array of dates between two dates
 */
function getDatesBetween(startDate, endDate) {
    const dates = [];
    const current = new Date(startDate);
    const end = new Date(endDate);
    
    while (current <= end) {
        dates.push(formatDate(current));
        current.setDate(current.getDate() + 1);
    }
    
    return dates;
}

/**
 * Check if dates overlap with blocked periods
 */
function checkDateOverlap(selectedDates, blockedPeriods) {
    for (const date of selectedDates) {
        for (const period of blockedPeriods) {
            const start = new Date(period.from);
            const end = new Date(period.to);
            const check = new Date(date);
            
            if (check >= start && check <= end) {
                return true;
            }
        }
    }
    return false;
}