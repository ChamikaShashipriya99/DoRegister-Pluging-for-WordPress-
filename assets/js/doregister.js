(function($) {
    'use strict';
    
    var DoRegister = {
        currentStep: 1,
        totalSteps: 5,
        formData: {},
        countries: [],
        
        init: function() {
            this.loadFromStorage();
            this.initRegistrationForm();
            this.initLoginForm();
            this.initCountryDropdown();
            this.initNavigationLinks();
            this.initLogout();
        },
        
        /**
         * Initialize registration form
         */
        initRegistrationForm: function() {
            var self = this;
            
            // Restore form data
            this.restoreFormData();
            
            // Next button handler
            $(document).on('click', '.doregister-btn-next', function(e) {
                e.preventDefault();
                var nextStep = $(this).data('next-step');
                if (self.validateStep(self.currentStep)) {
                    self.saveStepData(self.currentStep);
                    self.goToStep(nextStep);
                } else {
                    // Scroll to first error
                    var $firstError = $('.doregister-step[data-step="' + self.currentStep + '"]').find('.doregister-input-error').first();
                    if ($firstError.length) {
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 500);
                    }
                }
            });
            
            // Back button handler
            $(document).on('click', '.doregister-btn-back', function(e) {
                e.preventDefault();
                var prevStep = $(this).data('prev-step');
                self.saveStepData(self.currentStep);
                self.goToStep(prevStep);
            });
            
            // Real-time validation
            $(document).on('blur', '#doregister-registration-form input, #doregister-registration-form select', function() {
                self.validateField($(this));
            });
            
            // Password strength check
            $(document).on('input', '#password', function() {
                self.checkPasswordStrength($(this).val());
            });
            
            // Confirm password validation
            $(document).on('input', '#confirm_password', function() {
                var password = $('#password').val();
                var confirmPassword = $(this).val();
                if (confirmPassword && password !== confirmPassword) {
                    self.showFieldError($(this), 'Passwords do not match.');
                } else {
                    self.clearFieldError($(this));
                }
            });
            
            // Email uniqueness check
            $(document).on('blur', '#email', function() {
                var email = $(this).val();
                if (email && self.isValidEmail(email)) {
                    self.checkEmailUniqueness(email);
                }
            });
            
            // Phone number validation
            $(document).on('input', '#phone_number', function() {
                var phone = $(this).val();
                $(this).val(phone.replace(/[^0-9+\-\s()]/g, ''));
            });
            
            // Interests validation
            $(document).on('change', '.doregister-checkbox', function() {
                self.validateInterests();
            });
            
            // Profile photo upload
            $(document).on('change', '#profile_photo', function() {
                self.handlePhotoUpload($(this)[0].files[0]);
            });
            
            // Form submission
            $(document).on('submit', '#doregister-registration-form', function(e) {
                e.preventDefault();
                self.submitRegistration();
            });
            
            // Update review summary when reaching step 5
            $(document).on('doregister:stepChanged', function(e, step) {
                if (step === 5) {
                    self.updateReviewSummary();
                }
            });
        },
        
        /**
         * Initialize login form
         */
        initLoginForm: function() {
            var self = this;
            
            $(document).on('submit', '#doregister-login-form', function(e) {
                e.preventDefault();
                self.submitLogin();
            });
            
            // Real-time validation
            $(document).on('blur', '#doregister-login-form input', function() {
                self.validateLoginField($(this));
            });
        },
        
        /**
         * Initialize country dropdown
         */
        initCountryDropdown: function() {
            var self = this;
            this.countries = doregisterData.countries || [];
            
            $(document).on('input', '.doregister-country-search', function() {
                var searchTerm = $(this).val().toLowerCase();
                var $dropdown = $(this).siblings('.doregister-country-dropdown');
                
                if (searchTerm.length < 1) {
                    $dropdown.hide().empty();
                    return;
                }
                
                var filtered = self.countries.filter(function(country) {
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
            
            $(document).on('click', '.doregister-country-item', function() {
                var country = $(this).data('country');
                $('.doregister-country-search').val(country);
                $('.doregister-country-dropdown').hide().empty();
                self.clearFieldError($('.doregister-country-search'));
            });
            
            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.doregister-country-wrapper').length) {
                    $('.doregister-country-dropdown').hide();
                }
            });
        },
        
        /**
         * Initialize navigation links
         */
        initNavigationLinks: function() {
            $(document).on('click', '.doregister-link-to-login', function(e) {
                e.preventDefault();
                window.location.href = window.location.origin + '/login';
            });
            
            $(document).on('click', '.doregister-link-to-register', function(e) {
                e.preventDefault();
                window.location.href = window.location.origin + '/registration';
            });
        },
        
        /**
         * Initialize logout
         */
        initLogout: function() {
            var self = this;
            
            $(document).on('click', '.doregister-btn-logout', function(e) {
                e.preventDefault();
                self.handleLogout();
            });
        },
        
        /**
         * Go to step
         */
        goToStep: function(step) {
            if (step < 1 || step > this.totalSteps) {
                return;
            }
            
            // Hide current step
            $('.doregister-step[data-step="' + this.currentStep + '"]').removeClass('doregister-step-active').addClass('doregister-step-hidden');
            
            // Show new step
            this.currentStep = step;
            $('.doregister-step[data-step="' + step + '"]').removeClass('doregister-step-hidden').addClass('doregister-step-active');
            
            // Update progress bar
            var progress = (step / this.totalSteps) * 100;
            $('.doregister-progress-fill').css('width', progress + '%');
            
            // Update step indicator
            $('#doregister-step-number').text(step);
            
            // Save current step
            this.saveToStorage();
            
            // Trigger event
            $(document).trigger('doregister:stepChanged', [step]);
        },
        
        /**
         * Validate step
         */
        validateStep: function(step) {
            var self = this;
            var isValid = true;
            var $step = $('.doregister-step[data-step="' + step + '"]');
            
            // Validate required fields
            $step.find('input[required], select[required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });
            
            // Step-specific validations
            if (step === 1) {
                // Password match - only check if both fields have values
                var password = $('#password').val();
                var confirmPassword = $('#confirm_password').val();
                if (password && confirmPassword && password !== confirmPassword) {
                    self.showFieldError($('#confirm_password'), 'Passwords do not match.');
                    isValid = false;
                }
                // Password length validation
                if (password && password.length < 8) {
                    self.showFieldError($('#password'), 'Password must be at least 8 characters.');
                    isValid = false;
                }
            } else if (step === 3) {
                // Interests
                if (!self.validateInterests()) {
                    isValid = false;
                }
            } else if (step === 4) {
                // Profile photo - check if file is selected or already uploaded
                var $photoField = $('#profile_photo');
                var hasFile = $photoField[0] && $photoField[0].files && $photoField[0].files.length > 0;
                if (!hasFile && !self.formData.profile_photo) {
                    self.showFieldError($photoField, 'Profile photo is required.');
                    isValid = false;
                }
            }
            
            return isValid;
        },
        
        /**
         * Validate field
         */
        validateField: function($field) {
            var self = this;
            var value = $field.val();
            var name = $field.attr('name') || $field.attr('id');
            var type = $field.attr('type');
            var required = $field.prop('required');
            
            // Clear previous error
            this.clearFieldError($field);
            
            // Required check
            if (required && !value.trim()) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }
            
            // Type-specific validation
            if (value) {
                if (type === 'email' && !this.isValidEmail(value)) {
                    this.showFieldError($field, 'Please enter a valid email address.');
                    return false;
                }
                
                if (name === 'phone_number' && !/^[0-9+\-\s()]+$/.test(value)) {
                    this.showFieldError($field, 'Please enter a valid phone number.');
                    return false;
                }
                
                if (name === 'password' && value.length < 8) {
                    this.showFieldError($field, 'Password must be at least 8 characters.');
                    return false;
                }
            }
            
            return true;
        },
        
        /**
         * Validate login field
         */
        validateLoginField: function($field) {
            var value = $field.val();
            var required = $field.prop('required');
            
            this.clearFieldError($field);
            
            if (required && !value.trim()) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate interests
         */
        validateInterests: function() {
            var checked = $('.doregister-checkbox:checked').length;
            var $errorContainer = $('.doregister-step[data-step="3"]').find('input[name="interests[]"]').first().closest('.doregister-field-group').find('.doregister-error-message');
            
            if (checked < 1) {
                if ($errorContainer.length) {
                    $errorContainer.text('Please select at least one interest.').addClass('doregister-error-visible');
                }
                return false;
            } else {
                if ($errorContainer.length) {
                    $errorContainer.text('').removeClass('doregister-error-visible');
                }
                return true;
            }
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $errorContainer = $field.closest('.doregister-field-group').find('.doregister-error-message');
            $errorContainer.text(message).addClass('doregister-error-visible');
            $field.addClass('doregister-input-error');
        },
        
        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            var $errorContainer = $field.closest('.doregister-field-group').find('.doregister-error-message');
            $errorContainer.text('').removeClass('doregister-error-visible');
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
         * Handle photo upload
         */
        handlePhotoUpload: function(file) {
            var self = this;
            var $field = $('#profile_photo');
            var $preview = $('.doregister-image-preview');
            
            if (!file) {
                return;
            }
            
            // Validate file type
            if (!file.type.match('image.*')) {
                this.showFieldError($field, 'Please select an image file.');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                this.showFieldError($field, 'File size must be less than 5MB.');
                return;
            }
            
            // Show preview using FileReader
            var reader = new FileReader();
            reader.onload = function(e) {
                if (e.target.result) {
                    $preview.html('<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; height: auto; margin-top: 10px;">');
                }
            };
            reader.onerror = function() {
                // If FileReader fails, just show a message
                $preview.html('<p style="color: #999; margin-top: 10px;">Preview unavailable</p>');
            };
            try {
                reader.readAsDataURL(file);
            } catch (e) {
                // If readAsDataURL fails, continue with upload anyway
                console.warn('Could not create preview:', e);
            }
            
            // Upload via AJAX
            var formData = new FormData();
            formData.append('action', 'doregister_upload_photo');
            formData.append('profile_photo', file);
            formData.append('nonce', doregisterData.nonce);
            
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.formData.profile_photo = response.data.url;
                        self.saveToStorage();
                        self.clearFieldError($field);
                    } else {
                        self.showFieldError($field, response.data.message || 'Upload failed.');
                    }
                },
                error: function() {
                    self.showFieldError($field, 'Upload failed. Please try again.');
                }
            });
        },
        
        /**
         * Save step data
         */
        saveStepData: function(step) {
            var self = this;
            var $step = $('.doregister-step[data-step="' + step + '"]');
            
            // Handle checkboxes separately for interests
            if (step === 3) {
                var interests = [];
                $step.find('input[name="interests[]"]:checked').each(function() {
                    interests.push($(this).val());
                });
                self.formData['interests[]'] = interests;
            }
            
            $step.find('input, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name') || $field.attr('id');
                var type = $field.attr('type');
                
                // Skip checkboxes as they're handled above
                if (type === 'checkbox' && name === 'interests[]') {
                    return;
                }
                
                if (type === 'checkbox') {
                    if ($field.is(':checked')) {
                        if (!self.formData[name]) {
                            self.formData[name] = [];
                        }
                        if (self.formData[name].indexOf($field.val()) === -1) {
                            self.formData[name].push($field.val());
                        }
                    }
                } else if (type === 'radio') {
                    if ($field.is(':checked')) {
                        self.formData[name] = $field.val();
                    }
                } else if (type === 'file') {
                    // File handled separately
                } else {
                    self.formData[name] = $field.val();
                }
            });
            
            this.saveToStorage();
        },
        
        /**
         * Restore form data
         */
        restoreFormData: function() {
            if (Object.keys(this.formData).length > 0) {
                var self = this;
                
                $.each(this.formData, function(name, value) {
                    // Skip file inputs and profile_photo (cannot be restored)
                    if (name === 'profile_photo' || name === 'currentStep') {
                        return;
                    }
                    
                    if (Array.isArray(value)) {
                        value.forEach(function(val) {
                            $('input[name="' + name + '"][value="' + val + '"]').prop('checked', true);
                        });
                    } else {
                        var $field = $('[name="' + name + '"], #' + name);
                        if ($field.length) {
                            // Skip file input types
                            var fieldType = $field.attr('type');
                            if (fieldType === 'file') {
                                return;
                            }
                            $field.val(value);
                        }
                    }
                });
                
                // Restore profile photo preview if exists (but not the file input itself)
                if (this.formData.profile_photo) {
                    $('.doregister-image-preview').html('<img src="' + this.escapeHtml(this.formData.profile_photo) + '" alt="Preview" style="max-width: 200px; height: auto; margin-top: 10px;">');
                }
                
                // Restore step
                if (this.formData.currentStep) {
                    this.goToStep(this.formData.currentStep);
                }
            }
        },
        
        /**
         * Update review summary
         */
        updateReviewSummary: function() {
            var self = this;
            var $summary = $('#doregister-review-summary');
            var html = '<div class="doregister-review-item"><strong>Full Name:</strong> ' + self.escapeHtml(this.formData.full_name || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Email:</strong> ' + self.escapeHtml(this.formData.email || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Phone:</strong> ' + self.escapeHtml(this.formData.phone_number || '') + '</div>';
            html += '<div class="doregister-review-item"><strong>Country:</strong> ' + self.escapeHtml(this.formData.country || '') + '</div>';
            if (this.formData.city) {
                html += '<div class="doregister-review-item"><strong>City:</strong> ' + self.escapeHtml(this.formData.city) + '</div>';
            }
            if (this.formData.gender) {
                html += '<div class="doregister-review-item"><strong>Gender:</strong> ' + self.escapeHtml(this.formData.gender) + '</div>';
            }
            if (this.formData.date_of_birth) {
                html += '<div class="doregister-review-item"><strong>Date of Birth:</strong> ' + self.escapeHtml(this.formData.date_of_birth) + '</div>';
            }
            var interests = this.formData['interests[]'] || this.formData.interests || [];
            if (interests.length > 0) {
                html += '<div class="doregister-review-item"><strong>Interests:</strong> ' + self.escapeHtml(Array.isArray(interests) ? interests.join(', ') : interests) + '</div>';
            }
            if (this.formData.profile_photo) {
                html += '<div class="doregister-review-item"><strong>Profile Photo:</strong> <img src="' + self.escapeHtml(this.formData.profile_photo) + '" alt="Photo" style="max-width: 100px;"></div>';
            }
            
            $summary.html(html);
        },
        
        /**
         * Submit registration
         */
        submitRegistration: function() {
            var self = this;
            
            // Validate all steps
            var isValid = true;
            for (var i = 1; i <= this.totalSteps; i++) {
                if (!this.validateStep(i)) {
                    isValid = false;
                    if (i < this.currentStep) {
                        this.goToStep(i);
                    }
                    break;
                }
            }
            
            if (!isValid) {
                return;
            }
            
            // Collect all form data
            this.saveStepData(this.currentStep);
            
            // Prepare data
            var formData = {
                action: 'doregister_register',
                nonce: doregisterData.nonce,
                full_name: this.formData.full_name,
                email: this.formData.email,
                password: this.formData.password,
                confirm_password: this.formData.confirm_password,
                phone_number: this.formData.phone_number,
                country: this.formData.country,
                city: this.formData.city || '',
                gender: this.formData.gender || '',
                date_of_birth: this.formData.date_of_birth || '',
                interests: this.formData['interests[]'] || this.formData.interests || [],
                profile_photo: this.formData.profile_photo || ''
            };
            
            // Show loading
            var $submitBtn = $('.doregister-btn-submit');
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            // Submit
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Clear storage
                        localStorage.removeItem('doregister_form_data');
                        
                        // Show success message
                        self.showMessage('success', response.data.message);
                        
                        // Redirect
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else {
                        $submitBtn.prop('disabled', false).text('Submit Registration');
                        
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, message) {
                                var $field = $('[name="' + field + '"], #' + field);
                                if ($field.length) {
                                    self.showFieldError($field, message);
                                }
                            });
                        }
                        
                        self.showMessage('error', response.data.message || 'Registration failed. Please check the errors above.');
                    }
                },
                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).text('Submit Registration');
                    console.error('Registration Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    self.showMessage('error', 'An error occurred. Please try again. Check console for details.');
                }
            });
        },
        
        /**
         * Submit login
         */
        submitLogin: function() {
            var self = this;
            
            // Validate form
            var isValid = true;
            $('#doregister-login-form input[required]').each(function() {
                if (!self.validateLoginField($(this))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                return;
            }
            
            var formData = {
                action: 'doregister_login',
                nonce: doregisterData.loginNonce,
                login_email: $('#login_email').val(),
                login_password: $('#login_password').val()
            };
            
            var $submitBtn = $('#doregister-login-form .doregister-btn-submit');
            $submitBtn.prop('disabled', true).text('Logging in...');
            
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('success', response.data.message);
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    } else {
                        $submitBtn.prop('disabled', false).text('Login');
                        
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, message) {
                                var $field = $('#' + field);
                                if ($field.length) {
                                    self.showFieldError($field, message);
                                }
                            });
                        }
                        
                        self.showMessage('error', response.data.message || 'Login failed.');
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text('Login');
                    self.showMessage('error', 'An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Handle logout
         */
        handleLogout: function() {
            var self = this;
            
            $.ajax({
                url: doregisterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'doregister_logout',
                    nonce: doregisterData.loginNonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    }
                }
            });
        },
        
        /**
         * Show message
         */
        showMessage: function(type, message) {
            var $container = $('.doregister-form-messages');
            var className = type === 'success' ? 'doregister-success' : 'doregister-error';
            $container.html('<div class="doregister-message ' + className + '">' + this.escapeHtml(message) + '</div>');
            
            setTimeout(function() {
                $container.fadeOut(function() {
                    $(this).empty().show();
                });
            }, 5000);
        },
        
        /**
         * Save to localStorage
         */
        saveToStorage: function() {
            this.formData.currentStep = this.currentStep;
            localStorage.setItem('doregister_form_data', JSON.stringify(this.formData));
        },
        
        /**
         * Load from localStorage
         */
        loadFromStorage: function() {
            var stored = localStorage.getItem('doregister_form_data');
            if (stored) {
                try {
                    this.formData = JSON.parse(stored);
                    if (this.formData.currentStep) {
                        this.currentStep = this.formData.currentStep;
                    }
                } catch (e) {
                    this.formData = {};
                }
            } else {
                this.formData = {};
            }
        },
        
        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        DoRegister.init();
    });
    
})(jQuery);

