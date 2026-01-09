/**
 * @fileoverview DoRegister Plugin - Multi-Step Registration and Login System
 * 
 * This JavaScript module handles all frontend functionality for the DoRegister WordPress plugin.
 * It manages a 5-step registration form, login form, form validation, AJAX submissions,
 * localStorage auto-save, and user interactions.
 * 
 * Architecture:
 * - Uses IIFE (Immediately Invoked Function Expression) to avoid global namespace pollution
 * - jQuery dependency: Passed as parameter to IIFE ($)
 * - Event delegation: Uses $(document).on() for dynamic content support
 * - Object-oriented: DoRegister object contains all methods and state
 * 
 * Key Features:
 * - Multi-step form navigation with validation
 * - Real-time field validation
 * - Password strength meter
 * - Email uniqueness checking via AJAX
 * - File upload with preview (FileReader API)
 * - localStorage persistence (auto-save/restore)
 * - AJAX form submissions (no page reload)
 * - Custom event system (doregister:stepChanged)
 * 
 * Security:
 * - HTML escaping to prevent XSS attacks
 * - Nonce verification for AJAX requests
 * - Input sanitization and validation
 * 
 * @requires jQuery
 * @author DoRegister Plugin
 * @since 1.0.0
 */

// IIFE (Immediately Invoked Function Expression)
// Wraps code to avoid global namespace pollution
// $ parameter receives jQuery (ensures jQuery is available even if $ conflicts with other libraries)
(function($) {
    // 'use strict': Enables strict mode for better error catching and performance
    // Prevents common JavaScript mistakes (e.g., undeclared variables)
    'use strict';
    
    /**
     * Main DoRegister object
     * 
     * Contains all methods and state for the registration/login system.
     * Uses object literal pattern (not a class) for simplicity.
     * 
     * @namespace DoRegister
     * @property {number} currentStep - Current step number in registration form (1-5)
     * @property {number} totalSteps - Total number of steps in registration form (5)
     * @property {Object} formData - Stores all form field values and current step
     * @property {Array<string>} countries - Array of country names for searchable dropdown
     */
    var DoRegister = {
        // Current step in multi-step form (1 = first step)
        // Updated when user navigates between steps
        currentStep: 1,
        
        // Total number of steps in registration form
        // Used for progress bar calculation and validation loops
        totalSteps: 5,
        
        // Form data object: Stores all field values and metadata
        // Structure: { full_name: '...', email: '...', currentStep: 1, ... }
        // Persisted to localStorage for auto-save functionality
        formData: {},
        
        // Country list for searchable dropdown
        // Populated from doregisterData.countries (passed from PHP via wp_localize_script)
        countries: [],
        
        /**
         * Initialize the plugin
         * 
         * Entry point called when DOM is ready.
         * Sets up all event handlers and restores saved form data.
         * 
         * Execution order:
         * 1. Load saved data from localStorage
         * 2. Initialize registration form handlers
         * 3. Initialize login form handlers
         * 4. Initialize country dropdown search
         * 5. Initialize navigation links
         * 6. Initialize logout handler
         * 
         * @method init
         * @returns {void}
         */
        init: function() {
            // Restore form data from localStorage (if user refreshed page)
            // This must happen first so restored data is available to other init methods
            this.loadFromStorage();
            
            // Set up registration form event handlers
            // Handles: step navigation, validation, file upload, form submission
            this.initRegistrationForm();
            
            // Set up login form event handlers
            // Handles: form submission, field validation
            this.initLoginForm();
            
            // Set up country searchable dropdown
            // Handles: filtering countries as user types, selection
            this.initCountryDropdown();
            
            // Set up navigation links between login/registration pages
            // Handles: clicking "Login here" / "Register here" links
            this.initNavigationLinks();
            
            // Set up logout button handler
            // Handles: logout AJAX request
            this.initLogout();
            
            // Set up profile edit mode functionality
            // Handles: edit button, cancel button, form submission
            this.initProfileEdit();
            
            // Set up profile edit form features
            // Handles: password toggle, photo upload, country dropdown
            this.initProfileEditFeatures();
            
            // Set up profile edit form validation
            // Handles: real-time validation on blur, password strength, etc.
            this.initProfileEditValidation();
        },
        
        /**
         * Initialize registration form event handlers
         * 
         * Sets up all event listeners for the multi-step registration form.
         * Uses event delegation ($(document).on()) so handlers work even if form
         * is dynamically added to the page.
         * 
         * Event Handlers:
         * - Next/Back button clicks (step navigation)
         * - Field blur events (real-time validation)
         * - Password input (strength checking)
         * - Email blur (uniqueness check via AJAX)
         * - Phone input (character filtering)
         * - Checkbox change (interests validation)
         * - File input change (photo upload)
         * - Form submit (final submission)
         * - Custom stepChanged event (review summary update)
         * 
         * @method initRegistrationForm
         * @returns {void}
         */
        initRegistrationForm: function() {
            // Store reference to 'this' for use in callbacks
            // In jQuery callbacks, 'this' refers to the DOM element, not the DoRegister object
            var self = this;
            
            // INITIALIZE STEP VISIBILITY: Hide all steps first, then show correct one
            // This ensures only one step is visible on page load (normal reload or hard refresh)
            // Fixes issue where multiple steps could be visible after page reload
            $('.doregister-step').each(function() {
                var $step = $(this);
                var stepNumber = parseInt($step.data('step'), 10);
                // Hide all steps except the one that should be active
                if (stepNumber !== self.currentStep) {
                    $step.removeClass('doregister-step-active slide-in-left slide-in-right').addClass('doregister-step-hidden');
                }
            });
            
            // Restore form data from localStorage to form fields
            // Populates fields with previously entered values (if page was refreshed)
            this.restoreFormData();
            
            // PASSWORD VISIBILITY TOGGLE: Show/hide password functionality
            // Handles clicks on password toggle buttons
            $(document).on('click', '.doregister-password-toggle', function(e) {
                // Prevent default button behavior and stop event propagation
                e.preventDefault();
                e.stopPropagation();
                
                // Get the toggle button
                var $toggle = $(this);
                
                // Find the associated password input within the same wrapper
                // The input and button are siblings within .doregister-password-wrapper
                var $wrapper = $toggle.closest('.doregister-password-wrapper');
                
                // Find the input field - it should be a direct child of the wrapper
                var $input = $wrapper.find('input[type="password"], input[type="text"]').first();
                
                // Alternative: try finding by class
                if ($input.length === 0) {
                    $input = $wrapper.find('.doregister-password-input');
                }
                
                // Check if input was found
                if ($input.length === 0) {
                    console.error('Password input not found for toggle button');
                    return false;
                }
                
                // Get current input type
                var currentType = $input.attr('type') || 'password';
                
                // Toggle password visibility
                if (currentType === 'password') {
                    // Show password: Change input type to text
                    $input.attr('type', 'text');
                    $toggle.addClass('active');
                    // Update icon to "hide" icon
                    $toggle.find('.doregister-password-toggle-icon').text('🙈');
                    $toggle.attr('aria-label', 'Hide password');
                } else {
                    // Hide password: Change input type back to password
                    $input.attr('type', 'password');
                    $toggle.removeClass('active');
                    // Update icon to "show" icon
                    $toggle.find('.doregister-password-toggle-icon').text('👁️');
                    $toggle.attr('aria-label', 'Show password');
                }
                
                // Return false to prevent any further event handling
                return false;
            });
            
            // NEXT BUTTON HANDLER: Navigate to next step
            // Event delegation: Works even if button is added dynamically
            // $(document).on() attaches handler to document, listens for clicks on matching elements
            $(document).on('click', '.doregister-btn-next', function(e) {
                // Prevent default button behavior (form submission, page navigation)
                e.preventDefault();
                
                // Get target step number from data attribute
                // Example: <button data-next-step="2"> gets step 2
                var nextStep = $(this).data('next-step');
                
                // Validate current step before allowing navigation
                // validateStep() returns true if all required fields are valid
                if (self.validateStep(self.currentStep)) {
                    // Validation passed: Save current step data to formData object
                    self.saveStepData(self.currentStep);
                    
                    // Navigate to next step (updates UI, progress bar, step indicator)
                    self.goToStep(nextStep);
                } else {
                    // Validation failed: Scroll to first error field
                    // Find first field with error class in current step
                    var $firstError = $('.doregister-step[data-step="' + self.currentStep + '"]').find('.doregister-input-error').first();
                    
                    // If error field exists, scroll to it
                    if ($firstError.length) {
                        // Smooth scroll animation to error field
                        // offset().top - 100: Position 100px above field (for better visibility)
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 500); // 500ms animation duration
                    }
                }
            });
            
            // BACK BUTTON HANDLER: Navigate to previous step
            // No validation needed - user can always go back
            $(document).on('click', '.doregister-btn-back', function(e) {
                // Prevent default behavior
                e.preventDefault();
                
                // Get previous step number from data attribute
                var prevStep = $(this).data('prev-step');
                
                // Save current step data before navigating back
                // Ensures data is preserved even when going backwards
                self.saveStepData(self.currentStep);
                
                // Navigate to previous step
                self.goToStep(prevStep);
            });
            
            // REAL-TIME VALIDATION: Validate field when user leaves it (blur event)
            // blur event fires when field loses focus (user clicks away or tabs out)
            // Validates all input and select fields in registration form
            $(document).on('blur', '#doregister-registration-form input, #doregister-registration-form select', function() {
                // Validate the field that just lost focus
                // $(this) refers to the field that triggered the event
                self.validateField($(this));
            });
            
            // PASSWORD STRENGTH CHECK: Update strength meter as user types
            // input event fires on every keystroke (more responsive than blur)
            $(document).on('input', '#password', function() {
                // Check password strength and update visual meter
                // Passes current password value to strength checker
                self.checkPasswordStrength($(this).val());
            });
            
            // CONFIRM PASSWORD VALIDATION: Check if passwords match in real-time
            // Validates as user types in confirm password field
            $(document).on('input', '#confirm_password', function() {
                // Get both password values
                var password = $('#password').val();
                var confirmPassword = $(this).val(); // $(this) = confirm_password field
                
                // Only validate if both fields have values
                // Prevents showing error when user hasn't finished typing
                if (confirmPassword && password !== confirmPassword) {
                    // Passwords don't match: Show error
                    self.showFieldError($(this), 'Passwords do not match.');
                } else {
                    // Passwords match (or one is empty): Clear error
                    self.clearFieldError($(this));
                }
            });
            
            // EMAIL UNIQUENESS CHECK: Verify email isn't already registered
            // Runs on blur (when user leaves email field) to avoid excessive AJAX calls
            $(document).on('blur', '#email', function() {
                var email = $(this).val();
                
                // Only check if email exists and is valid format
                // Avoids unnecessary AJAX call for empty/invalid emails
                if (email && self.isValidEmail(email)) {
                    // Make AJAX request to check if email exists in database
                    self.checkEmailUniqueness(email);
                }
            });
            
            // PHONE NUMBER VALIDATION: Filter out invalid characters as user types
            // input event: Filters on every keystroke
            // Rules: Only digits and + (for country code at start), no letters, no spaces
            $(document).on('input', '#phone_number', function() {
                var phone = $(this).val();
                
                // Remove all non-digit characters except + at the start
                // First, remove everything except digits and +
                var cleaned = phone.replace(/[^0-9+]/g, '');
                
                // Ensure + only appears at the start (if it exists)
                if (cleaned.indexOf('+') !== -1) {
                    // If + exists but not at start, remove all + and add one at start
                    if (cleaned.indexOf('+') !== 0) {
                        cleaned = cleaned.replace(/\+/g, '');
                        cleaned = '+' + cleaned;
                    } else {
                        // + is at start, but might have more + characters - remove them
                        var hasPlus = cleaned[0] === '+';
                        cleaned = cleaned.replace(/\+/g, '');
                        if (hasPlus) {
                            cleaned = '+' + cleaned;
                        }
                    }
                }
                
                $(this).val(cleaned);
            });
            
            // INTERESTS VALIDATION: Check if at least one interest is selected
            // Runs when any checkbox is checked/unchecked
            $(document).on('change', '.doregister-checkbox', function() {
                // Validate that at least one interest is selected
                self.validateInterests();
            });
            
            // PROFILE PHOTO UPLOAD: Handle file selection
            // change event fires when user selects a file
            $(document).on('change', '#profile_photo', function() {
                // Get first file from file input
                // files[0]: FileList is array-like, [0] gets first file
                // $(this)[0]: Get native DOM element from jQuery object
                self.handlePhotoUpload($(this)[0].files[0]);
            });
            
            // FORM SUBMISSION: Handle final form submit (Step 5)
            // submit event fires when user clicks submit button or presses Enter
            $(document).on('submit', '#doregister-registration-form', function(e) {
                // Prevent default form submission (page reload)
                // We'll submit via AJAX instead
                e.preventDefault();
                
                // Call custom submission handler
                self.submitRegistration();
            });
            
            // REVIEW SUMMARY UPDATE: Populate Step 5 summary when step is reached
            // Listens for custom event 'doregister:stepChanged' (triggered by goToStep())
            // Custom events allow decoupled communication between methods
            $(document).on('doregister:stepChanged', function(e, step) {
                // Only update summary when reaching Step 5 (Review & Confirm)
                if (step === 5) {
                    // Populate review summary with all collected form data
                    self.updateReviewSummary();
                }
            });
        },
        
        /**
         * Initialize login form event handlers
         * 
         * Sets up event listeners for the login form.
         * Simpler than registration form (single step, no navigation).
         * 
         * Event Handlers:
         * - Form submission (AJAX login)
         * - Field blur (real-time validation)
         * 
         * @method initLoginForm
         * @returns {void}
         */
        initLoginForm: function() {
            var self = this;
            
            // FORM SUBMISSION: Handle login form submit
            $(document).on('submit', '#doregister-login-form', function(e) {
                // Prevent default form submission
                e.preventDefault();
                
                // Submit login credentials via AJAX
                self.submitLogin();
            });
            
            // REAL-TIME VALIDATION: Validate login fields on blur
            // Validates email/username and password fields
            $(document).on('blur', '#doregister-login-form input', function() {
                // Validate the field that lost focus
                self.validateLoginField($(this));
            });
        },
        
        /**
         * Initialize country searchable dropdown
         * 
         * Creates a searchable/filterable country dropdown.
         * As user types, filters country list and displays matching results.
         * 
         * Features:
         * - Real-time filtering as user types
         * - Limits results to 10 for performance
         * - Click to select country
         * - Auto-hide when clicking outside
         * - HTML escaping to prevent XSS
         * 
         * Data Source:
         * - Countries list passed from PHP via doregisterData.countries
         * - Set in class-doregister-assets.php via wp_localize_script()
         * 
         * @method initCountryDropdown
         * @returns {void}
         */
        initCountryDropdown: function() {
            var self = this;
            
            // Load country list from PHP (passed via wp_localize_script)
            // doregisterData is a global object created by WordPress
            // Fallback to empty array if not available
            this.countries = doregisterData.countries || [];
            
            // COUNTRY SEARCH: Filter countries as user types
            // input event: Fires on every keystroke
            $(document).on('input', '.doregister-country-search', function() {
                // Get search term and convert to lowercase for case-insensitive matching
                var searchTerm = $(this).val().toLowerCase();
                
                // Find dropdown container (sibling element)
                // .siblings(): Finds sibling elements with matching selector
                var $dropdown = $(this).siblings('.doregister-country-dropdown');
                
                // If search term is empty, hide dropdown
                // Prevents showing all countries when field is empty
                if (searchTerm.length < 1) {
                    $dropdown.hide().empty(); // Hide and clear content
                    return; // Exit early
                }
                
                // Filter countries array: Keep only countries that include search term
                // .filter(): Creates new array with matching items
                // .toLowerCase().includes(): Case-insensitive substring search
                // .slice(0, 10): Limit to first 10 results (performance optimization)
                var filtered = self.countries.filter(function(country) {
                    return country.toLowerCase().includes(searchTerm);
                }).slice(0, 10);
                
                // If matching countries found, display them
                if (filtered.length > 0) {
                    // Build HTML list of country options
                    var html = '<ul class="doregister-country-list">';
                    
                    // Loop through filtered countries and create list items
                    filtered.forEach(function(country) {
                        // escapeHtml(): Prevents XSS attacks (escapes special characters)
                        // data-country attribute: Stores country name for click handler
                        html += '<li class="doregister-country-item" data-country="' + self.escapeHtml(country) + '">' + self.escapeHtml(country) + '</li>';
                    });
                    html += '</ul>';
                    
                    // Insert HTML and show dropdown
                    $dropdown.html(html).show();
                } else {
                    // No matches found: Hide dropdown
                    $dropdown.hide().empty();
                }
            });
            
            // COUNTRY SELECTION: Handle clicking on a country option
            $(document).on('click', '.doregister-country-item', function() {
                // Get country name from data attribute
                // .data('country'): jQuery method to read data-* attributes
                var country = $(this).data('country');
                
                // Set country name in search input field
                $('.doregister-country-search').val(country);
                
                // Hide dropdown and clear its content
                $('.doregister-country-dropdown').hide().empty();
                
                // Clear any validation errors on the country field
                self.clearFieldError($('.doregister-country-search'));
                
                // AUTO-FILL PHONE CODE: Add country phone code to phone number field
                // Check if country phone codes are available
                if (typeof doregisterData !== 'undefined' && doregisterData.countryPhoneCodes) {
                    var phoneCode = doregisterData.countryPhoneCodes[country];
                    
                    if (phoneCode) {
                        // Get phone number field (works for both registration and profile forms)
                        var $phoneField = $('#phone_number, #profile_phone_number');
                        
                        if ($phoneField.length) {
                            var originalValue = $phoneField.val().trim();
                            
                            // Remove any existing phone code from the value
                            // Pattern: Remove leading + and digits at the start (1-4 digits for country codes)
                            var phoneNumber = originalValue.replace(/^\+\d{1,4}/, '');
                            
                            // Add phone code as prefix (no space, as per validation rules)
                            if (phoneNumber) {
                                $phoneField.val(phoneCode + phoneNumber);
                            } else {
                                // If field is empty, just add the code
                                $phoneField.val(phoneCode);
                            }
                            
                            // Trigger input event to update any listeners and apply filters
                            $phoneField.trigger('input');
                        }
                    }
                }
            });
            
            // CLICK OUTSIDE: Hide dropdown when clicking outside country wrapper
            // Attached to document to catch clicks anywhere on page
            $(document).on('click', function(e) {
                // Check if click target is NOT inside country wrapper
                // .closest(): Traverses up DOM tree to find matching ancestor
                // .length: Returns 0 if not found (falsy)
                if (!$(e.target).closest('.doregister-country-wrapper').length) {
                    // Click was outside: Hide dropdown
                    $('.doregister-country-dropdown').hide();
                }
            });
        },
        
        /**
         * Initialize navigation links between login and registration pages
         * 
         * Handles clicks on "Login here" and "Register here" links.
         * Navigates between login and registration pages.
         * 
         * Note: URLs are hardcoded and may need adjustment based on actual page slugs.
         * 
         * @method initNavigationLinks
         * @returns {void}
         */
        initNavigationLinks: function() {
            // LOGIN LINK: Navigate to login page from registration page
            $(document).on('click', '.doregister-link-to-login', function(e) {
                // Prevent default link behavior
                e.preventDefault();
                
                // Navigate to login page
                // window.location.origin: Gets current domain (e.g., "http://localhost")
                // '/login': Assumes login page slug is "login" (adjust if different)
                window.location.href = window.location.origin + '/login';
            });
            
            // REGISTRATION LINK: Navigate to registration page from login page
            $(document).on('click', '.doregister-link-to-register', function(e) {
                // Prevent default link behavior
                e.preventDefault();
                
                // Navigate to registration page
                // '/registration': Assumes registration page slug is "registration" (adjust if different)
                window.location.href = window.location.origin + '/registration';
            });
        },
        
        /**
         * Initialize logout button handler
         * 
         * Handles logout button clicks on profile page.
         * Makes AJAX request to destroy session and redirects to login.
         * 
         * @method initLogout
         * @returns {void}
         */
        initLogout: function() {
            var self = this;
            
            // LOGOUT BUTTON: Handle logout click
            $(document).on('click', '.doregister-btn-logout', function(e) {
                // Prevent default button behavior
                e.preventDefault();
                
                // Show confirmation dialog before logging out
                var confirmLogout = confirm('Are you sure you want to logout?');
                
                // Only proceed with logout if user confirms
                if (confirmLogout) {
                    // Call logout handler (makes AJAX request and redirects)
                    self.handleLogout();
                }
                // If user cancels, do nothing (stay on page)
            });
        },
        
        /**
         * Initialize Profile Edit Mode functionality
         * 
         * Handles:
         * - Edit button click (enters edit mode)
         * - Cancel button click (exits edit mode)
         * - Form submission (saves profile changes)
         * 
         * @method initProfileEdit
         * @returns {void}
         */
        initProfileEdit: function() {
            var self = this;
            var $wrapper = $('.doregister-profile-wrapper');
            
            // Only initialize if profile wrapper exists (on profile page)
            if ($wrapper.length === 0) {
                return;
            }
            
            // EDIT BUTTON: Enter edit mode
            $(document).on('click', '.doregister-btn-edit', function(e) {
                e.preventDefault();
                
                // Add edit-mode class to wrapper
                $wrapper.addClass('edit-mode');
                
                // Explicitly show edit mode elements (override inline styles)
                $('.doregister-profile-edit-mode').show();
                $('.doregister-profile-view-mode').hide();
                
                // Populate form with current data (ensure all fields are filled)
                self.populateProfileEditForm();
                
                // Focus on first input field for better UX
                $('#profile_full_name').focus();
            });
            
            // CANCEL BUTTON: Exit edit mode
            $(document).on('click', '.doregister-btn-cancel', function(e) {
                e.preventDefault();
                
                // Remove edit-mode class
                $wrapper.removeClass('edit-mode');
                
                // Explicitly hide edit mode and show view mode
                $('.doregister-profile-edit-mode').hide();
                $('.doregister-profile-view-mode').show();
                
                // Clear any error messages
                self.clearProfileFormErrors();
                
                // Reset form to original values (optional - could restore from data attributes)
                // For now, just exit edit mode
            });
            
            // FORM SUBMISSION: Handle profile update (form submit event)
            $(document).on('submit', '#doregister-profile-edit-form', function(e) {
                e.preventDefault();
                
                // Call profile update handler
                self.handleProfileUpdate();
            });
            
            // SAVE BUTTON CLICK: Handle save button click (in case button is outside form)
            $(document).on('click', '.doregister-btn-save', function(e) {
                e.preventDefault();
                
                console.log('DoRegister: Save button clicked');
                
                // Call profile update handler
                self.handleProfileUpdate();
            });
        },
        
        /**
         * Handle Profile Update Submission
         * 
         * Validates form data and submits via AJAX.
         * Shows success/error messages and handles response.
         * 
         * @method handleProfileUpdate
         * @returns {void}
         */
        handleProfileUpdate: function() {
            var self = this;
            var $form = $('#doregister-profile-edit-form');
            var $messages = $('.doregister-form-messages');
            
            // Debug: Check if form exists
            if ($form.length === 0) {
                console.error('DoRegister: Profile edit form not found');
                return;
            }
            
            // Debug: Check if nonce is available
            if (!doregisterData || !doregisterData.profileUpdateNonce) {
                console.error('DoRegister: Profile update nonce not available');
                return;
            }
            
            // Clear previous errors
            self.clearProfileFormErrors();
            $messages.empty().removeClass('doregister-success doregister-error');
            
            // Get form data
            var formData = {
                action: 'doregister_update_profile',
                nonce: doregisterData.profileUpdateNonce,
                user_id: $('input[name="user_id"]').val() || $('.doregister-profile-wrapper').data('user-id'), // Get user ID from form or wrapper
                full_name: $('#profile_full_name').val().trim(),
                email: $('#profile_email').val().trim(),
                phone_number: $('#profile_phone_number').val().trim(),
                country: $('#profile_country').val().trim(),
                city: $('#profile_city').val().trim(),
                gender: $('input[name="gender"]:checked').val() || '',
                date_of_birth: $('#profile_date_of_birth').val(),
                interests: $('input[name="interests[]"]:checked').map(function() { return $(this).val(); }).get(),
                profile_photo: $('#profile_photo').val(),
                change_password: $('#change_password_toggle').is(':checked'),
                password: '',
                confirm_password: ''
            };
            
            // Handle password change if toggle is checked
            if (formData.change_password) {
                formData.password = $('#profile_password').val();
                formData.confirm_password = $('#profile_confirm_password').val();
            }
            
            // Basic frontend validation
            var errors = {};
            
            if (!formData.full_name) {
                errors.full_name = 'Full name is required.';
            }
            
            if (!formData.email) {
                errors.email = 'Email is required.';
            } else if (!self.isValidEmail(formData.email)) {
                errors.email = 'Please enter a valid email address.';
            }
            
            if (!formData.phone_number) {
                errors.phone_number = 'Phone number is required.';
            }
            
            if (!formData.country) {
                errors.country = 'Country is required.';
            }
            
            if (formData.interests.length < 1) {
                errors.interests = 'Please select at least one interest.';
            }
            
            if (!formData.profile_photo) {
                errors.profile_photo = 'Profile photo is required.';
            }
            
            // Password validation (only if change password is checked)
            if (formData.change_password) {
                if (!formData.password || formData.password.length < 8) {
                    errors.password = 'Password must be at least 8 characters.';
                }
                if (formData.password !== formData.confirm_password) {
                    errors.confirm_password = 'Passwords do not match.';
                }
            }
            
            // Validate all fields using real-time validation before submission
            var isValid = true;
            $('#doregister-profile-edit-form input[required], #doregister-profile-edit-form select[required]').each(function() {
                var $field = $(this);
                // Skip hidden fields, checkboxes, radio buttons, and file inputs
                if ($field.attr('type') === 'hidden' || $field.attr('type') === 'file' || 
                    $field.attr('type') === 'checkbox' || $field.attr('type') === 'radio') {
                    return true; // Continue to next field
                }
                
                if (!self.validateProfileField($field)) {
                    isValid = false;
                }
            });
            
            // Validate interests separately
            if (!self.validateProfileInterests()) {
                isValid = false;
            }
            
            // Validate password fields if password change is enabled
            if (formData.change_password) {
                if (!formData.password || formData.password.length < 8) {
                    self.showFieldError($('#profile_password'), 'Password must be at least 8 characters.');
                    isValid = false;
                }
                if (formData.password !== formData.confirm_password) {
                    self.showFieldError($('#profile_confirm_password'), 'Passwords do not match.');
                    isValid = false;
                }
            }
            
            // Display errors if any
            if (!isValid || Object.keys(errors).length > 0) {
                console.log('DoRegister: Validation failed', { isValid: isValid, errors: errors });
                if (Object.keys(errors).length > 0) {
                    self.displayProfileFormErrors(errors);
                }
                // Show a general error message if validation failed
                if (!isValid) {
                    $messages.html('<div class="doregister-message doregister-error">Please fix the errors below.</div>').addClass('doregister-error');
                }
                return;
            }
            
            console.log('DoRegister: Validation passed, submitting form...');
            
            // Disable submit button during request
            var $submitBtn = $('.doregister-btn-save');
            $submitBtn.prop('disabled', true).text('Saving...');
            
            // AJAX Request
            console.log('DoRegister: Sending AJAX request', formData);
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('DoRegister: AJAX success', response);
                    if (response.success) {
                        // SUCCESS: Show success message
                        $messages.html('<div class="doregister-message doregister-success">' + 
                            self.escapeHtml(response.data.message || 'Profile updated successfully!') + 
                            '</div>').addClass('doregister-success');
                        
                        // Exit edit mode after short delay
                        setTimeout(function() {
                            $('.doregister-profile-wrapper').removeClass('edit-mode');
                            // Reload page to show updated data (or update DOM dynamically)
                            location.reload();
                        }, 1500);
                    } else {
                        // ERROR: Show error message and field errors
                        $messages.html('<div class="doregister-message doregister-error">' + 
                            self.escapeHtml(response.data.message || 'Failed to update profile.') + 
                            '</div>').addClass('doregister-error');
                        
                        // Display field-specific errors if provided
                        if (response.data.errors) {
                            self.displayProfileFormErrors(response.data.errors);
                        }
                        
                        // Re-enable submit button
                        $submitBtn.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX ERROR: Show generic error message
                    console.error('DoRegister: AJAX error', { xhr: xhr, status: status, error: error });
                    $messages.html('<div class="doregister-message doregister-error">' + 
                        'An error occurred. Please try again.' + 
                        '</div>').addClass('doregister-error');
                    
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text('Save Changes');
                }
            });
        },
        
        /**
         * Display Profile Form Errors
         * 
         * Shows error messages next to corresponding form fields.
         * 
         * @method displayProfileFormErrors
         * @param {Object} errors - Object with field names as keys and error messages as values
         * @returns {void}
         */
        displayProfileFormErrors: function(errors) {
            // Map field names to input IDs or selectors
            var fieldMap = {
                'full_name': '#profile_full_name',
                'email': '#profile_email',
                'phone_number': '#profile_phone_number',
                'country': '#profile_country',
                'city': '#profile_city',
                'gender': 'input[name="gender"]',
                'date_of_birth': '#profile_date_of_birth',
                'interests': '.doregister-checkbox-group',
                'profile_photo': '#profile_photo_upload',
                'password': '#profile_password',
                'confirm_password': '#profile_confirm_password'
            };
            
            // Display error for each field
            for (var field in errors) {
                if (errors.hasOwnProperty(field) && fieldMap[field]) {
                    var $field = $(fieldMap[field]);
                    
                    // For checkboxes/radio groups, find the container
                    if (field === 'interests') {
                        var $errorMsg = $field.siblings('.doregister-error-message');
                        $errorMsg.text(errors[field]).show();
                    } else {
                        var $errorMsg = $field.siblings('.doregister-error-message');
                        
                        // Add error class to input
                        $field.addClass('doregister-input-error');
                        
                        // Display error message
                        $errorMsg.text(errors[field]).show();
                    }
                }
            }
        },
        
        /**
         * Clear Profile Form Errors
         * 
         * Removes error styling and messages from all form fields.
         * 
         * @method clearProfileFormErrors
         * @returns {void}
         */
        clearProfileFormErrors: function() {
            // Remove error class from all inputs
            $('#doregister-profile-edit-form .doregister-input').removeClass('doregister-input-error');
            
            // Clear all error messages
            $('#doregister-profile-edit-form .doregister-error-message').text('').hide();
        },
        
        /**
         * Initialize Profile Edit Form Features
         * 
         * Handles:
         * - Password change toggle
         * - Photo upload preview
         * - Country dropdown initialization
         * 
         * @method initProfileEditFeatures
         * @returns {void}
         */
        initProfileEditFeatures: function() {
            var self = this;
            var $wrapper = $('.doregister-profile-wrapper');
            
            // Only initialize if profile wrapper exists
            if ($wrapper.length === 0) {
                return;
            }
            
            // PASSWORD CHANGE TOGGLE: Show/hide password fields
            $(document).on('change', '#change_password_toggle', function() {
                var $passwordFields = $('.doregister-password-change-fields');
                if ($(this).is(':checked')) {
                    $passwordFields.slideDown(300);
                    // Make password fields required
                    $('#profile_password, #profile_confirm_password').prop('required', true);
                } else {
                    $passwordFields.slideUp(300);
                    // Remove required and clear values
                    $('#profile_password, #profile_confirm_password').prop('required', false).val('');
                    // Clear errors
                    $('#profile_password, #profile_confirm_password').removeClass('doregister-input-error');
                    $('#profile_password, #profile_confirm_password').siblings('.doregister-error-message').text('').hide();
                }
            });
            
            // PHOTO UPLOAD PREVIEW: Show preview when file is selected
            $(document).on('change', '#profile_photo_upload', function(e) {
                var file = e.target.files[0];
                if (file) {
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        self.showFieldError($(this), 'Please select an image file.');
                        return;
                    }
                    
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        self.showFieldError($(this), 'File size must be less than 5MB.');
                        return;
                    }
                    
                    // Use FileReader to preview image
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var $preview = $('#doregister-profile-edit-form .doregister-image-preview');
                        $preview.html('<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 10px;">');
                        
                        // Upload file via AJAX
                        self.uploadProfilePhoto(file);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Initialize country dropdown for profile form
            // Only if country search input exists in profile form
            if ($('#profile_country.doregister-country-search').length > 0) {
                // Reuse the country dropdown initialization from registration
                // The initCountryDropdown method should handle both forms
                // But we need to make sure it works with profile form IDs
                self.initProfileCountryDropdown();
            }
        },
        
        /**
         * Initialize Country Dropdown for Profile Form
         * 
         * Similar to registration form but uses profile-specific IDs.
         * 
         * @method initProfileCountryDropdown
         * @returns {void}
         */
        initProfileCountryDropdown: function() {
            var self = this;
            var $countryInput = $('#profile_country.doregister-country-search');
            var $dropdown = $countryInput.siblings('.doregister-country-dropdown');
            
            if ($countryInput.length === 0 || !doregisterData.countries) {
                return;
            }
            
            var countries = doregisterData.countries;
            
            // Show dropdown on focus
            $countryInput.on('focus', function() {
                var searchTerm = $(this).val().toLowerCase();
                // Filter countries
                var filtered = countries.filter(function(country) {
                    return country.toLowerCase().includes(searchTerm);
                }).slice(0, 10);
                
                if (filtered.length > 0) {
                    var html = '<ul class="doregister-country-list">';
                    filtered.forEach(function(country) {
                        html += '<li class="doregister-country-item" data-country="' + self.escapeHtml(country) + '">' + self.escapeHtml(country) + '</li>';
                    });
                    html += '</ul>';
                    $dropdown.html(html).show();
                } else {
                    $dropdown.hide().empty();
                }
            });
            
            // Filter countries as user types
            $countryInput.on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                // Filter countries
                var filtered = countries.filter(function(country) {
                    return country.toLowerCase().includes(searchTerm);
                }).slice(0, 10);
                
                if (filtered.length > 0) {
                    var html = '<ul class="doregister-country-list">';
                    filtered.forEach(function(country) {
                        html += '<li class="doregister-country-item" data-country="' + self.escapeHtml(country) + '">' + self.escapeHtml(country) + '</li>';
                    });
                    html += '</ul>';
                    $dropdown.html(html).show();
                } else {
                    $dropdown.hide().empty();
                }
            });
            
            // Handle country selection
            $dropdown.on('click', '.doregister-country-item', function() {
                var country = $(this).data('country');
                $countryInput.val(country);
                $dropdown.hide().empty();
                
                // AUTO-FILL PHONE CODE: Add country phone code to phone number field
                // Check if country phone codes are available
                if (typeof doregisterData !== 'undefined' && doregisterData.countryPhoneCodes) {
                    var phoneCode = doregisterData.countryPhoneCodes[country];
                    
                    if (phoneCode) {
                        // Get phone number field in profile form
                        var $phoneField = $('#profile_phone_number');
                        
                        if ($phoneField.length) {
                            var originalValue = $phoneField.val().trim();
                            
                            // Remove any existing phone code from the value
                            // Pattern: Remove leading + and digits at the start (1-4 digits for country codes)
                            var phoneNumber = originalValue.replace(/^\+\d{1,4}/, '');
                            
                            // Add phone code as prefix (no space, as per validation rules)
                            if (phoneNumber) {
                                $phoneField.val(phoneCode + phoneNumber);
                            } else {
                                // If field is empty, just add the code
                                $phoneField.val(phoneCode);
                            }
                            
                            // Trigger input event to update any listeners and apply filters
                            $phoneField.trigger('input');
                        }
                    }
                }
            });
            
            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.doregister-country-wrapper').length) {
                    $dropdown.hide();
                }
            });
        },
        
        /**
         * Upload Profile Photo via AJAX
         * 
         * Uploads the selected photo file and stores the URL in hidden field.
         * 
         * @method uploadProfilePhoto
         * @param {File} file - The file to upload
         * @returns {void}
         */
        uploadProfilePhoto: function(file) {
            var self = this;
            var formData = new FormData();
            formData.append('action', 'doregister_upload_photo');
            formData.append('nonce', doregisterData.nonce);
            formData.append('profile_photo', file);
            
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.url) {
                        // Store uploaded photo URL in hidden field
                        $('#profile_photo').val(response.data.url);
                        // Clear any errors
                        $('#profile_photo_upload').removeClass('doregister-input-error');
                        $('#profile_photo_upload').siblings('.doregister-error-message').text('').hide();
                    } else {
                        self.showFieldError($('#profile_photo_upload'), response.data.message || 'Photo upload failed.');
                    }
                },
                error: function() {
                    self.showFieldError($('#profile_photo_upload'), 'An error occurred while uploading the photo.');
                }
            });
        },
        
        /**
         * Populate Profile Edit Form
         * 
         * Ensures all form fields are properly populated when entering edit mode.
         * This is mainly for fields that might need special handling (like interests).
         * 
         * @method populateProfileEditForm
         * @returns {void}
         */
        populateProfileEditForm: function() {
            // Form fields are already pre-filled via PHP value attributes
            // This method is here for any additional JavaScript-based population if needed
            // For example, if we need to set values dynamically or handle special cases
            
            // Clear any existing errors when entering edit mode
            this.clearProfileFormErrors();
        },
        
        /**
         * Initialize Profile Edit Form Validation
         * 
         * Sets up real-time validation for all profile edit form fields.
         * Similar to registration form validation but for profile edit form.
         * 
         * @method initProfileEditValidation
         * @returns {void}
         */
        initProfileEditValidation: function() {
            var self = this;
            var $form = $('#doregister-profile-edit-form');
            
            // Only initialize if profile edit form exists
            if ($form.length === 0) {
                return;
            }
            
            // REAL-TIME VALIDATION: Validate field when user leaves it (blur event)
            $(document).on('blur', '#doregister-profile-edit-form input, #doregister-profile-edit-form select', function() {
                // Skip validation for hidden fields and file inputs
                var $field = $(this);
                if ($field.attr('type') === 'hidden' || $field.attr('type') === 'file' || $field.attr('type') === 'checkbox' || $field.attr('type') === 'radio') {
                    return;
                }
                
                // Validate the field that just lost focus
                self.validateProfileField($field);
            });
            
            // PASSWORD STRENGTH CHECK: Update strength meter as user types (profile password)
            $(document).on('input', '#profile_password', function() {
                // Only check if password change toggle is checked
                if ($('#change_password_toggle').is(':checked')) {
                    // Pass the field context so it finds the correct strength meter
                    self.checkProfilePasswordStrength($(this).val(), $(this));
                }
            });
            
            // CONFIRM PASSWORD VALIDATION: Check if passwords match in real-time
            $(document).on('input', '#profile_confirm_password', function() {
                // Only validate if password change toggle is checked
                if ($('#change_password_toggle').is(':checked')) {
                    var password = $('#profile_password').val();
                    var confirmPassword = $(this).val();
                    
                    // Only validate if both fields have values
                    if (confirmPassword && password !== confirmPassword) {
                        self.showFieldError($(this), 'Passwords do not match.');
                    } else {
                        self.clearFieldError($(this));
                    }
                }
            });
            
            // PHONE NUMBER VALIDATION: Filter out invalid characters as user types
            // Rules: Only digits and + (for country code at start), no letters, no spaces
            $(document).on('input', '#profile_phone_number', function() {
                var phone = $(this).val();
                
                // Remove all non-digit characters except + at the start
                // First, remove everything except digits and +
                var cleaned = phone.replace(/[^0-9+]/g, '');
                
                // Ensure + only appears at the start (if it exists)
                if (cleaned.indexOf('+') !== -1) {
                    // If + exists but not at start, remove all + and add one at start
                    if (cleaned.indexOf('+') !== 0) {
                        cleaned = cleaned.replace(/\+/g, '');
                        cleaned = '+' + cleaned;
                    } else {
                        // + is at start, but might have more + characters - remove them
                        var hasPlus = cleaned[0] === '+';
                        cleaned = cleaned.replace(/\+/g, '');
                        if (hasPlus) {
                            cleaned = '+' + cleaned;
                        }
                    }
                }
                
                $(this).val(cleaned);
            });
            
            // INTERESTS VALIDATION: Check if at least one interest is selected
            $(document).on('change', '#doregister-profile-edit-form input[name="interests[]"]', function() {
                self.validateProfileInterests();
            });
        },
        
        /**
         * Validate Profile Edit Form Field
         * 
         * Validates a single field in the profile edit form.
         * Similar to validateField but specifically for profile form fields.
         * 
         * @method validateProfileField
         * @param {jQuery} $field - jQuery object of the field to validate
         * @returns {boolean} True if field is valid, false if validation fails
         */
        validateProfileField: function($field) {
            var self = this;
            
            // Extract field properties for validation
            var value = $field.val();
            var name = $field.attr('name') || $field.attr('id');
            var type = $field.attr('type');
            var required = $field.prop('required');
            
            // Clear previous error
            this.clearFieldError($field);
            
            // REQUIRED FIELD CHECK
            if (required && !value.trim()) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }
            
            // TYPE-SPECIFIC VALIDATION: Only validate if field has a value
            if (value) {
                // EMAIL VALIDATION
                if (type === 'email' && !this.isValidEmail(value)) {
                    this.showFieldError($field, 'Please enter a valid email address.');
                    return false;
                }
                
                // PHONE NUMBER VALIDATION: Check for 10-15 digits, no letters, no spaces
                // Rules:
                // - Only digits and + (for country code at start)
                // - Must have 10-15 digits total
                // - No letters allowed
                // - No spaces allowed
                if (name === 'phone_number') {
                    // Remove + to count only digits
                    var digitsOnly = value.replace(/[^0-9]/g, '');
                    var digitCount = digitsOnly.length;
                    
                    // Check if contains letters (shouldn't happen due to input filter, but double-check)
                    if (/[a-zA-Z]/.test(value)) {
                        this.showFieldError($field, 'Phone number cannot contain letters.');
                        return false;
                    }
                    
                    // Check if contains spaces (shouldn't happen due to input filter, but double-check)
                    if (/\s/.test(value)) {
                        this.showFieldError($field, 'Phone number cannot contain spaces.');
                        return false;
                    }
                    
                    // Check digit count: must be between 10 and 15
                    if (digitCount < 10) {
                        this.showFieldError($field, 'Phone number must have at least 10 digits.');
                        return false;
                    }
                    if (digitCount > 15) {
                        this.showFieldError($field, 'Phone number cannot have more than 15 digits.');
                        return false;
                    }
                    
                    // Check format: only digits and optional + at start
                    if (!/^\+?[0-9]+$/.test(value)) {
                        this.showFieldError($field, 'Please enter a valid phone number (digits only, + allowed at start).');
                        return false;
                    }
                }
                
                // PASSWORD LENGTH VALIDATION: Minimum 8 characters (only if password change is enabled)
                if (name === 'password' && $('#change_password_toggle').is(':checked')) {
                    if (value.length < 8) {
                        this.showFieldError($field, 'Password must be at least 8 characters.');
                        return false;
                    }
                }
            }
            
            // All validations passed
            return true;
        },
        
        /**
         * Validate Profile Interests
         * 
         * Ensures at least one interest is selected in profile edit form.
         * 
         * @method validateProfileInterests
         * @returns {boolean} True if at least one interest is selected, false otherwise
         */
        validateProfileInterests: function() {
            // Count checked interest checkboxes in profile form
            var checked = $('#doregister-profile-edit-form input[name="interests[]"]:checked').length;
            
            // Find error message container for interests field
            var $errorContainer = $('#doregister-profile-edit-form').find('input[name="interests[]"]').first().closest('.doregister-field-group').find('.doregister-error-message');
            
            // Validate: At least one interest must be selected
            if (checked < 1) {
                // No interests selected: Show error
                if ($errorContainer.length) {
                    $errorContainer.text('Please select at least one interest.').addClass('doregister-error-visible');
                }
                return false;
            } else {
                // Interests selected: Clear error
                if ($errorContainer.length) {
                    $errorContainer.text('').removeClass('doregister-error-visible');
                }
                return true;
            }
        },
        
        /**
         * Check Password Strength for Profile Form
         * 
         * Similar to checkPasswordStrength but works with profile form context.
         * 
         * @method checkProfilePasswordStrength
         * @param {string} password - Password to check
         * @param {jQuery} $field - Password field element (for context)
         * @returns {void}
         */
        checkProfilePasswordStrength: function(password, $field) {
            // Find the strength meter within the same field group
            var $meter = $field.closest('.doregister-field-group').find('.doregister-password-strength');
            var strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            $meter.removeClass('doregister-weak doregister-medium doregister-strong');
            
            if (password.length === 0) {
                $meter.text('').removeClass('doregister-visible');
            } else if (strength <= 2) {
                $meter.text('Weak').addClass('doregister-weak doregister-visible');
            } else if (strength === 3) {
                $meter.text('Medium').addClass('doregister-medium doregister-visible');
            } else {
                $meter.text('Strong').addClass('doregister-strong doregister-visible');
            }
        },
        
        /**
         * Navigate to a specific step in the registration form
         * 
         * Handles step transitions: Updates UI, progress bar, step indicator.
         * Saves current step to localStorage and triggers custom event.
         * Implements slide animations based on navigation direction.
         * 
         * UI Updates:
         * - Hides current step (removes active class, adds hidden class)
         * - Shows target step (removes hidden class, adds active class with direction)
         * - Updates progress bar width (visual completion indicator)
         * - Updates step number text ("Step X of 5")
         * 
         * Animation:
         * - Forward navigation (step > currentStep): Slides in from right
         * - Backward navigation (step < currentStep): Slides in from left
         * - Can be skipped for initial page load (skipAnimation = true)
         * 
         * Side Effects:
         * - Saves step to localStorage (for auto-restore on refresh)
         * - Triggers 'doregister:stepChanged' event (used by review summary)
         * 
         * @method goToStep
         * @param {number} step - Step number to navigate to (1-5)
         * @param {boolean} skipAnimation - If true, skip animation (for initial load)
         * @returns {void}
         */
        goToStep: function(step, skipAnimation) {
            // Validate step number: Must be between 1 and totalSteps
            // Prevents navigation to invalid steps
            if (step < 1 || step > this.totalSteps) {
                return; // Exit early if invalid step
            }
            
            // Store previous step before updating (needed for direction detection)
            var previousStep = this.currentStep;
            
            // Get references to current and target step elements
            var $currentStep = $('.doregister-step[data-step="' + previousStep + '"]');
            var $targetStep = $('.doregister-step[data-step="' + step + '"]');
            
            // HIDE ALL STEPS FIRST: Ensure only one step is visible at a time
            // This fixes the issue where multiple steps show after page refresh
            // Find all steps that are currently visible (have doregister-step-active class)
            $('.doregister-step.doregister-step-active').each(function() {
                $(this).removeClass('doregister-step-active slide-in-left slide-in-right').addClass('doregister-step-hidden');
            });
            
            // Also hide the step based on previousStep (for cases where it's not visible but should be cleaned up)
            if ($currentStep.length && previousStep !== step) {
                $currentStep.removeClass('doregister-step-active slide-in-left slide-in-right').addClass('doregister-step-hidden');
            }
            
            // SHOW NEW STEP: Update current step and show it
            // Update internal state first
            this.currentStep = step;
            
            // Remove any existing animation classes and hidden class
            $targetStep.removeClass('doregister-step-hidden slide-in-left slide-in-right');
            
            // Add active class and direction-specific animation class (if not skipping animation)
            if (!skipAnimation) {
                // Determine navigation direction for animation
                // Forward: step > previous step (e.g., Step 1 -> Step 2)
                // Backward: step < previous step (e.g., Step 3 -> Step 2)
                var isForward = step > previousStep;
                var animationClass = isForward ? 'slide-in-right' : 'slide-in-left';
                
                // Add active class and direction-specific animation class
                // This triggers the slide animation
                $targetStep.addClass('doregister-step-active ' + animationClass);
            } else {
                // Skip animation: Just add active class without animation
                // Used for initial page load or restoration from localStorage
                $targetStep.addClass('doregister-step-active');
            }
            
            // UPDATE PROGRESS BAR: Calculate and set width percentage
            // Formula: (current step / total steps) * 100
            // Example: Step 2 of 5 = (2/5) * 100 = 40%
            var progress = (step / this.totalSteps) * 100;
            
            // Set CSS width property to show progress
            $('.doregister-progress-fill').css('width', progress + '%');
            
            // UPDATE STEP INDICATOR: Update "Step X of 5" text
            // $('#doregister-step-number'): Element that displays step number
            $('#doregister-step-number').text(step);
            
            // SAVE CURRENT STEP: Persist to localStorage
            // Allows form to restore to this step if page is refreshed
            this.saveToStorage();
            
            // TRIGGER CUSTOM EVENT: Notify other code that step changed
            // 'doregister:stepChanged': Custom event name (namespaced with 'doregister:')
            // [step]: Pass step number as event data
            // Used by review summary to update when reaching Step 5
            $(document).trigger('doregister:stepChanged', [step]);
        },
        
        /**
         * Validate all fields in a specific step
         * 
         * Performs comprehensive validation for a step before allowing navigation.
         * Validates required fields and step-specific rules.
         * 
         * Validation Process:
         * 1. Validate all required fields (input[required], select[required])
         * 2. Apply step-specific validation rules:
         *    - Step 1: Password match, password length
         *    - Step 3: At least one interest selected
         *    - Step 4: Profile photo uploaded
         * 
         * Returns false if any validation fails, preventing step navigation.
         * 
         * @method validateStep
         * @param {number} step - Step number to validate (1-5)
         * @returns {boolean} True if step is valid, false if validation fails
         */
        validateStep: function(step) {
            var self = this;
            var isValid = true; // Assume valid until proven otherwise
            
            // Get jQuery object for the step container
            var $step = $('.doregister-step[data-step="' + step + '"]');
            
            // VALIDATE REQUIRED FIELDS: Check all fields marked as required
            // .find('input[required], select[required]'): Selects all required inputs and selects
            // .each(): Iterates through each field
            $step.find('input[required], select[required]').each(function() {
                // Validate individual field
                // If validation fails, set isValid to false
                if (!self.validateField($(this))) {
                    isValid = false; // Mark step as invalid
                }
            });
            
            // STEP-SPECIFIC VALIDATIONS: Additional rules beyond required fields
            if (step === 1) {
                // STEP 1: Password validation
                
                // PASSWORD MATCH: Check if password and confirm password match
                // Only validate if both fields have values (prevents premature errors)
                var password = $('#password').val();
                var confirmPassword = $('#confirm_password').val();
                
                if (password && confirmPassword && password !== confirmPassword) {
                    // Passwords don't match: Show error on confirm password field
                    self.showFieldError($('#confirm_password'), 'Passwords do not match.');
                    isValid = false;
                }
                
                // PASSWORD LENGTH: Minimum 8 characters
                if (password && password.length < 8) {
                    self.showFieldError($('#password'), 'Password must be at least 8 characters.');
                    isValid = false;
                }
            } else if (step === 3) {
                // STEP 3: Interests validation
                // Must select at least one interest checkbox
                if (!self.validateInterests()) {
                    isValid = false;
                }
            } else if (step === 4) {
                // STEP 4: Profile photo validation
                var $photoField = $('#profile_photo');
                
                // Check if file is currently selected in input
                // $photoField[0]: Get native DOM element from jQuery object
                // .files: FileList object (array-like)
                // .length > 0: At least one file selected
                var hasFile = $photoField[0] && $photoField[0].files && $photoField[0].files.length > 0;
                
                // Also check if photo was already uploaded (stored in formData)
                // This handles case where photo was uploaded but page refreshed
                if (!hasFile && !self.formData.profile_photo) {
                    // No file selected and no previously uploaded photo: Show error
                    self.showFieldError($photoField, 'Profile photo is required.');
                    isValid = false;
                }
            }
            
            // Return validation result
            return isValid;
        },
        
        /**
         * Validate a single form field
         * 
         * Performs validation on an individual field based on its type and requirements.
         * Clears previous errors before validating (allows real-time error updates).
         * 
         * Validation Rules:
         * - Required fields: Must not be empty (after trimming whitespace)
         * - Email fields: Must match email regex pattern
         * - Phone fields: Must contain only valid phone characters
         * - Password fields: Must be at least 8 characters
         * 
         * @method validateField
         * @param {jQuery} $field - jQuery object of the field to validate
         * @returns {boolean} True if field is valid, false if validation fails
         */
        validateField: function($field) {
            var self = this;
            
            // Extract field properties for validation
            var value = $field.val(); // Get field value
            var name = $field.attr('name') || $field.attr('id'); // Get field name or ID (for identification)
            var type = $field.attr('type'); // Get input type (email, password, text, etc.)
            var required = $field.prop('required'); // Check if field is required (boolean)
            
            // CLEAR PREVIOUS ERROR: Remove any existing error state
            // Allows field to be re-validated without stale errors
            this.clearFieldError($field);
            
            // REQUIRED FIELD CHECK: Validate if field is required
            if (required && !value.trim()) {
                // Field is required but empty (after trimming whitespace)
                // .trim(): Removes leading/trailing whitespace
                this.showFieldError($field, 'This field is required.');
                return false; // Validation failed
            }
            
            // TYPE-SPECIFIC VALIDATION: Only validate if field has a value
            // Prevents showing errors for empty optional fields
            if (value) {
                // EMAIL VALIDATION: Check email format
                if (type === 'email' && !this.isValidEmail(value)) {
                    this.showFieldError($field, 'Please enter a valid email address.');
                    return false;
                }
                
                // PHONE NUMBER VALIDATION: Check for 10-15 digits, no letters, no spaces
                // Rules:
                // - Only digits and + (for country code at start)
                // - Must have 10-15 digits total
                // - No letters allowed
                // - No spaces allowed
                if (name === 'phone_number') {
                    // Remove + to count only digits
                    var digitsOnly = value.replace(/[^0-9]/g, '');
                    var digitCount = digitsOnly.length;
                    
                    // Check if contains letters (shouldn't happen due to input filter, but double-check)
                    if (/[a-zA-Z]/.test(value)) {
                        this.showFieldError($field, 'Phone number cannot contain letters.');
                        return false;
                    }
                    
                    // Check if contains spaces (shouldn't happen due to input filter, but double-check)
                    if (/\s/.test(value)) {
                        this.showFieldError($field, 'Phone number cannot contain spaces.');
                        return false;
                    }
                    
                    // Check digit count: must be between 10 and 15
                    if (digitCount < 10) {
                        this.showFieldError($field, 'Phone number must have at least 10 digits.');
                        return false;
                    }
                    if (digitCount > 15) {
                        this.showFieldError($field, 'Phone number cannot have more than 15 digits.');
                        return false;
                    }
                    
                    // Check format: only digits and optional + at start
                    if (!/^\+?[0-9]+$/.test(value)) {
                        this.showFieldError($field, 'Please enter a valid phone number (digits only, + allowed at start).');
                        return false;
                    }
                }
                
                // PASSWORD LENGTH VALIDATION: Minimum 8 characters
                if (name === 'password' && value.length < 8) {
                    this.showFieldError($field, 'Password must be at least 8 characters.');
                    return false;
                }
            }
            
            // All validations passed
            return true;
        },
        
        /**
         * Validate a login form field
         * 
         * Simpler validation than registration fields (only checks required).
         * Login fields don't need complex validation (email format, etc.).
         * 
         * @method validateLoginField
         * @param {jQuery} $field - jQuery object of the field to validate
         * @returns {boolean} True if field is valid, false if validation fails
         */
        validateLoginField: function($field) {
            var value = $field.val();
            var required = $field.prop('required');
            
            // Clear any existing error
            this.clearFieldError($field);
            
            // Check if required field is empty
            if (required && !value.trim()) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate interests checkboxes
         * 
         * Ensures at least one interest is selected (required field).
         * Finds error container dynamically (works even if DOM structure changes).
         * 
         * @method validateInterests
         * @returns {boolean} True if at least one interest is selected, false otherwise
         */
        validateInterests: function() {
            // Count checked interest checkboxes
            // .doregister-checkbox:checked: Selects all checked checkboxes with this class
            // .length: Returns count of checked boxes
            var checked = $('.doregister-checkbox:checked').length;
            
            // Find error message container for interests field
            // Complex selector: Step 3 -> interests input -> field group -> error message
            // .closest('.doregister-field-group'): Traverses up to find parent field group
            var $errorContainer = $('.doregister-step[data-step="3"]').find('input[name="interests[]"]').first().closest('.doregister-field-group').find('.doregister-error-message');
            
            // Validate: At least one interest must be selected
            if (checked < 1) {
                // No interests selected: Show error
                if ($errorContainer.length) {
                    $errorContainer.text('Please select at least one interest.').addClass('doregister-error-visible');
                }
                return false;
            } else {
                // Interests selected: Clear error
                if ($errorContainer.length) {
                    $errorContainer.text('').removeClass('doregister-error-visible');
                }
                return true;
            }
        },
        
        /**
         * Display error message for a field
         * 
         * Shows validation error by:
         * 1. Finding error message container (sibling element)
         * 2. Setting error message text
         * 3. Adding error CSS classes for styling
         * 
         * @method showFieldError
         * @param {jQuery} $field - jQuery object of the field with error
         * @param {string} message - Error message to display
         * @returns {void}
         */
        showFieldError: function($field, message) {
            // Find error message container within same field group
            // .closest('.doregister-field-group'): Find parent field group
            // .find('.doregister-error-message'): Find error message element within group
            var $errorContainer = $field.closest('.doregister-field-group').find('.doregister-error-message');
            
            // Set error message text and make it visible
            // .text(message): Sets text content (automatically escapes HTML)
            // .addClass('doregister-error-visible'): Adds CSS class for styling
            $errorContainer.text(message).addClass('doregister-error-visible');
            
            // Add error class to field itself (for styling, e.g., red border)
            $field.addClass('doregister-input-error');
        },
        
        /**
         * Clear error message for a field
         * 
         * Removes error state by clearing message and removing error classes.
         * Called when field becomes valid or is re-validated.
         * 
         * @method clearFieldError
         * @param {jQuery} $field - jQuery object of the field to clear
         * @returns {void}
         */
        clearFieldError: function($field) {
            // Find error message container
            var $errorContainer = $field.closest('.doregister-field-group').find('.doregister-error-message');
            
            // Clear error message and hide it
            // .text(''): Clear text content
            // .removeClass('doregister-error-visible'): Remove visibility class
            $errorContainer.text('').removeClass('doregister-error-visible');
            
            // Remove error class from field
            $field.removeClass('doregister-input-error');
        },
        
        /**
         * Check email uniqueness
         */
        checkEmailUniqueness: function(email) {
            var self = this;
            var $field = $('#email');
            
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'doregister_check_email',
                    email: email,
                    nonce: doregisterData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.exists) {
                        self.showFieldError($field, 'This email is already registered.');
                    }
                }
            });
        },
        
        /**
         * Check password strength
         */
        checkPasswordStrength: function(password) {
            var $meter = $('.doregister-password-strength');
            var strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            $meter.removeClass('doregister-weak doregister-medium doregister-strong');
            
            if (password.length === 0) {
                $meter.text('').removeClass('doregister-visible');
            } else if (strength <= 2) {
                $meter.text('Weak').addClass('doregister-weak doregister-visible');
            } else if (strength === 3) {
                $meter.text('Medium').addClass('doregister-medium doregister-visible');
            } else {
                $meter.text('Strong').addClass('doregister-strong doregister-visible');
            }
        },
        
        /**
         * Handle profile photo file upload
         * 
         * Processes file selection: validates, shows preview, uploads via AJAX.
         * Uses FileReader API for client-side preview before upload.
         * 
         * Process:
         * 1. Validate file exists
         * 2. Validate file type (must be image)
         * 3. Validate file size (max 5MB)
         * 4. Show preview using FileReader (data URL)
         * 5. Upload file via AJAX (FormData)
         * 6. Store uploaded URL in formData
         * 
         * @method handlePhotoUpload
         * @param {File} file - File object from file input
         * @returns {void}
         */
        handlePhotoUpload: function(file) {
            var self = this;
            var $field = $('#profile_photo'); // File input field
            var $preview = $('.doregister-image-preview'); // Preview container
            
            // Validate file exists
            if (!file) {
                return; // Exit early if no file
            }
            
            // VALIDATE FILE TYPE: Must be an image
            // file.type: MIME type (e.g., "image/jpeg", "image/png")
            // .match('image.*'): Checks if type starts with "image/"
            if (!file.type.match('image.*')) {
                this.showFieldError($field, 'Please select an image file.');
                return; // Exit if not an image
            }
            
            // VALIDATE FILE SIZE: Maximum 5MB
            // file.size: Size in bytes
            // 5 * 1024 * 1024: 5MB in bytes (5 * 1024 KB * 1024 bytes)
            if (file.size > 5 * 1024 * 1024) {
                this.showFieldError($field, 'File size must be less than 5MB.');
                return; // Exit if file too large
            }
            
            // SHOW PREVIEW: Use FileReader API to display image before upload
            // FileReader: Browser API for reading file contents
            var reader = new FileReader();
            
            // onload: Fired when file reading completes successfully
            reader.onload = function(e) {
                // e.target.result: Data URL (base64-encoded image)
                // Can be used directly as img src
                if (e.target.result) {
                    $preview.html('<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; height: auto; margin-top: 10px;">');
                }
            };
            
            // onerror: Fired if file reading fails
            reader.onerror = function() {
                // Show fallback message if preview fails
                $preview.html('<p style="color: #999; margin-top: 10px;">Preview unavailable</p>');
            };
            
            // Read file as data URL (triggers onload/onerror)
            try {
                // readAsDataURL(): Converts file to base64 data URL
                // Result can be used directly in <img src>
                reader.readAsDataURL(file);
            } catch (e) {
                // If reading fails, log warning but continue with upload
                // Preview is optional, upload can still proceed
                console.warn('Could not create preview:', e);
            }
            
            // UPLOAD FILE VIA AJAX: Send file to server
            // FormData: API for creating multipart/form-data (required for file uploads)
            var formData = new FormData();
            
            // Append data to FormData
            formData.append('action', 'doregister_upload_photo'); // WordPress AJAX action
            formData.append('profile_photo', file); // File object
            formData.append('nonce', doregisterData.nonce); // Security token
            
            // Make AJAX request
            $.ajax({
                url: doregisterData.ajaxUrl, // WordPress AJAX endpoint
                type: 'POST',
                data: formData, // FormData object (contains file)
                
                // IMPORTANT: Required for file uploads
                processData: false, // Don't process data (jQuery would convert to string)
                contentType: false, // Don't set Content-Type (browser sets it with boundary)
                
                // Success callback
                success: function(response) {
                    if (response.success) {
                        // Upload successful: Store URL in formData
                        // response.data.url: URL to uploaded image (from server)
                        self.formData.profile_photo = response.data.url;
                        
                        // Save to localStorage (persist across page refreshes)
                        self.saveToStorage();
                        
                        // Clear any validation errors
                        self.clearFieldError($field);
                    } else {
                        // Upload failed: Show error message
                        // response.data.message: Error message from server
                        self.showFieldError($field, response.data.message || 'Upload failed.');
                    }
                },
                
                // Error callback: Network/server error
                error: function() {
                    self.showFieldError($field, 'Upload failed. Please try again.');
                }
            });
        },
        
        /**
         * Save all field values from a specific step to formData object
         * 
         * Collects all form field values from a step and stores them in formData.
         * Handles different field types appropriately (text, radio, checkbox, file).
         * 
         * Field Type Handling:
         * - Text/Email/Password/Select: Store value directly
         * - Radio: Store selected value
         * - Checkbox: Store as array of checked values
         * - File: Skip (handled separately via handlePhotoUpload)
         * 
         * Special Case:
         * - Interests (Step 3): Handled separately due to array notation in name
         * 
         * @method saveStepData
         * @param {number} step - Step number to save data from (1-5)
         * @returns {void}
         */
        saveStepData: function(step) {
            var self = this;
            
            // Get jQuery object for step container
            var $step = $('.doregister-step[data-step="' + step + '"]');
            
            // SPECIAL HANDLING: Interests checkboxes (Step 3)
            // Interests use array notation (name="interests[]"), need special handling
            if (step === 3) {
                var interests = [];
                
                // Find all checked interest checkboxes and collect their values
                $step.find('input[name="interests[]"]:checked').each(function() {
                    interests.push($(this).val()); // Add checked value to array
                });
                
                // Store interests array in formData
                // Key 'interests[]' matches the form field name
                self.formData['interests[]'] = interests;
            }
            
            // ITERATE ALL FIELDS: Process each input and select in the step
            $step.find('input, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name') || $field.attr('id'); // Get field identifier
                var type = $field.attr('type'); // Get input type
                
                // Skip interests checkboxes (already handled above)
                if (type === 'checkbox' && name === 'interests[]') {
                    return; // Skip to next field
                }
                
                // HANDLE CHECKBOXES: Store as array of checked values
                if (type === 'checkbox') {
                    if ($field.is(':checked')) {
                        // Checkbox is checked: Add to array
                        // Initialize array if it doesn't exist
                        if (!self.formData[name]) {
                            self.formData[name] = [];
                        }
                        
                        // Add value to array if not already present (prevent duplicates)
                        // .indexOf(): Returns -1 if value not found
                        if (self.formData[name].indexOf($field.val()) === -1) {
                            self.formData[name].push($field.val());
                        }
                    }
                } else if (type === 'radio') {
                    // HANDLE RADIO BUTTONS: Store selected value
                    // Only store if this radio button is checked
                    if ($field.is(':checked')) {
                        self.formData[name] = $field.val();
                    }
                } else if (type === 'file') {
                    // HANDLE FILE INPUTS: Skip (handled separately)
                    // File values cannot be stored directly (security restriction)
                    // File is uploaded via handlePhotoUpload() and URL is stored
                } else {
                    // HANDLE TEXT/EMAIL/PASSWORD/SELECT: Store value directly
                    // Includes: text, email, password, tel, date, select
                    self.formData[name] = $field.val();
                }
            });
            
            // Persist formData to localStorage
            this.saveToStorage();
        },
        
        /**
         * Restore form data from formData object to form fields
         * 
         * Populates form fields with previously saved values (from localStorage).
         * Called on page load to restore user's progress if they refreshed the page.
         * 
         * Restoration Process:
         * 1. Loop through all saved formData entries
         * 2. Skip special fields (profile_photo, currentStep)
         * 3. Restore arrays (checkboxes) by checking matching values
         * 4. Restore single values (text, radio, select) directly
         * 5. Skip file inputs (cannot be programmatically set for security)
         * 6. Restore profile photo preview (image URL, not file input)
         * 7. Restore current step (navigate to saved step)
         * 
         * Security Note:
         * - File inputs cannot be restored (browser security restriction)
         * - Only the preview image URL is restored
         * 
         * @method restoreFormData
         * @returns {void}
         */
        restoreFormData: function() {
            // Check if formData has any saved values
            // Object.keys(): Returns array of property names
            // .length > 0: At least one property exists
            if (Object.keys(this.formData).length > 0) {
                var self = this;
                
                // ITERATE FORM DATA: Restore each saved field value
                // $.each(): jQuery utility to iterate object properties
                $.each(this.formData, function(name, value) {
                    // SKIP SPECIAL FIELDS: Don't restore these directly
                    // profile_photo: Handled separately (preview only)
                    // currentStep: Handled separately (navigation)
                    if (name === 'profile_photo' || name === 'currentStep') {
                        return; // Skip to next property
                    }
                    
                    // HANDLE ARRAYS: Checkbox values (e.g., interests)
                    if (Array.isArray(value)) {
                        // Loop through array values
                        value.forEach(function(val) {
                            // Find checkbox with matching name and value, check it
                            // Selector: input[name="interests[]"][value="technology"]
                            $('input[name="' + name + '"][value="' + val + '"]').prop('checked', true);
                        });
                    } else {
                        // HANDLE SINGLE VALUES: Text, radio, select fields
                        // Find field by name or ID
                        var $field = $('[name="' + name + '"], #' + name);
                        
                        if ($field.length) {
                            // Field exists: Check if it's a file input
                            var fieldType = $field.attr('type');
                            
                            // Skip file inputs (cannot be restored for security)
                            if (fieldType === 'file') {
                                return; // Skip to next property
                            }
                            
                            // Restore field value
                            $field.val(value);
                        }
                    }
                });
                
                // RESTORE PROFILE PHOTO PREVIEW: Show image if URL exists
                // Cannot restore file input itself (browser security), but can show preview
                if (this.formData.profile_photo) {
                    // Display preview image using saved URL
                    // escapeHtml(): Prevents XSS attacks (escapes HTML special characters)
                    $('.doregister-image-preview').html('<img src="' + this.escapeHtml(this.formData.profile_photo) + '" alt="Preview" style="max-width: 200px; height: auto; margin-top: 10px;">');
                }
                
                // RESTORE CURRENT STEP: Navigate to saved step without animation
                // Allows user to continue from where they left off
                // No animation needed on page load (instant restoration)
                if (this.formData.currentStep) {
                    this.goToStep(this.formData.currentStep, true); // true = skip animation
                }
            }
        },
        
        /**
         * Update review summary on Step 5 (Review & Confirm)
         * 
         * Generates HTML summary of all collected form data for user review.
         * Displays all entered information in a readable format before final submission.
         * 
         * Data Displayed:
         * - Basic Information: Name, Email
         * - Contact Details: Phone, Country, City (if provided)
         * - Personal Details: Gender, Date of Birth, Interests (if provided)
         * - Profile Media: Profile Photo (if uploaded)
         * 
         * Security:
         * - All user input is escaped using escapeHtml() to prevent XSS attacks
         * - Image URLs are escaped (though they should be trusted from server)
         * 
         * @method updateReviewSummary
         * @returns {void}
         */
        updateReviewSummary: function() {
            var self = this;
            var $summary = $('#doregister-review-summary'); // Summary container element
            
            // Build HTML summary string
            // Start with basic information (always displayed)
            var html = '<div class="doregister-review-item"><strong>Full Name:</strong> ' + self.escapeHtml(this.formData.full_name || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Email:</strong> ' + self.escapeHtml(this.formData.email || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Phone:</strong> ' + self.escapeHtml(this.formData.phone_number || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Country:</strong> ' + self.escapeHtml(this.formData.country || '') + '</div>';
            
            // CONDITIONAL FIELDS: Only display if value exists
            // City (optional)
            if (this.formData.city) {
                html += '<div class="doregister-review-item"><strong>City:</strong> ' + self.escapeHtml(this.formData.city) + '</div>';
            }
            
            // Gender (optional)
            if (this.formData.gender) {
                html += '<div class="doregister-review-item"><strong>Gender:</strong> ' + self.escapeHtml(this.formData.gender) + '</div>';
            }
            
            // Date of Birth (optional)
            if (this.formData.date_of_birth) {
                html += '<div class="doregister-review-item"><strong>Date of Birth:</strong> ' + self.escapeHtml(this.formData.date_of_birth) + '</div>';
            }
            
            // Interests (required, but check if exists)
            // Handle both array notation ('interests[]') and regular ('interests')
            var interests = this.formData['interests[]'] || this.formData.interests || [];
            
            if (interests.length > 0) {
                // Convert array to comma-separated string if needed
                // Array.isArray(): Check if value is array
                // .join(', '): Convert array to string with comma separator
                html += '<div class="doregister-review-item"><strong>Interests:</strong> ' + self.escapeHtml(Array.isArray(interests) ? interests.join(', ') : interests) + '</div>';
            }
            
            // Profile Photo (if uploaded)
            if (this.formData.profile_photo) {
                // Display thumbnail image
                // escapeHtml(): Escapes URL (though URLs are typically safe)
                html += '<div class="doregister-review-item"><strong>Profile Photo:</strong> <img src="' + self.escapeHtml(this.formData.profile_photo) + '" alt="Photo" style="max-width: 100px;"></div>';
            }
            
            // Insert HTML into summary container
            $summary.html(html);
        },
        
        /**
         * Submit registration form via AJAX
         * 
         * Final submission of registration form. Validates all steps, collects data,
         * sends to server via AJAX, and handles response (success/error).
         * 
         * Process:
         * 1. Validate all steps (1-5) before submission
         * 2. Collect all form data from current step
         * 3. Prepare data object for AJAX request
         * 4. Disable submit button and show loading state
         * 5. Send AJAX request to server
         * 6. Handle success: Clear localStorage, show message, redirect
         * 7. Handle error: Re-enable button, show field errors, show message
         * 
         * Security:
         * - Uses nonce for CSRF protection
         * - Server-side validation is authoritative
         * 
         * @method submitRegistration
         * @returns {void}
         */
        submitRegistration: function() {
            var self = this;
            
            // VALIDATE ALL STEPS: Ensure all data is valid before submission
            // Loop through all steps (1 to totalSteps)
            var isValid = true;
            for (var i = 1; i <= this.totalSteps; i++) {
                // Validate each step
                if (!this.validateStep(i)) {
                    isValid = false; // Mark as invalid
                    
                    // If invalid step is before current step, navigate to it
                    // Ensures user sees the first invalid step
                    if (i < this.currentStep) {
                        this.goToStep(i);
                    }
                    break; // Stop validation on first error
                }
            }
            
            // If validation failed, exit (don't submit)
            if (!isValid) {
                return;
            }
            
            // COLLECT FORM DATA: Save current step data to formData
            // Ensures all fields from current step are included
            this.saveStepData(this.currentStep);
            
            // PREPARE AJAX DATA: Build data object for server
            // Includes all form fields and WordPress AJAX parameters
            var formData = {
                action: 'doregister_register', // WordPress AJAX action name
                nonce: doregisterData.nonce, // Security token
                full_name: this.formData.full_name,
                email: this.formData.email,
                password: this.formData.password,
                confirm_password: this.formData.confirm_password,
                phone_number: this.formData.phone_number,
                country: this.formData.country,
                city: this.formData.city || '', // Optional: Default to empty string
                gender: this.formData.gender || '', // Optional: Default to empty string
                date_of_birth: this.formData.date_of_birth || '', // Optional: Default to empty string
                interests: this.formData['interests[]'] || this.formData.interests || [], // Handle both array notations
                profile_photo: this.formData.profile_photo || '' // Optional: Default to empty string
            };
            
            // SHOW LOADING STATE: Disable button and change text
            // Prevents double-submission and provides user feedback
            var $submitBtn = $('.doregister-btn-submit');
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            // SUBMIT VIA AJAX: Send data to server
            $.ajax({
                url: doregisterData.ajaxUrl, // WordPress AJAX endpoint
                type: 'POST',
                data: formData, // Form data object
                
                // SUCCESS CALLBACK: Handle successful registration
                success: function(response) {
                    if (response.success) {
                        // Registration successful
                        
                        // CLEAR LOCALSTORAGE: Remove saved form data
                        // Registration complete, no need to persist data
                        localStorage.removeItem('doregister_form_data');
                        
                        // SHOW SUCCESS MESSAGE: Display confirmation
                        self.showMessage('success', response.data.message);
                        
                        // REDIRECT: Navigate to success page (usually login or profile)
                        // setTimeout: Delay redirect to allow user to see success message
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1500); // 1.5 second delay
                    } else {
                        // Registration failed: Re-enable button
                        $submitBtn.prop('disabled', false).text('Submit Registration');
                        
                        // DISPLAY FIELD ERRORS: Show server-side validation errors
                        if (response.data.errors) {
                            // Loop through error object (field name -> error message)
                            $.each(response.data.errors, function(field, message) {
                                // Find field by name or ID
                                var $field = $('[name="' + field + '"], #' + field);
                                if ($field.length) {
                                    // Display error on field
                                    self.showFieldError($field, message);
                                }
                            });
                        }
                        
                        // SHOW ERROR MESSAGE: Display general error message
                        self.showMessage('error', response.data.message || 'Registration failed. Please check the errors above.');
                    }
                },
                
                // ERROR CALLBACK: Handle network/server errors
                error: function(xhr, status, error) {
                    // Re-enable button
                    $submitBtn.prop('disabled', false).text('Submit Registration');
                    
                    // Log error details to console for debugging
                    console.error('Registration Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    // Show user-friendly error message
                    self.showMessage('error', 'An error occurred. Please try again. Check console for details.');
                }
            });
        },
        
        /**
         * Submit login form via AJAX
         * 
         * Handles login form submission. Validates fields, sends credentials to server,
         * and handles authentication response.
         * 
         * Process:
         * 1. Validate required fields
         * 2. Prepare login data (email/username, password)
         * 3. Disable submit button and show loading
         * 4. Send AJAX request
         * 5. Handle success: Show message, redirect to profile
         * 6. Handle error: Re-enable button, show errors
         * 
         * @method submitLogin
         * @returns {void}
         */
        submitLogin: function() {
            var self = this;
            
            // VALIDATE FORM: Check all required fields
            var isValid = true;
            $('#doregister-login-form input[required]').each(function() {
                if (!self.validateLoginField($(this))) {
                    isValid = false;
                }
            });
            
            // Exit if validation failed
            if (!isValid) {
                return;
            }
            
            // PREPARE LOGIN DATA: Build data object for AJAX
            var formData = {
                action: 'doregister_login', // WordPress AJAX action
                nonce: doregisterData.loginNonce, // Security token (different from registration)
                login_email: $('#login_email').val(), // Email or username
                login_password: $('#login_password').val(), // Password
                remember_me: $('#remember_me').is(':checked') ? 'true' : 'false' // Remember Me checkbox
            };
            
            // SHOW LOADING STATE
            var $submitBtn = $('#doregister-login-form .doregister-btn-submit');
            $submitBtn.prop('disabled', true).text('Logging in...');
            
            // SUBMIT VIA AJAX
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Login successful: Show message and redirect
                        self.showMessage('success', response.data.message);
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url; // Usually profile page
                        }, 1000); // 1 second delay
                    } else {
                        // Login failed: Re-enable button
                        $submitBtn.prop('disabled', false).text('Login');
                        
                        // Display field errors if provided
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, message) {
                                var $field = $('#' + field);
                                if ($field.length) {
                                    self.showFieldError($field, message);
                                }
                            });
                        }
                        
                        // Show error message
                        self.showMessage('error', response.data.message || 'Login failed.');
                    }
                },
                error: function() {
                    // Network/server error
                    $submitBtn.prop('disabled', false).text('Login');
                    self.showMessage('error', 'An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Handle logout request
         * 
         * Sends AJAX request to destroy session and redirects to login page.
         * Simple implementation: No validation needed.
         * 
         * @method handleLogout
         * @returns {void}
         */
        handleLogout: function() {
            var self = this;
            
            // Send logout AJAX request
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'doregister_logout', // WordPress AJAX action
                    nonce: doregisterData.loginNonce // Security token
                },
                success: function(response) {
                    if (response.success) {
                        // Logout successful: Redirect to login page
                        window.location.href = response.data.redirect_url;
                    }
                }
                // No error handler: Fail silently (user will stay on page)
            });
        },
        
        /**
         * Display success or error message to user
         * 
         * Shows a temporary message (success or error) that auto-hides after 5 seconds.
         * Uses fadeOut animation for smooth UX.
         * 
         * Message Types:
         * - 'success': Green success message
         * - 'error': Red error message
         * 
         * Security:
         * - Message text is escaped to prevent XSS attacks
         * 
         * @method showMessage
         * @param {string} type - Message type ('success' or 'error')
         * @param {string} message - Message text to display
         * @returns {void}
         */
        showMessage: function(type, message) {
            // Find message container
            var $container = $('.doregister-form-messages');
            
            // Determine CSS class based on type
            var className = type === 'success' ? 'doregister-success' : 'doregister-error';
            
            // Insert message HTML (escaped for security)
            $container.html('<div class="doregister-message ' + className + '">' + this.escapeHtml(message) + '</div>');
            
            // Auto-hide message after 5 seconds
            setTimeout(function() {
                // Fade out animation
                $container.fadeOut(function() {
                    // Clear content and show container again (for next message)
                    $(this).empty().show();
                });
            }, 5000); // 5 second delay
        },
        
        /**
         * Save formData to localStorage
         * 
         * Persists form data and current step to browser's localStorage.
         * Allows form to restore user's progress if page is refreshed.
         * 
         * Storage Format:
         * - Key: 'doregister_form_data'
         * - Value: JSON string of formData object
         * 
         * @method saveToStorage
         * @returns {void}
         */
        saveToStorage: function() {
            // Update current step in formData before saving
            this.formData.currentStep = this.currentStep;
            
            // Save to localStorage
            // JSON.stringify(): Converts object to JSON string (required for storage)
            localStorage.setItem('doregister_form_data', JSON.stringify(this.formData));
        },
        
        /**
         * Load formData from localStorage
         * 
         * Retrieves saved form data from browser's localStorage.
         * Called on page load to restore user's progress.
         * 
         * Error Handling:
         * - If JSON parse fails, resets formData to empty object
         * - If no stored data exists, initializes empty formData
         * 
         * @method loadFromStorage
         * @returns {void}
         */
        loadFromStorage: function() {
            // Get stored data from localStorage
            var stored = localStorage.getItem('doregister_form_data');
            
            if (stored) {
                // Data exists: Parse JSON string
                try {
                    // JSON.parse(): Converts JSON string back to object
                    this.formData = JSON.parse(stored);
                    
                    // Restore current step if it exists
                    if (this.formData.currentStep) {
                        this.currentStep = this.formData.currentStep;
                    }
                } catch (e) {
                    // JSON parse failed: Reset to empty object
                    // This handles corrupted or invalid stored data
                    this.formData = {};
                }
            } else {
                // No stored data: Initialize empty object
                this.formData = {};
            }
        },
        
        /**
         * Validate email address format
         * 
         * Uses regex pattern to check if email format is valid.
         * Basic validation (doesn't check if email actually exists).
         * 
         * Regex Pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
         * - ^: Start of string
         * - [^\s@]+: One or more characters that are not whitespace or @
         * - @: Literal @ symbol
         * - [^\s@]+: One or more characters that are not whitespace or @
         * - \.: Literal dot (escaped)
         * - [^\s@]+: One or more characters that are not whitespace or @
         * - $: End of string
         * 
         * @method isValidEmail
         * @param {string} email - Email address to validate
         * @returns {boolean} True if email format is valid, false otherwise
         */
        isValidEmail: function(email) {
            // Email regex pattern
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Test email against pattern
            return emailRegex.test(email);
        },
        
        /**
         * Escape HTML special characters to prevent XSS attacks
         * 
         * Converts potentially dangerous HTML characters to their entity equivalents.
         * Prevents cross-site scripting (XSS) attacks when displaying user input.
         * 
         * Characters Escaped:
         * - & → &amp;
         * - < → &lt;
         * - > → &gt;
         * - " → &quot;
         * - ' → &#039;
         * 
         * @method escapeHtml
         * @param {string} text - Text to escape
         * @returns {string} Escaped text safe for HTML display
         */
        escapeHtml: function(text) {
            // Character mapping: HTML entity for each dangerous character
            var map = {
                '&': '&amp;', // Ampersand (must be first to avoid double-escaping)
                '<': '&lt;', // Less than
                '>': '&gt;', // Greater than
                '"': '&quot;', // Double quote
                "'": '&#039;' // Single quote
            };
            
            // Replace all dangerous characters with their entities
            // /[&<>"']/g: Regex pattern matching any of these characters
            //   g flag: Global (replace all occurrences, not just first)
            // Function: Returns mapped entity for each matched character
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // INITIALIZATION: Run when DOM is ready
    // $(document).ready(): Ensures DOM is fully loaded before executing code
    // This prevents errors from trying to access elements that don't exist yet
    $(document).ready(function() {
        // Initialize DoRegister plugin
        // Sets up all event handlers and restores saved form data
        DoRegister.init();
    });
    
// End of IIFE: Closes the immediately invoked function expression
// jQuery is passed as parameter and available as $ inside the function
})(jQuery);

