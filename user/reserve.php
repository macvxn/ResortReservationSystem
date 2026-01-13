<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

// Check if user is verified
$user_profile = getUserProfile($_SESSION['user_id']);
if ($user_profile['verification_status'] !== 'verified') {
    $_SESSION['error'] = "You need to be verified to make reservations. Please complete ID verification first.";
    header("Location: cottages.php");
    exit();
}

// Get cottage ID
$cottage_id = $_GET['cottage_id'] ?? 0;
if (!$cottage_id) {
    header("Location: cottages.php");
    exit();
}

// Get cottage details
$stmt = $pdo->prepare("SELECT * FROM cottages WHERE cottage_id = ? AND is_active = TRUE");
$stmt->execute([$cottage_id]);
$cottage = $stmt->fetch();

if (!$cottage) {
    header("Location: cottages.php?error=cottage_not_found");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests = $_POST['guests'] ?? 1;
    
    // Validate dates
    $errors = [];
    
    if (empty($check_in) || empty($check_out)) {
        $errors[] = "Please select both check-in and check-out dates.";
    }
    
    if ($check_in >= $check_out) {
        $errors[] = "Check-out date must be after check-in date.";
    }
    
    // Check if dates are in the past
    $today = date('Y-m-d');
    if ($check_in < $today) {
        $errors[] = "Check-in date cannot be in the past.";
    }
    
    // Validate guest count
    $guests = intval($guests);
    if ($guests < 1) {
        $errors[] = "Number of guests must be at least 1.";
    }
    
    if ($guests > $cottage['capacity']) {
        $errors[] = "This cottage can only accommodate up to {$cottage['capacity']} guests.";
    }
    
    // Check availability
    if (empty($errors) && !isDateAvailable($cottage_id, $check_in, $check_out)) {
        $errors[] = "The selected dates are not available. Please choose different dates.";
    }
    
    // Calculate total
    if (empty($errors)) {
        $total_nights = calculateNights($check_in, $check_out);
        $total_price = $total_nights * $cottage['price_per_night'];
        
        // Create reservation
        try {
            $pdo->beginTransaction();
            
            // Insert reservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations 
                (user_id, cottage_id, check_in_date, check_out_date, total_nights, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_admin_review')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $cottage_id,
                $check_in,
                $check_out,
                $total_nights,
                $total_price
            ]);
            
            $reservation_id = $pdo->lastInsertId();
            
            // Log the action
            logAction($_SESSION['user_id'], 'CREATE_RESERVATION', 'reservations', $reservation_id);
            
            $pdo->commit();
            
            // Redirect to payment page
            header("Location: upload-payment.php?reservation_id=" . $reservation_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to create reservation. Please try again.";
        }
    }
}

// Get blocked dates for calendar (approved reservations)
$stmt = $pdo->prepare("
    SELECT check_in_date, check_out_date 
    FROM reservations 
    WHERE cottage_id = ? 
    AND status = 'approved'
    AND check_out_date >= CURDATE()
");
$stmt->execute([$cottage_id]);
$blocked_periods = $stmt->fetchAll();

// Convert to JavaScript array format
$js_blocked_dates = [];
foreach ($blocked_periods as $period) {
    $js_blocked_dates[] = [
        'from' => $period['check_in_date'],
        'to' => $period['check_out_date']
    ];
}
$js_blocked_dates = json_encode($js_blocked_dates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve <?= htmlspecialchars($cottage['cottage_name']) ?> - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <!-- Back Button -->
        <a href="cottage-details.php?id=<?= $cottage_id ?>" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Cottage Details
        </a>
        
        <div class="reservation-wizard">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active">
                    <span class="step-number">1</span>
                    <span class="step-label">Select Dates</span>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-label">Upload Payment</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-label">Confirmation</span>
                </div>
            </div>
            
            <div class="reservation-content">
                <!-- Left Column: Cottage Summary -->
                <div class="reservation-summary">
                    <h3><i class="fas fa-home"></i> Cottage Summary</h3>
                    
                    <div class="summary-card">
                        <div class="cottage-image-small">
                            <?php
                            // Get primary image
                            $stmt = $pdo->prepare("
                                SELECT image_path 
                                FROM cottage_images 
                                WHERE cottage_id = ? AND is_primary = TRUE 
                                LIMIT 1
                            ");
                            $stmt->execute([$cottage_id]);
                            $image = $stmt->fetch();
                            ?>
                            <img src="../uploads/cottages/<?= htmlspecialchars($image ? $image['image_path'] : 'default_cottage.jpg') ?>" 
                                 alt="<?= htmlspecialchars($cottage['cottage_name']) ?>">
                        </div>
                        
                        <div class="summary-details">
                            <h4><?= htmlspecialchars($cottage['cottage_name']) ?></h4>
                            
                            <div class="detail-row">
                                <i class="fas fa-users"></i>
                                <span>Capacity:</span>
                                <strong><?= $cottage['capacity'] ?> guests</strong>
                            </div>
                            
                            <div class="detail-row">
                                <i class="fas fa-tag"></i>
                                <span>Price per night:</span>
                                <strong>₱<?= number_format($cottage['price_per_night'], 2) ?></strong>
                            </div>
                            
                            <div class="detail-row">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Min. stay:</span>
                                <strong>1 night</strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price Calculator -->
                    <div class="price-calculator" id="priceCalculator" style="display: none;">
                        <h4><i class="fas fa-calculator"></i> Price Breakdown</h4>
                        
                        <div class="breakdown-row">
                            <span>Cottage rate:</span>
                            <span id="ratePerNight">₱0.00</span>
                        </div>
                        
                        <div class="breakdown-row">
                            <span>Number of nights:</span>
                            <span id="nightsCount">0</span>
                        </div>
                        
                        <hr>
                        
                        <div class="breakdown-row total">
                            <span><strong>Total amount:</strong></span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                        
                        <div class="note">
                            <i class="fas fa-info-circle"></i>
                            Payment proof must be uploaded within 24 hours of reservation.
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Reservation Form -->
                <div class="reservation-form-container">
                    <h2><i class="fas fa-calendar-check"></i> Select Your Dates</h2>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following:</h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="reservationForm">
                        <!-- Date Selection -->
                        <div class="form-section">
                            <h3><i class="fas fa-calendar"></i> Select Dates</h3>
                            
                            <div class="date-picker-container">
                                <div class="date-input-group">
                                    <label for="check_in"><i class="fas fa-sign-in-alt"></i> Check-in Date</label>
                                    <input type="text" 
                                           id="check_in" 
                                           name="check_in" 
                                           class="date-input"
                                           placeholder="Select check-in date"
                                           required
                                           value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>">
                                </div>
                                
                                <div class="date-input-group">
                                    <label for="check_out"><i class="fas fa-sign-out-alt"></i> Check-out Date</label>
                                    <input type="text" 
                                           id="check_out" 
                                           name="check_out" 
                                           class="date-input"
                                           placeholder="Select check-out date"
                                           required
                                           value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <!-- Calendar Display -->
                            <div class="calendar-container">
                                <div id="calendar"></div>
                            </div>
                        </div>
                        
                        <!-- Guest Selection -->
                        <div class="form-section">
                            <h3><i class="fas fa-users"></i> Number of Guests</h3>
                            
                            <div class="guest-selector">
                                <button type="button" class="guest-btn" onclick="adjustGuests(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                
                                <input type="number" 
                                       id="guests" 
                                       name="guests" 
                                       value="<?= htmlspecialchars($_POST['guests'] ?? 1) ?>" 
                                       min="1" 
                                       max="<?= $cottage['capacity'] ?>"
                                       readonly>
                                
                                <span class="guest-label">Guest(s)</span>
                                
                                <button type="button" class="guest-btn" onclick="adjustGuests(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <p class="note">
                                <i class="fas fa-info-circle"></i>
                                Maximum capacity: <?= $cottage['capacity'] ?> guests
                            </p>
                        </div>
                        
                        <!-- Important Notes -->
                        <div class="form-section">
                            <h3><i class="fas fa-exclamation-circle"></i> Important Information</h3>
                            
                            <div class="info-box">
                                <ul>
                                    <li>
                                        <i class="fas fa-clock"></i>
                                        <strong>Check-in time:</strong> 2:00 PM
                                    </li>
                                    <li>
                                        <i class="fas fa-clock"></i>
                                        <strong>Check-out time:</strong> 12:00 PM
                                    </li>
                                    <li>
                                        <i class="fas fa-calendar-times"></i>
                                        <strong>Cancellation:</strong> No cancellations after submission
                                    </li>
                                    <li>
                                        <i class="fas fa-money-bill-wave"></i>
                                        <strong>Payment:</strong> Upload payment proof after reservation
                                    </li>
                                    <li>
                                        <i class="fas fa-user-shield"></i>
                                        <strong>Confirmation:</strong> Subject to admin approval
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle"></i> Confirm Reservation
                            </button>
                            
                            <a href="cottage-details.php?id=<?= $cottage_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/main.js"></script>
    
    <script>
// Blocked dates from PHP
const blockedPeriods = <?= $js_blocked_dates ?>;
const cottagePrice = <?= $cottage['price_per_night'] ?>;
const cottageCapacity = <?= $cottage['capacity'] ?>;

// Store the date picker instances
let checkInPicker, checkOutPicker;

// Initialize date pickers
document.addEventListener('DOMContentLoaded', function() {
    // Convert blocked periods to disabled dates array
    const disabledDates = getBlockedDatesArray(blockedPeriods);
    
    // Get today's date
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Initialize check-in date picker
    checkInPicker = flatpickr("#check_in", {
        minDate: "today",
        dateFormat: "Y-m-d",
        disable: disabledDates,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const selectedDate = selectedDates[0];
                
                // Set minimum check-out date (next day)
                const minCheckOut = new Date(selectedDate);
                minCheckOut.setDate(minCheckOut.getDate() + 1);
                
                // Update check-out picker
                checkOutPicker.set('minDate', minCheckOut);
                checkOutPicker.set('disable', []);
                checkOutPicker.clear(); // Clear previous selection
                
                // Enable check-out field
                document.getElementById('check_out').disabled = false;
                
                // Update calendar highlight
                highlightSelectedDates(dateStr, null);
                
                // Update price calculator
                updatePriceCalculator();
            }
        }
    });
    
    // Initialize check-out date picker (initially disabled)
    checkOutPicker = flatpickr("#check_out", {
        minDate: new Date().fp_incr(1), // Tomorrow
        dateFormat: "Y-m-d",
        disable: disabledDates,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                // Update calendar highlight
                const checkInVal = document.getElementById('check_in').value;
                highlightSelectedDates(checkInVal, dateStr);
                
                // Update price calculator
                updatePriceCalculator();
            }
        }
    });
    
    // Initially disable check-out field until check-in is selected
    document.getElementById('check_out').disabled = true;
    
    // Initialize calendar with current month
    initCalendar(today.getFullYear(), today.getMonth(), disabledDates);
    
    // Initialize guest selector
    updateGuestSelector();
});

/**
 * Convert blocked periods to array of disabled dates
 */
function getBlockedDatesArray(blockedPeriods) {
    const disabledDates = [];
    
    blockedPeriods.forEach(period => {
        let current = new Date(period.from);
        const end = new Date(period.to);
        
        // Create array of all dates in the blocked period
        while (current < end) { // Use < instead of <= to allow check-out on the end date
            disabledDates.push(new Date(current));
            current.setDate(current.getDate() + 1);
        }
    });
    
    return disabledDates;
}

/**
 * Initialize calendar display
 */
function initCalendar(year, month, disabledDates) {
    const calendarEl = document.getElementById('calendar');
    const monthNames = ["January", "February", "March", "April", "May", "June",
                      "July", "August", "September", "October", "November", "December"];
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();
    
    let calendarHTML = `
        <div class="calendar-header">
            <button type="button" class="calendar-nav" onclick="changeMonth(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h4>${monthNames[month]} ${year}</h4>
            <button type="button" class="calendar-nav" onclick="changeMonth(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="calendar-weekdays">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div class="calendar-days">
    `;
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDay; i++) {
        calendarHTML += '<div class="empty-day"></div>';
    }
    
    // Get today's date
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const dateStr = currentDate.toISOString().split('T')[0];
        
        let className = 'calendar-day';
        let status = '';
        
        // Check if date is today
        if (currentDate.getTime() === today.getTime()) {
            className += ' today';
        }
        
        // Check if date is in the past
        if (currentDate < today) {
            className += ' past-date';
            status = 'past';
        }
        
        // Check if date is blocked
        const isBlocked = isDateBlocked(currentDate, disabledDates);
        
        if (isBlocked) {
            className += ' blocked-date';
            status = 'blocked';
        }
        
        // Add click event for selecting dates
        if (status !== 'past' && status !== 'blocked') {
            calendarHTML += `<div class="${className}" data-date="${dateStr}" onclick="selectDateFromCalendar('${dateStr}')">${day}</div>`;
        } else {
            calendarHTML += `<div class="${className}" data-date="${dateStr}">${day}</div>`;
        }
    }
    
    calendarHTML += '</div>';
    calendarEl.innerHTML = calendarHTML;
    
    // Store current month/year for navigation
    calendarEl.dataset.currentYear = year;
    calendarEl.dataset.currentMonth = month;
    
    // Highlight selected dates if any
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    highlightSelectedDates(checkIn, checkOut);
}

/**
 * Check if a date is blocked
 */
function isDateBlocked(date, disabledDates) {
    return disabledDates.some(blockedDate => {
        return blockedDate.toDateString() === date.toDateString();
    });
}

/**
 * Change calendar month
 */
function changeMonth(direction) {
    const calendarEl = document.getElementById('calendar');
    let year = parseInt(calendarEl.dataset.currentYear);
    let month = parseInt(calendarEl.dataset.currentMonth);
    
    // Calculate new month
    month += direction;
    
    // Handle year rollover
    if (month < 0) {
        month = 11;
        year--;
    } else if (month > 11) {
        month = 0;
        year++;
    }
    
    // Get blocked dates for the new month
    const disabledDates = getBlockedDatesArray(blockedPeriods);
    
    // Reinitialize calendar
    initCalendar(year, month, disabledDates);
}

/**
 * Select date from calendar click
 */
function selectDateFromCalendar(dateStr) {
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const selectedDate = new Date(dateStr);
    
    // If no check-in selected or check-in is after this date, set as check-in
    if (!checkInInput.value || new Date(checkInInput.value) > selectedDate) {
        checkInPicker.setDate(dateStr, true);
        checkOutInput.disabled = false;
    } 
    // If check-in is selected and this date is after check-in, set as check-out
    else if (checkInInput.value && selectedDate > new Date(checkInInput.value)) {
        checkOutPicker.setDate(dateStr, true);
    }
    // If clicking on check-in date again, clear both
    else if (checkInInput.value === dateStr) {
        checkInPicker.clear();
        checkOutPicker.clear();
        checkOutInput.disabled = true;
    }
    
    // Update calendar highlight
    highlightSelectedDates(checkInInput.value, checkOutInput.value);
    updatePriceCalculator();
}

/**
 * Highlight selected dates on calendar
 */
function highlightSelectedDates(checkIn, checkOut) {
    // Remove previous highlights
    document.querySelectorAll('.calendar-day.selected').forEach(el => {
        el.classList.remove('selected');
        el.classList.remove('in-range');
    });
    
    if (!checkIn) return;
    
    const checkInDate = new Date(checkIn);
    
    // Highlight check-in date
    const checkInEl = document.querySelector(`.calendar-day[data-date="${checkIn}"]`);
    if (checkInEl) {
        checkInEl.classList.add('selected');
    }
    
    if (!checkOut) return;
    
    const checkOutDate = new Date(checkOut);
    
    // Highlight check-out date
    const checkOutEl = document.querySelector(`.calendar-day[data-date="${checkOut}"]`);
    if (checkOutEl) {
        checkOutEl.classList.add('selected');
    }
    
    // Highlight dates in between
    document.querySelectorAll('.calendar-day').forEach(dayEl => {
        const dateStr = dayEl.dataset.date;
        if (!dateStr) return;
        
        const date = new Date(dateStr);
        
        if (date > checkInDate && date < checkOutDate) {
            if (!dayEl.classList.contains('blocked-date') && !dayEl.classList.contains('past-date')) {
                dayEl.classList.add('in-range');
            }
        }
    });
}

function adjustGuests(change) {
    const guestInput = document.getElementById('guests');
    let current = parseInt(guestInput.value) + change;
    
    // Validate bounds
    if (current < 1) current = 1;
    if (current > cottageCapacity) current = cottageCapacity;
    
    guestInput.value = current;
    updateGuestSelector();
}

function updateGuestSelector() {
    const guestInput = document.getElementById('guests');
    const minusBtn = document.querySelector('.guest-btn:first-of-type');
    const plusBtn = document.querySelector('.guest-btn:last-of-type');
    
    // Disable minus button at minimum
    minusBtn.disabled = (parseInt(guestInput.value) <= 1);
    
    // Disable plus button at maximum
    plusBtn.disabled = (parseInt(guestInput.value) >= cottageCapacity);
}

/**
 * UPDATE THIS FUNCTION - This is the fix for price calculator
 */
function updatePriceCalculator() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const calculator = document.getElementById('priceCalculator');
    
    console.log('updatePriceCalculator called');
    console.log('checkIn:', checkIn);
    console.log('checkOut:', checkOut);
    console.log('cottagePrice:', cottagePrice);
    
    if (checkIn && checkOut) {
        // Parse dates
        const checkInDate = new Date(checkIn);
        const checkOutDate = new Date(checkOut);
        
        // Make sure check-out is after check-in
        if (checkInDate >= checkOutDate) {
            calculator.style.display = 'none';
            console.log('Check-out must be after check-in');
            return;
        }
        
        // Calculate nights
        const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        console.log('Nights calculated:', nights);
        
        if (nights <= 0) {
            calculator.style.display = 'none';
            return;
        }
        
        // Calculate total
        const total = nights * cottagePrice;
        
        console.log('Total calculated:', total);
        
        // Update display
        document.getElementById('ratePerNight').textContent = '₱' + cottagePrice.toFixed(2);
        document.getElementById('nightsCount').textContent = nights + ' night' + (nights !== 1 ? 's' : '');
        document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
        
        // Show calculator
        calculator.style.display = 'block';
        console.log('Calculator shown');
    } else {
        calculator.style.display = 'none';
        console.log('Calculator hidden - missing dates');
    }
}

// Form validation
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests = document.getElementById('guests').value;
    
    if (!checkIn || !checkOut) {
        e.preventDefault();
        alert('Please select both check-in and check-out dates.');
        return false;
    }
    
    if (checkIn >= checkOut) {
        e.preventDefault();
        alert('Check-out date must be after check-in date.');
        return false;
    }
    
    if (guests < 1 || guests > cottageCapacity) {
        e.preventDefault();
        alert('Please select a valid number of guests.');
        return false;
    }
    
    // Confirm reservation
    if (!confirm('Are you sure you want to proceed with this reservation? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
    </script>
</body>
</html>