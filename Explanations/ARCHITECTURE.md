# DoRegister Plugin - Complete Architecture & Execution Flow

## Table of Contents
1. [Plugin Entry Point](#1-plugin-entry-point)
2. [Bootstrap Process](#2-bootstrap-process)
3. [Hooks & Lifecycle](#3-hooks--lifecycle)
4. [Class Responsibilities](#4-class-responsibilities)
5. [Request Flow](#5-request-flow)
6. [Data Flow](#6-data-flow)
7. [Assets & UI Flow](#7-assets--ui-flow)
8. [Error Handling & Edge Cases](#8-error-handling--edge-cases)

---

## 1. Plugin Entry Point

### Which File Loads First

**File: `DoRegister.php`** (Main plugin file)

WordPress loads this file first when the plugin is activated. The plugin header at the top tells WordPress this is a plugin:

```php
/**
 * Plugin Name: DoRegister
 * Description: Advanced multi-step user registration system...
 */
```

### How WordPress Initializes the Plugin

**WordPress Plugin Loading Sequence:**

```
1. WordPress scans wp-content/plugins/ directory
2. Finds DoRegister.php (based on plugin header)
3. Loads DoRegister.php on every page request
4. Executes code from top to bottom:
   - Defines constants (DOREGISTER_VERSION, DOREGISTER_PLUGIN_DIR, etc.)
   - Includes class-doregister-database.php (required for activation hook)
   - Registers activation/deactivation hooks
   - Includes all class files
   - Instantiates DoRegister class (singleton)
```

**Key Line:**
```php
DoRegister::get_instance(); // Line 195 - Plugin starts here
```

This creates the main plugin instance, which triggers the initialization chain.

---

## 2. Bootstrap Process

### Constants Defined

```php
DOREGISTER_VERSION        // '1.0.0' - Plugin version
DOREGISTER_PLUGIN_DIR     // Absolute path: /wp-content/plugins/DoRegister/
DOREGISTER_PLUGIN_URL     // URL: http://site.com/wp-content/plugins/DoRegister/
DOREGISTER_PLUGIN_FILE    // Full path to DoRegister.php
```

**Purpose:** Used throughout plugin for file paths, URLs, and versioning.

### Autoloaders & Includes

**No autoloader used** - All classes are explicitly included:

```php
// Line 31: Database class (required for activation hook)
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';

// Lines 69-74: All other classes
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-registration.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-login.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-ajax.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-assets.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-admin.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-profile.php';
```

**Load Order Matters:**
1. Database class first (needed for activation hook)
2. Other classes can load in any order (they're independent)

### How Dependencies Are Loaded

**Singleton Pattern:** All classes use singleton pattern to ensure only one instance exists:

```php
// Example from DoRegister_Registration:
private static $instance = null;

public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

**Initialization Chain:**

```
DoRegister::get_instance()
  └─> DoRegister::__construct()
      └─> DoRegister::init_hooks()
          ├─> add_action('init', 'start_session', 1)
          └─> add_action('plugins_loaded', 'init')
              └─> DoRegister::init()
                  ├─> DoRegister_Database::get_instance()
                  ├─> DoRegister_Registration::get_instance()
                  ├─> DoRegister_Login::get_instance()
                  ├─> DoRegister_Ajax::get_instance()
                  ├─> DoRegister_Assets::get_instance()
                  ├─> DoRegister_Admin::get_instance() (if is_admin())
                  └─> DoRegister_Profile::get_instance()
```

---

## 3. Hooks & Lifecycle

### WordPress Lifecycle Hooks Used

#### Activation/Deactivation Hooks

**Activation Hook** (`register_activation_hook`):
```php
function doregister_activate() {
    DoRegister_Database::create_table(); // Creates wp_doregister_users table
    flush_rewrite_rules(); // Refreshes permalinks
}
register_activation_hook(__FILE__, 'doregister_activate');
```

**When it runs:** Only when plugin is activated (one-time setup)

**Deactivation Hook** (`register_deactivation_hook`):
```php
function doregister_deactivate() {
    flush_rewrite_rules(); // Clean up permalinks
}
register_deactivation_hook(__FILE__, 'doregister_deactivate');
```

**When it runs:** Only when plugin is deactivated

#### Action Hooks

**1. `init` (Priority 1)**
```php
add_action('init', array($this, 'start_session'), 1);
```
- **When:** Early in WordPress initialization (before most plugins)
- **Why:** Sessions must start before any session-dependent code runs
- **What:** Starts PHP session for custom authentication

**2. `plugins_loaded`**
```php
add_action('plugins_loaded', array($this, 'init'));
```
- **When:** After all plugins are loaded
- **Why:** Ensures WordPress and other plugins are fully initialized
- **What:** Initializes all plugin components

**3. `wp_enqueue_scripts`**
```php
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
```
- **When:** Frontend page load (before HTML output)
- **Why:** WordPress standard way to load CSS/JS
- **What:** Enqueues JavaScript and CSS files

**4. `admin_enqueue_scripts`**
```php
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
```
- **When:** Admin page load
- **Why:** Load admin-specific styles
- **What:** Conditionally loads CSS only on DoRegister admin page

**5. `admin_menu`**
```php
add_action('admin_menu', array($this, 'add_admin_menu'));
```
- **When:** Admin menu generation
- **Why:** Add plugin menu to WordPress admin sidebar
- **What:** Creates "DoRegister" menu item

**6. `admin_init`**
```php
add_action('admin_init', array($this, 'check_and_create_table'));
```
- **When:** Admin page initialization
- **Why:** Handle manual table creation requests
- **What:** Processes table creation if requested

**7. `admin_notices`**
```php
add_action('admin_notices', array($this, 'show_admin_notices'));
```
- **When:** Admin notices display
- **Why:** Show success/error messages
- **What:** Displays table creation status

#### AJAX Hooks

**For Logged-in Users:**
```php
add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
add_action('wp_ajax_doregister_login', array($this, 'handle_login'));
add_action('wp_ajax_doregister_logout', array($this, 'handle_logout'));
add_action('wp_ajax_doregister_upload_photo', array($this, 'handle_photo_upload'));
add_action('wp_ajax_doregister_check_email', array($this, 'check_email_exists'));
```

**For Non-logged-in Users (Public):**
```php
add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));
add_action('wp_ajax_nopriv_doregister_login', array($this, 'handle_login'));
add_action('wp_ajax_nopriv_doregister_logout', array($this, 'handle_logout'));
add_action('wp_ajax_nopriv_doregister_upload_photo', array($this, 'handle_photo_upload'));
add_action('wp_ajax_nopriv_doregister_check_email', array($this, 'check_email_exists'));
```

**Why both?** Registration/login happens before users are authenticated, so both hooks are needed.

#### Shortcode Hooks

**Shortcodes are registered in constructors:**
```php
// DoRegister_Registration::__construct()
add_shortcode('doregister_form', array($this, 'render_registration_form'));

// DoRegister_Login::__construct()
add_shortcode('doregister_login', array($this, 'render_login_form'));

// DoRegister_Profile::__construct()
add_shortcode('doregister_profile', array($this, 'render_profile_page'));
```

**When they run:** When WordPress encounters `[doregister_form]`, `[doregister_login]`, or `[doregister_profile]` in post/page content

---

## 4. Class Responsibilities

### DoRegister (Main Class)
**File:** `DoRegister.php`

**Responsibilities:**
- Plugin initialization coordinator
- Session management (starts PHP session)
- Orchestrates all component initialization
- Singleton pattern ensures single instance

**Key Methods:**
- `get_instance()` - Returns singleton instance
- `start_session()` - Initializes PHP session
- `init()` - Initializes all plugin components

### DoRegister_Database
**File:** `includes/class-doregister-database.php`

**Responsibilities:**
- Database table creation (`wp_doregister_users`)
- User CRUD operations (Create, Read, Update, Delete)
- Password hashing/verification
- Data serialization (interests array)
- Email uniqueness checking

**Key Methods:**
- `create_table()` - Creates custom table
- `insert_user()` - Adds new user
- `get_user_by_email()` - Finds user by email
- `get_user_by_id()` - Finds user by ID
- `get_all_users()` - Paginated user list
- `delete_users()` - Bulk delete
- `verify_password()` - Password verification
- `email_exists()` - Email uniqueness check

**Database Table Structure:**
```sql
wp_doregister_users
├── id (bigint, PRIMARY KEY, AUTO_INCREMENT)
├── full_name (varchar 255)
├── email (varchar 191, UNIQUE)
├── password (varchar 255, hashed)
├── phone_number (varchar 50)
├── country (varchar 100)
├── city (varchar 100, nullable)
├── gender (varchar 20, nullable)
├── date_of_birth (date, nullable)
├── interests (text, serialized array)
├── profile_photo (varchar 255, nullable)
├── created_at (datetime, auto)
└── updated_at (datetime, auto)
```

### DoRegister_Registration
**File:** `includes/class-doregister-registration.php`

**Responsibilities:**
- Multi-step registration form HTML generation
- Shortcode handler: `[doregister_form]`
- Form structure (5 steps)

**Key Methods:**
- `render_registration_form()` - Generates HTML for 5-step form

**Form Steps:**
1. Basic Information (name, email, password)
2. Contact Details (phone, country, city)
3. Personal Details (gender, DOB, interests)
4. Profile Media (photo upload)
5. Review & Confirm (summary)

### DoRegister_Login
**File:** `includes/class-doregister-login.php`

**Responsibilities:**
- Login form HTML generation
- Shortcode handler: `[doregister_login]`
- Simple single-step form

**Key Methods:**
- `render_login_form()` - Generates login form HTML

### DoRegister_Profile
**File:** `includes/class-doregister-profile.php`

**Responsibilities:**
- User profile page HTML generation
- Shortcode handler: `[doregister_profile]`
- Session-based authentication check
- Displays user data in categorized sections

**Key Methods:**
- `render_profile_page()` - Generates profile page HTML
- Checks `$_SESSION['doregister_user_id']` for authentication

### DoRegister_Ajax
**File:** `includes/class-doregister-ajax.php`

**Responsibilities:**
- Handles all AJAX requests
- Form submission processing
- File upload handling
- Authentication (login/logout)
- Email validation

**Key Methods:**
- `handle_registration()` - Processes registration form
- `handle_login()` - Authenticates user
- `handle_logout()` - Destroys session
- `handle_photo_upload()` - Uploads profile photo
- `check_email_exists()` - Real-time email validation

**Security Measures:**
- Nonce verification on all requests
- Input sanitization (`sanitize_text_field`, `sanitize_email`)
- Server-side validation
- Password hashing (`wp_hash_password`)

### DoRegister_Assets
**File:** `includes/class-doregister-assets.php`

**Responsibilities:**
- CSS/JavaScript enqueuing
- Asset dependency management
- Localization (passing PHP data to JavaScript)
- Admin-specific styling

**Key Methods:**
- `enqueue_scripts()` - Loads JavaScript
- `enqueue_styles()` - Loads CSS
- `enqueue_admin_styles()` - Admin CSS
- `get_countries_list()` - Country list for JavaScript

**JavaScript Localization:**
```php
wp_localize_script('doregister-js', 'doregisterData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('doregister_registration'),
    'loginNonce' => wp_create_nonce('doregister_login'),
    'countries' => $this->get_countries_list()
));
```

Creates global JavaScript object: `doregisterData.ajaxUrl`, `doregisterData.nonce`, etc.

### DoRegister_Admin
**File:** `includes/class-doregister-admin.php`

**Responsibilities:**
- Admin dashboard creation
- User registration list display
- Bulk delete operations
- Pagination handling
- Admin notices

**Key Methods:**
- `add_admin_menu()` - Creates admin menu
- `render_admin_page()` - Displays user list
- `check_and_create_table()` - Manual table creation
- `show_admin_notices()` - Success/error messages

---

## 5. Request Flow

### Frontend Requests

#### Registration Form Request Flow

```
1. User visits page with [doregister_form] shortcode
   └─> WordPress processes shortcode
       └─> DoRegister_Registration::render_registration_form()
           └─> Returns HTML form (5 steps)

2. User fills form, clicks "Next"
   └─> JavaScript (doregister.js) validates step
       └─> If valid: goToStep(nextStep)
           └─> Updates UI, progress bar, saves to localStorage

3. User completes Step 5, clicks "Submit"
   └─> JavaScript: submitRegistration()
       └─> Validates all steps
       └─> Collects form data
       └─> AJAX POST to admin-ajax.php
           └─> Action: 'doregister_register'
           └─> DoRegister_Ajax::handle_registration()
               ├─> Verify nonce
               ├─> Sanitize inputs
               ├─> Validate fields
               ├─> Check email uniqueness
               ├─> Hash password
               ├─> Insert into database
               ├─> Create session
               └─> Return JSON response
                   └─> JavaScript handles response
                       ├─> Success: Clear localStorage, show message, redirect
                       └─> Error: Show field errors, re-enable button
```

#### Login Request Flow

```
1. User visits page with [doregister_login] shortcode
   └─> DoRegister_Login::render_login_form()
       └─> Returns HTML login form

2. User enters credentials, clicks "Login"
   └─> JavaScript: submitLogin()
       └─> AJAX POST to admin-ajax.php
           └─> Action: 'doregister_login'
           └─> DoRegister_Ajax::handle_login()
               ├─> Verify nonce
               ├─> Sanitize email
               ├─> Get user by email
               ├─> Verify password
               ├─> Create session
               └─> Return JSON response
                   └─> JavaScript redirects to profile page
```

#### Profile Page Request Flow

```
1. User visits page with [doregister_profile] shortcode
   └─> DoRegister_Profile::render_profile_page()
       ├─> Check session: $_SESSION['doregister_user_id']
       ├─> If not logged in: Return login prompt
       └─> If logged in:
           ├─> Get user by ID from database
           └─> Return HTML profile page with user data
```

### Admin Requests

#### Admin Dashboard Request Flow

```
1. Admin visits WordPress admin → DoRegister menu
   └─> DoRegister_Admin::render_admin_page()
       ├─> Check for bulk delete POST request
       │   ├─> Verify nonce
       │   ├─> Check capability (manage_options)
       │   ├─> Delete selected users
       │   └─> Show success message
       ├─> Get pagination parameters (page number)
       ├─> Fetch users from database (paginated)
       ├─> Get total user count
       └─> Render HTML table with user list
```

### AJAX Requests

#### AJAX Request Flow (General Pattern)

```
1. JavaScript makes AJAX request
   └─> POST to admin-ajax.php
       ├─> action: 'doregister_*'
       ├─> nonce: Security token
       └─> data: Form data

2. WordPress routes to handler
   └─> wp_ajax_{action} or wp_ajax_nopriv_{action}
       └─> DoRegister_Ajax::handle_*()
           ├─> Verify nonce
           ├─> Sanitize inputs
           ├─> Validate data
           ├─> Process request
           └─> wp_send_json_success() or wp_send_json_error()
               └─> JavaScript receives JSON response
```

**AJAX Actions:**
- `doregister_register` - Registration submission
- `doregister_login` - Login authentication
- `doregister_logout` - Session destruction
- `doregister_upload_photo` - File upload
- `doregister_check_email` - Email uniqueness check

### REST API

**Not used** - This plugin uses WordPress AJAX (`admin-ajax.php`) instead of REST API.

---

## 6. Data Flow

### Registration Data Flow

```
USER INPUT
  │
  ├─> JavaScript Validation (doregister.js)
  │   ├─> Real-time field validation (on blur)
  │   ├─> Password strength check
  │   ├─> Email uniqueness check (AJAX)
  │   └─> Step validation before navigation
  │
  ├─> localStorage Auto-Save
  │   └─> Saves form data and current step
  │       └─> Restores on page refresh
  │
  ├─> File Upload (Step 4)
  │   └─> AJAX: doregister_upload_photo
  │       ├─> DoRegister_Ajax::handle_photo_upload()
  │       ├─> Validate file type/size
  │       ├─> wp_handle_upload()
  │       ├─> wp_insert_attachment()
  │       └─> Returns image URL
  │           └─> Stored in formData.profile_photo
  │
  └─> Final Submission (Step 5)
      └─> AJAX: doregister_register
          └─> DoRegister_Ajax::handle_registration()
              │
              ├─> SECURITY LAYER
              │   ├─> Nonce verification (CSRF protection)
              │   └─> Input sanitization
              │       ├─> sanitize_text_field()
              │       ├─> sanitize_email()
              │       └─> array_map('sanitize_text_field', $interests)
              │
              ├─> VALIDATION LAYER
              │   ├─> Required field checks
              │   ├─> Email format validation (is_email())
              │   ├─> Email uniqueness check (DoRegister_Database::email_exists())
              │   ├─> Password length (min 8 chars)
              │   ├─> Password match verification
              │   ├─> Phone format validation (regex)
              │   └─> Interests count (min 1)
              │
              ├─> DATA PREPARATION
              │   ├─> Password hashing (wp_hash_password())
              │   ├─> Interests serialization (serialize())
              │   └─> Empty strings → NULL conversion
              │
              └─> DATABASE LAYER
                  └─> DoRegister_Database::insert_user()
                      ├─> Prepared statement (SQL injection protection)
                      ├─> wpdb->insert() with format array
                      └─> Returns user ID
                          │
                          └─> SESSION CREATION
                              ├─> $_SESSION['doregister_user_id'] = $user_id
                              └─> $_SESSION['doregister_user_email'] = $email
                                  │
                                  └─> JSON RESPONSE
                                      └─> JavaScript handles redirect
```

### Login Data Flow

```
USER INPUT (Email/Password)
  │
  ├─> JavaScript Validation
  │   └─> Required field check
  │
  └─> AJAX: doregister_login
      └─> DoRegister_Ajax::handle_login()
          │
          ├─> SECURITY
          │   ├─> Nonce verification
          │   └─> sanitize_email()
          │
          ├─> AUTHENTICATION
          │   ├─> DoRegister_Database::get_user_by_email()
          │   │   └─> Returns user object or null
          │   │
          │   └─> DoRegister_Database::verify_password()
          │       └─> wp_check_password($password, $hash)
          │           └─> Returns true/false
          │
          └─> SESSION CREATION (if valid)
              ├─> $_SESSION['doregister_user_id'] = $user->id
              └─> $_SESSION['doregister_user_email'] = $user->email
                  │
                  └─> JSON RESPONSE
                      └─> Redirect to profile page
```

### Profile Data Flow

```
PROFILE PAGE REQUEST
  │
  └─> DoRegister_Profile::render_profile_page()
      │
      ├─> AUTHENTICATION CHECK
      │   └─> isset($_SESSION['doregister_user_id'])
      │       ├─> If false: Return login prompt
      │       └─> If true: Continue
      │
      └─> DATA RETRIEVAL
          └─> DoRegister_Database::get_user_by_id($user_id)
              ├─> Prepared statement query
              ├─> Unserialize interests (maybe_unserialize())
              └─> Returns user object
                  │
                  └─> HTML GENERATION
                      ├─> Escape all output (esc_html(), esc_url())
                      └─> Display categorized user data
```

### Security Measures

#### Nonces (CSRF Protection)
- **Registration:** `wp_create_nonce('doregister_registration')`
- **Login:** `wp_create_nonce('doregister_login')`
- **Verification:** `wp_verify_nonce($_POST['nonce'], 'action_name')`

#### Sanitization
- **Text fields:** `sanitize_text_field()` - Removes HTML, trims whitespace
- **Email:** `sanitize_email()` - Validates and sanitizes email format
- **Arrays:** `array_map('sanitize_text_field', $array)` - Sanitizes each element

#### Capability Checks
- **Admin operations:** `current_user_can('manage_options')` - Only admins can delete users

#### Output Escaping
- **HTML:** `esc_html()` - Escapes HTML entities
- **URLs:** `esc_url()` - Escapes URLs
- **Attributes:** `esc_attr()` - Escapes HTML attributes

#### Password Security
- **Hashing:** `wp_hash_password()` - Uses bcrypt/argon2
- **Verification:** `wp_check_password()` - Secure password comparison

---

## 7. Assets & UI Flow

### CSS Enqueuing

**Frontend CSS:**
```php
// DoRegister_Assets::enqueue_styles()
wp_enqueue_style(
    'doregister-css',
    DOREGISTER_PLUGIN_URL . 'assets/css/doregister.css',
    array(),
    DOREGISTER_VERSION
);
```

**When:** On every frontend page (via `wp_enqueue_scripts` hook)

**Admin CSS:**
```php
// DoRegister_Assets::enqueue_admin_styles($hook)
if ($hook === 'toplevel_page_doregister') {
    wp_enqueue_style('doregister-admin-css', ...);
    wp_add_inline_style('doregister-admin-css', $this->get_admin_styles());
}
```

**When:** Only on DoRegister admin page

### JavaScript Enqueuing

**Frontend JavaScript:**
```php
// DoRegister_Assets::enqueue_scripts()
wp_enqueue_script('jquery'); // Dependency
wp_enqueue_script(
    'doregister-js',
    DOREGISTER_PLUGIN_URL . 'assets/js/doregister.js',
    array('jquery'), // jQuery must load first
    DOREGISTER_VERSION,
    true // Load in footer
);

// Localize script (pass PHP data to JavaScript)
wp_localize_script('doregister-js', 'doregisterData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('doregister_registration'),
    'loginNonce' => wp_create_nonce('doregister_login'),
    'countries' => $this->get_countries_list()
));
```

**When:** On every frontend page

**JavaScript Global Object:**
```javascript
// Available in JavaScript:
doregisterData.ajaxUrl      // "http://site.com/wp-admin/admin-ajax.php"
doregisterData.nonce        // "abc123..." (security token)
doregisterData.loginNonce   // "xyz789..." (login security token)
doregisterData.countries    // ["United States", "United Kingdom", ...]
```

### Frontend Interactions → PHP

#### Multi-Step Form Navigation

```
1. User clicks "Next" button
   └─> JavaScript: .doregister-btn-next click handler
       ├─> validateStep(currentStep)
       ├─> If valid:
       │   ├─> saveStepData(currentStep) → localStorage
       │   └─> goToStep(nextStep)
       │       ├─> Updates UI (shows/hides steps)
       │       ├─> Updates progress bar
       │       ├─> Updates step indicator
       │       └─> Triggers 'doregister:stepChanged' event
       └─> If invalid: Scroll to first error
```

#### Real-Time Validation

```
1. User leaves field (blur event)
   └─> JavaScript: validateField($field)
       ├─> Checks required, format, length
       ├─> If invalid: showFieldError()
       └─> If valid: clearFieldError()
```

#### Email Uniqueness Check

```
1. User leaves email field (blur)
   └─> JavaScript: checkEmailUniqueness(email)
       └─> AJAX: doregister_check_email
           └─> DoRegister_Ajax::check_email_exists()
               ├─> DoRegister_Database::email_exists($email)
               └─> Returns JSON: { exists: true/false }
                   └─> JavaScript shows error if exists
```

#### File Upload

```
1. User selects file
   └─> JavaScript: handlePhotoUpload(file)
       ├─> Validate file type (image/*)
       ├─> Validate file size (< 5MB)
       ├─> Show preview (FileReader API)
       └─> AJAX: doregister_upload_photo
           └─> DoRegister_Ajax::handle_photo_upload()
               ├─> wp_handle_upload()
               ├─> wp_insert_attachment()
               ├─> wp_generate_attachment_metadata()
               └─> Returns JSON: { url: "http://..." }
                   └─> JavaScript stores URL in formData.profile_photo
```

#### Form Submission

```
1. User clicks "Submit Registration"
   └─> JavaScript: submitRegistration()
       ├─> Validates all steps (1-5)
       ├─> Collects all form data
       └─> AJAX: doregister_register
           └─> DoRegister_Ajax::handle_registration()
               ├─> Security checks
               ├─> Validation
               ├─> Database insert
               ├─> Session creation
               └─> Returns JSON response
                   ├─> Success: Clear localStorage, redirect
                   └─> Error: Show field errors, re-enable button
```

### localStorage Auto-Save

**Saving:**
```javascript
// After each step navigation
DoRegister.saveToStorage() {
    this.formData.currentStep = this.currentStep;
    localStorage.setItem('doregister_form_data', JSON.stringify(this.formData));
}
```

**Loading:**
```javascript
// On page load
DoRegister.loadFromStorage() {
    var stored = localStorage.getItem('doregister_form_data');
    if (stored) {
        this.formData = JSON.parse(stored);
        this.currentStep = this.formData.currentStep || 1;
    }
}
```

**Restoring:**
```javascript
// After loading from localStorage
DoRegister.restoreFormData() {
    // Populates all form fields with saved values
    // Restores profile photo preview (URL, not file)
    // Navigates to saved step
}
```

---

## 8. Error Handling & Edge Cases

### Error Handling Strategies

#### PHP Error Handling

**1. Database Errors**
```php
// DoRegister_Database::insert_user()
$result = $wpdb->insert($table_name, $insert_data, $format);
if ($result === false) {
    error_log('DoRegister Insert Error: ' . $wpdb->last_error);
    error_log('DoRegister Insert Query: ' . $wpdb->last_query);
    return false;
}
```
- **Logs to:** WordPress debug log (if `WP_DEBUG_LOG` enabled)
- **User sees:** Generic error message (doesn't expose database details)

**2. Table Creation Failures**
```php
// DoRegister_Database::create_table()
if (!self::table_exists()) {
    error_log('DoRegister: Table creation verification failed');
    return false;
}
```
- **Fallback:** Constructor checks table existence and creates if missing

**3. Missing User Data**
```php
// DoRegister_Profile::render_profile_page()
$user = DoRegister_Database::get_user_by_id($user_id);
if (!$user) {
    return '<div class="doregister-message doregister-error">User not found.</div>';
}
```
- **Handles:** Session has user ID but user was deleted from database

**4. Session Not Started**
```php
// Multiple places check session before use
if (!session_id()) {
    session_start();
}
```
- **Prevents:** "Session not started" errors

#### JavaScript Error Handling

**1. AJAX Errors**
```javascript
$.ajax({
    // ...
    error: function(xhr, status, error) {
        console.error('Registration Error:', status, error);
        console.error('Response:', xhr.responseText);
        self.showMessage('error', 'An error occurred. Please try again.');
    }
});
```
- **Logs to:** Browser console
- **User sees:** User-friendly error message

**2. localStorage Errors**
```javascript
loadFromStorage: function() {
    try {
        this.formData = JSON.parse(stored);
    } catch (e) {
        // JSON parse failed: Reset to empty object
        this.formData = {};
    }
}
```
- **Handles:** Corrupted localStorage data

**3. File Upload Errors**
```javascript
handlePhotoUpload: function(file) {
    if (!file.type.match('image.*')) {
        this.showFieldError($field, 'Please select an image file.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        this.showFieldError($field, 'File size must be less than 5MB.');
        return;
    }
}
```
- **Validates:** File type and size before upload

### Edge Cases Handled

#### 1. Table Doesn't Exist
- **When:** Plugin activated but table creation failed
- **Handling:** Constructor checks and creates table automatically
- **Admin:** Shows warning notice with manual create link

#### 2. Email Already Exists
- **When:** User tries to register with existing email
- **Handling:** 
  - Real-time check on blur (AJAX)
  - Server-side check before insert
  - Shows error message, prevents registration

#### 3. Session Expired
- **When:** User session expires while on profile page
- **Handling:** Profile page checks session, shows login prompt if missing

#### 4. Invalid Nonce
- **When:** CSRF attack or expired nonce
- **Handling:** `wp_verify_nonce()` returns false, request rejected

#### 5. File Upload Failures
- **When:** File too large, wrong type, upload error
- **Handling:** 
  - Client-side validation (type, size)
  - Server-side validation (MIME type, size)
  - Error message shown to user

#### 6. Page Refresh During Registration
- **When:** User refreshes page mid-registration
- **Handling:** 
  - localStorage restores form data
  - Restores current step
  - Restores profile photo preview (URL)

#### 7. Database Connection Failure
- **When:** Database unavailable
- **Handling:** 
  - `$wpdb->insert()` returns false
  - Error logged
  - User sees generic error message

#### 8. Missing Required Fields
- **When:** User submits form with missing required fields
- **Handling:** 
  - Client-side validation prevents submission
  - Server-side validation double-checks
  - Field-specific error messages shown

#### 9. Password Mismatch
- **When:** Password and confirm password don't match
- **Handling:** 
  - Real-time validation on confirm password input
  - Step validation before navigation
  - Server-side validation before insert

#### 10. Invalid Email Format
- **When:** User enters invalid email
- **Handling:** 
  - HTML5 email validation (browser)
  - JavaScript regex validation
  - WordPress `is_email()` validation (server)

### Error Logging

**WordPress Debug Log:**
```php
// Enable in wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Errors logged to: wp-content/debug.log
```

**Logged Errors:**
- Database insert failures
- Table creation failures
- SQL query errors

**Not Logged (User-Facing):**
- Validation errors (shown to user)
- Authentication failures (shown to user)

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                           │
│  ┌────────────────────────────────────────── ────────────┐  │
│  │              DoRegister.php (Entry Point)             │  │
│  │  • Defines constants                                  │  │
│  │  • Registers activation hooks                         │  │
│  │  • Includes all classes                               │  │
│  │  • Instantiates DoRegister (singleton)                │  │
│  └───────────────────────────────────────────────────────┘  │
│                           │                                 │
│                           ▼                                 │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              DoRegister (Main Class)                  │  │
│  │  • Session management                                 │  │
│  │  • Component initialization                           │  │
│  └───────────────────────────────────────────────────────┘  │
│         │         │         │         │         │           │
│         ▼         ▼         ▼         ▼         ▼           │
│  ┌─────────┐ ┌─────────┐ ┌──────┐ ┌──────┐ ┌──────────┐     │
│  │Database │ │Register │ │Login │ │Ajax  │ │Assets    │     │
│  │         │ │         │ │      │ │      │ │          │     │
│  │• Table  │ │• Form   │ │•Form │ │•AJAX │ │•CSS/JS   │     │
│  │• CRUD   │ │• HTML   │ │•HTML │ │•Handl│ │•Enqueue  │     │
│  │• Hash   │ │•Shortcod│ │•Short│ │•Valid│ │•Localize │     │
│  └─────────┘ └─────────┘ └──────┘ └──────┘ └──────────┘     │
│         │         │         │         │         │           │
│         └─────────┴─────────┴─────────┴─────────┘           │
│                           │                                 │
│         ┌─────────────────┴─────────────────┐               │
│         ▼                                   ▼               │
│  ┌──────────────┐                  ┌──────────────┐         │
│  │   Profile    │                  │    Admin     │         │
│  │              │                  │              │         │
│  │• Profile HTML│                  │• Admin Menu  │         │
│  │• Auth Check  │                  │• User List   │         │
│  │• Shortcode   │                  │• Bulk Delete │         │
│  └──────────────┘                  └──────────────┘         │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Browser)                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              doregister.js (JavaScript)              │   │
│  │  • Form validation                                   │   │
│  │  • Step navigation                                   │   │
│  │  • AJAX requests                                     │   │
│  │  • localStorage auto-save                            │   │
│  └──────────────────────────────────────────────────────┘   │
│                           │                                 │
│                           ▼                                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              doregister.css (Styles)                 │   │
│  │  • Form styling                                      │   │
│  │  • Profile page styling                              │   │
│  │  • Responsive design                                 │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Summary

The DoRegister plugin follows a clean, modular architecture:

1. **Entry Point:** `DoRegister.php` loads first, defines constants, registers hooks
2. **Bootstrap:** Singleton pattern ensures single instances, components initialize via `plugins_loaded` hook
3. **Hooks:** Uses WordPress hooks (`init`, `plugins_loaded`, `wp_enqueue_scripts`, AJAX hooks, shortcodes)
4. **Classes:** Each class has a single responsibility (Database, Registration, Login, AJAX, Assets, Admin, Profile)
5. **Request Flow:** Frontend → JavaScript → AJAX → PHP → Database → Response → JavaScript → UI Update
6. **Data Flow:** User input → Validation → Sanitization → Database → Session → Output (with escaping)
7. **Assets:** CSS/JS enqueued via WordPress hooks, localized data passed to JavaScript
8. **Error Handling:** Logging for developers, user-friendly messages for users, graceful degradation

The plugin uses WordPress best practices: prepared statements, nonces, capability checks, sanitization, and output escaping for security.

