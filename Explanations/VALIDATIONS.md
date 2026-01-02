# DoRegister Plugin - Validation Summary

This document lists all validations implemented in the DoRegister plugin codebase.

## Table of Contents
1. [Frontend Validations (JavaScript)](#frontend-validations-javascript)
2. [Backend Validations (PHP)](#backend-validations-php)
3. [Security Validations](#security-validations)
4. [File Upload Validations](#file-upload-validations)

---

## Frontend Validations (JavaScript)

### Real-Time Field Validations
These validations occur as users interact with form fields (on blur/input events).

#### 1. **Required Field Validation**
- **Location**: `validateField()`, `validateLoginField()`
- **Rule**: Fields marked with `required` attribute must not be empty
- **Method**: Checks if value is empty after trimming whitespace
- **Error Message**: "This field is required."
- **Applied To**: All required fields in registration and login forms

#### 2. **Email Format Validation**
- **Location**: `validateField()`, `isValidEmail()`
- **Rule**: Email must match valid email format
- **Regex Pattern**: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- **Error Message**: "Please enter a valid email address."
- **Applied To**: Email field in registration form
- **When**: On blur event (when user leaves the field)

#### 3. **Email Uniqueness Check**
- **Location**: `checkEmailUniqueness()`
- **Rule**: Email must not already exist in database
- **Method**: AJAX call to server to check email existence
- **Error Message**: "This email is already registered."
- **Applied To**: Email field in registration form
- **When**: On blur event (after email format validation passes)

#### 4. **Password Length Validation**
- **Location**: `validateField()`, `validateStep()`
- **Rule**: Password must be at least 8 characters long
- **Error Message**: "Password must be at least 8 characters."
- **Applied To**: Password field in registration form
- **When**: On blur and before step navigation

#### 5. **Password Match Validation**
- **Location**: `validateStep()`, real-time on input
- **Rule**: Confirm password must match password field
- **Error Message**: "Passwords do not match."
- **Applied To**: Confirm password field
- **When**: 
  - Real-time as user types in confirm password field
  - Before navigating from Step 1

#### 6. **Password Strength Check**
- **Location**: `checkPasswordStrength()`
- **Rule**: Evaluates password strength based on multiple criteria
- **Criteria**:
  - Length >= 8 characters
  - Contains lowercase and uppercase letters
  - Contains at least one digit
  - Contains at least one special character
- **Strength Levels**: Weak (0-2 criteria), Medium (3 criteria), Strong (4 criteria)
- **Applied To**: Password field
- **When**: As user types (on input event)

#### 7. **Phone Number Format Validation**
- **Location**: `validateField()`
- **Rule**: Phone number must contain only valid characters
- **Regex Pattern**: `/^[0-9+\-\s()]+$/`
- **Allowed Characters**: Digits (0-9), plus (+), hyphen (-), spaces, parentheses
- **Error Message**: "Please enter a valid phone number."
- **Applied To**: Phone number field
- **When**: On blur event
- **Note**: Also filters invalid characters in real-time as user types

#### 8. **Phone Number Character Filtering**
- **Location**: Real-time input event handler
- **Rule**: Automatically removes invalid characters as user types
- **Method**: Replaces any character not matching `/^[0-9+\-\s()]+$/` with empty string
- **Applied To**: Phone number field
- **When**: On every keystroke (input event)

#### 9. **Country Selection Validation**
- **Location**: `validateStep()`
- **Rule**: Country field must not be empty
- **Error Message**: "This field is required."
- **Applied To**: Country searchable dropdown
- **When**: Before navigating from Step 2

#### 10. **Interests Selection Validation**
- **Location**: `validateInterests()`, `validateStep()`
- **Rule**: At least one interest checkbox must be selected
- **Error Message**: "Please select at least one interest."
- **Applied To**: Interests checkboxes in Step 3
- **When**: 
  - When any checkbox is checked/unchecked
  - Before navigating from Step 3

#### 11. **Profile Photo Upload Validation**
- **Location**: `validateStep()`, `handlePhotoUpload()`
- **Rules**:
  - File must be selected or previously uploaded
  - File type must be an image (MIME type starts with "image/")
  - File size must not exceed 5MB
- **Error Messages**:
  - "Profile photo is required." (if no file selected)
  - "Please select an image file." (if invalid file type)
  - "File size must be less than 5MB." (if file too large)
- **Applied To**: Profile photo file input
- **When**: 
  - When file is selected (immediate validation)
  - Before navigating from Step 4

#### 12. **Step-Level Validation**
- **Location**: `validateStep()`
- **Rule**: All required fields in current step must be valid before navigation
- **Method**: 
  - Validates all required fields
  - Applies step-specific validation rules
  - Prevents navigation if any validation fails
- **Applied To**: All steps before "Next" button click
- **When**: When user clicks "Next" button

#### 13. **Form Submission Validation**
- **Location**: `submitRegistration()`
- **Rule**: All steps (1-5) must be valid before final submission
- **Method**: Loops through all steps and validates each one
- **Applied To**: Entire registration form
- **When**: When user clicks "Submit Registration" button

#### 14. **Login Form Validation**
- **Location**: `validateLoginField()`, `submitLogin()`
- **Rules**:
  - Email/Username field is required
  - Password field is required
- **Error Message**: "This field is required."
- **Applied To**: Login form fields
- **When**: 
  - On blur event (real-time)
  - Before form submission

---

## Backend Validations (PHP)

### Registration Form Validations

#### 1. **Nonce Verification**
- **Location**: `handle_registration()`
- **Rule**: Security token (nonce) must be valid
- **Method**: `wp_verify_nonce()`
- **Error Message**: "Security check failed."
- **Purpose**: Prevents CSRF (Cross-Site Request Forgery) attacks

#### 2. **Input Sanitization**
- **Location**: `handle_registration()`
- **Methods Used**:
  - `sanitize_text_field()` - For text fields (name, phone, country, city, gender)
  - `sanitize_email()` - For email field
  - `array_map('sanitize_text_field', ...)` - For interests array
- **Purpose**: Prevents XSS (Cross-Site Scripting) attacks

#### 3. **Full Name Validation**
- **Location**: `handle_registration()`
- **Rule**: Full name is required and must not be empty
- **Error Message**: "Full name is required."

#### 4. **Email Validation**
- **Location**: `handle_registration()`
- **Rules**:
  - Email is required
  - Email must be valid format (using `is_email()`)
  - Email must be unique (not already in database)
- **Error Messages**:
  - "Valid email is required." (if empty or invalid format)
  - "Email already exists." (if email already registered)
- **Method**: `is_email()` and `DoRegister_Database::email_exists()`

#### 5. **Password Validation**
- **Location**: `handle_registration()`
- **Rules**:
  - Password is required
  - Password must be at least 8 characters long
- **Error Message**: "Password must be at least 8 characters."

#### 6. **Password Confirmation Validation**
- **Location**: `handle_registration()`
- **Rule**: Confirm password must exactly match password
- **Error Message**: "Passwords do not match."

#### 7. **Phone Number Validation**
- **Location**: `handle_registration()`
- **Rules**:
  - Phone number is required
  - Phone number must match pattern: `/^[0-9+\-\s()]+$/`
- **Error Messages**:
  - "Phone number is required." (if empty)
  - "Invalid phone number format." (if invalid characters)
- **Method**: `preg_match()` with regex

#### 8. **Country Validation**
- **Location**: `handle_registration()`
- **Rule**: Country is required and must not be empty
- **Error Message**: "Country is required."

#### 9. **Interests Validation**
- **Location**: `handle_registration()`
- **Rule**: At least one interest must be selected
- **Error Message**: "Please select at least one interest."
- **Method**: Checks if interests array is not empty and has at least 1 item

#### 10. **Profile Photo Validation**
- **Location**: `handle_registration()`
- **Rule**: Profile photo URL must be provided (must be uploaded)
- **Error Message**: "Profile photo is required."

### Login Form Validations

#### 1. **Nonce Verification**
- **Location**: `handle_login()`
- **Rule**: Security token (nonce) must be valid
- **Method**: `wp_verify_nonce()`
- **Error Message**: "Security check failed."

#### 2. **Email/Username Validation**
- **Location**: `handle_login()`
- **Rule**: Email/Username field is required
- **Error Message**: "Email is required."

#### 3. **Password Validation**
- **Location**: `handle_login()`
- **Rule**: Password field is required
- **Error Message**: "Password is required."

#### 4. **User Existence Check**
- **Location**: `handle_login()`
- **Rule**: User must exist in database
- **Method**: `DoRegister_Database::get_user_by_email()`
- **Error Message**: "Invalid email or password." (generic for security)

#### 5. **Password Verification**
- **Location**: `handle_login()`
- **Rule**: Password must match stored hash
- **Method**: `DoRegister_Database::verify_password()` (uses `wp_check_password()`)
- **Error Message**: "Invalid email or password." (generic for security)

---

## Security Validations

### 1. **CSRF Protection (Nonces)**
- **Location**: All AJAX handlers
- **Method**: `wp_verify_nonce()`
- **Purpose**: Prevents Cross-Site Request Forgery attacks
- **Applied To**: 
  - Registration form submission
  - Login form submission
  - File upload
  - Email uniqueness check

### 2. **XSS Protection (Input Sanitization)**
- **Location**: All form handlers
- **Methods**: 
  - `sanitize_text_field()`
  - `sanitize_email()`
  - `esc_html()`, `esc_url()`, `esc_attr()` (output escaping)
- **Purpose**: Prevents Cross-Site Scripting attacks

### 3. **SQL Injection Protection**
- **Location**: Database operations
- **Methods**: 
  - Prepared statements (`$wpdb->prepare()`)
  - WordPress database functions (`$wpdb->insert()`, `$wpdb->get_row()`, etc.)
- **Purpose**: Prevents SQL injection attacks

### 4. **Authentication Token Verification**
- **Location**: `verify_auth_token()`
- **Method**: `hash_equals()` for timing-safe comparison
- **Purpose**: Validates "Remember Me" cookies securely
- **Applied To**: Persistent login functionality

---

## File Upload Validations

### Frontend (JavaScript)

#### 1. **File Existence Check**
- **Location**: `handlePhotoUpload()`
- **Rule**: File must be selected
- **Method**: Checks if `file` parameter exists

#### 2. **File Type Validation**
- **Location**: `handlePhotoUpload()`
- **Rule**: File must be an image
- **Method**: Checks if `file.type.match('image.*')`
- **Allowed Types**: image/jpeg, image/jpg, image/png, image/gif
- **Error Message**: "Please select an image file."

#### 3. **File Size Validation**
- **Location**: `handlePhotoUpload()`
- **Rule**: File size must not exceed 5MB
- **Method**: Checks if `file.size > 5 * 1024 * 1024`
- **Error Message**: "File size must be less than 5MB."

### Backend (PHP)

#### 1. **Nonce Verification**
- **Location**: `handle_photo_upload()`
- **Rule**: Security token must be valid
- **Method**: `wp_verify_nonce()`
- **Error Message**: "Security check failed."

#### 2. **File Upload Check**
- **Location**: `handle_photo_upload()`
- **Rule**: File must be uploaded
- **Method**: Checks if `$_FILES['profile_photo']` exists
- **Error Message**: "No file uploaded."

#### 3. **File Type Validation (MIME Type)**
- **Location**: `handle_photo_upload()`
- **Rule**: File must be an allowed image type
- **Allowed Types**: 
  - image/jpeg
  - image/jpg
  - image/png
  - image/gif
- **Method**: `wp_check_filetype()` and `in_array()` check
- **Error Message**: "Invalid file type. Only JPEG, PNG, and GIF are allowed."

#### 4. **File Size Validation**
- **Location**: `handle_photo_upload()`
- **Rule**: File size must not exceed 5MB
- **Method**: Checks if `$file['size'] > 5 * 1024 * 1024`
- **Error Message**: "File size exceeds 5MB limit."

#### 5. **WordPress Upload Handler**
- **Location**: `handle_photo_upload()`
- **Method**: `wp_handle_upload()`
- **Purpose**: Uses WordPress's secure file upload handler
- **Additional Security**: WordPress validates file content, not just extension

---

## Validation Flow Summary

### Registration Form Flow:
1. **Real-time validation** (on blur) → Individual field validation
2. **Step validation** (on Next click) → All fields in step + step-specific rules
3. **Final validation** (on Submit) → All steps validated
4. **Server-side validation** → Double-check all validations + security checks
5. **Database insertion** → User created if all validations pass

### Login Form Flow:
1. **Real-time validation** (on blur) → Required field check
2. **Form submission validation** → All required fields
3. **Server-side validation** → Nonce, sanitization, user existence, password verification

### File Upload Flow:
1. **Client-side validation** (on file select) → File type, size
2. **AJAX upload** → Server receives file
3. **Server-side validation** → Nonce, file existence, MIME type, size
4. **WordPress upload handler** → Secure file processing
5. **Media library integration** → File stored securely

---

## Validation Error Display

### Frontend:
- **Inline error messages** appear below each field
- **Real-time updates** as user corrects errors
- **Visual indicators** (red border on invalid fields)
- **Scroll to first error** when validation fails

### Backend:
- **JSON error responses** with field-specific error messages
- **Structured error format** for easy frontend handling
- **Generic error messages** for security (login failures)

---

## Notes

1. **Dual Validation**: Both frontend and backend validations are implemented for security
2. **Real-time Feedback**: Most validations provide immediate feedback to users
3. **Security First**: All user input is sanitized and validated server-side
4. **User Experience**: Clear error messages guide users to fix issues
5. **Progressive Validation**: Step-by-step validation prevents overwhelming users

