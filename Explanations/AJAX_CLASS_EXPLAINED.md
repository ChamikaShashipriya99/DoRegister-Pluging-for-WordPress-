# class-doregister-ajax.php - AJAX Handler Explained

**A Beginner-Friendly Guide to WordPress AJAX Development**

---

## Table of Contents
1. [What This File Does](#what-this-file-does)
2. [What is AJAX?](#what-is-ajax)
3. [WordPress AJAX Hooks](#wordpress-ajax-hooks)
4. [Singleton Pattern](#singleton-pattern)
5. [Registration Handler](#registration-handler)
6. [Login Handler](#login-handler)
7. [Logout Handler](#logout-handler)
8. [Photo Upload Handler](#photo-upload-handler)
9. [Email Check Handler](#email-check-handler)
10. [Security Measures](#security-measures)
11. [Common WordPress AJAX Patterns](#common-wordpress-ajax-patterns)

---

## What This File Does

**In simple terms:** This file handles all **AJAX requests** from the frontend (JavaScript) to the backend (PHP). It's the "bridge" between what users see in their browser and what happens on the server.

**Think of it like this:**
- Frontend JavaScript says: "Hey server, register this user!"
- This file receives the request
- It processes the data (validates, saves to database)
- It sends back a response: "Success!" or "Error!"
- **No page reload** - everything happens in the background

**What this file handles:**
1. **User registration** - When someone completes the registration form
2. **User login** - When someone logs in
3. **User logout** - When someone logs out
4. **Photo upload** - When someone uploads a profile picture
5. **Email check** - Real-time check if email is already taken

**Why AJAX instead of regular forms?**
- ✅ **Better user experience** - No page reloads
- ✅ **Faster** - Only sends necessary data
- ✅ **Modern** - Users expect instant feedback
- ✅ **Interactive** - Can show loading spinners, progress bars

---

## What is AJAX?

### Understanding AJAX

**AJAX stands for:** Asynchronous JavaScript and XML

**In simple terms:**
- **Asynchronous** = Happens in the background (doesn't block the page)
- **JavaScript** = Runs in the browser
- **XML/JSON** = Data format (this plugin uses JSON)

**Traditional form submission:**
```
User fills form → Clicks Submit → Page reloads → New page shows result
```

**AJAX form submission:**
```
User fills form → Clicks Submit → JavaScript sends request → Server responds → JavaScript updates page (no reload!)
```

### How AJAX Works

**Step-by-step flow:**

1. **User action** (clicks button, types in field)
2. **JavaScript intercepts** (prevents default form submission)
3. **JavaScript sends request** to server (via AJAX)
4. **Server processes** (PHP runs, database updates)
5. **Server sends response** (JSON: `{success: true, message: "..."}`)
6. **JavaScript receives response** (updates page without reload)

**Example:**
```javascript
// JavaScript sends AJAX request
$.ajax({
    url: ajaxurl,  // WordPress AJAX endpoint
    type: 'POST',
    data: {
        action: 'doregister_register',  // Which PHP function to call
        nonce: 'abc123',                 // Security token
        email: 'user@example.com'       // Form data
    },
    success: function(response) {
        // Server responded with success
        alert('Registration successful!');
    }
});
```

**Server receives:**
```php
// WordPress calls this function
public function handle_registration() {
    // Process the data
    // Send JSON response
    wp_send_json_success(array('message' => 'Success!'));
}
```

---

## WordPress AJAX Hooks

### Two Types of AJAX Hooks

**WordPress has TWO types of AJAX hooks:**

**1. `wp_ajax_{action}`** - For logged-in users
**2. `wp_ajax_nopriv_{action}`** - For non-logged-in users (public)

### Why Two Hooks?

**The problem:**
- Registration happens **before** user is logged in
- Login happens **before** user is logged in
- But some actions need logged-in users

**The solution:**
- Register **both** hooks for each action
- WordPress automatically calls the right one:
  - Logged-in user → `wp_ajax_*` fires
  - Non-logged-in user → `wp_ajax_nopriv_*` fires

### How They're Registered

```php
// Registration handlers
add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));

// Login handlers
add_action('wp_ajax_doregister_login', array($this, 'handle_login'));
add_action('wp_ajax_nopriv_doregister_login', array($this, 'handle_login'));
```

**Breaking it down:**

**`wp_ajax_doregister_register`:**
- `wp_ajax_` = WordPress AJAX hook prefix
- `doregister_register` = Action name (unique identifier)
- `array($this, 'handle_registration')` = Method to call

**When JavaScript sends:**
```javascript
data: {
    action: 'doregister_register'  // This matches the hook name
}
```

**WordPress automatically:**
1. Checks if user is logged in
2. Calls `wp_ajax_doregister_register` OR `wp_ajax_nopriv_doregister_register`
3. Both point to same method: `handle_registration()`

**Why register both?**
- **Covers all cases:** Works for logged-in and non-logged-in users
- **Future-proof:** If you add features later, they'll work for both
- **Standard practice:** Most WordPress plugins do this

---

## Singleton Pattern

### Why Singleton?

**Same pattern as other classes:**

```php
private static $instance = null;

public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

**Why Singleton here?**
- ✅ **Prevents duplicate hooks:** AJAX handlers registered once
- ✅ **Prevents duplicate requests:** Same handler called multiple times
- ✅ **Memory efficient:** Only one instance exists
- ✅ **Consistent:** Matches pattern used in other classes

**How it's called:**
```php
// In DoRegister.php
DoRegister_Ajax::get_instance();
```

**What happens:**
1. First call creates instance
2. Constructor runs → Registers all AJAX hooks
3. Subsequent calls return same instance
4. Hooks only registered once

---

## Registration Handler

### The `handle_registration()` Method

**This is the "main" method** - handles the complete registration process.

**What it does:**
1. Verifies security (nonce check)
2. Sanitizes all input data
3. Validates all fields
4. Checks for errors
5. Saves user to database
6. Creates session (auto-login)
7. Sends JSON response

### Step-by-Step Breakdown

#### Step 1: Security Check (Nonce Verification)

```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
    wp_send_json_error(array('message' => 'Security check failed.'));
}
```

**What is a nonce?**
- **Nonce** = Number Used Once
- **Security token** that prevents CSRF attacks
- Generated by WordPress, sent with form, verified on server

**How it works:**
1. WordPress generates nonce: `'abc123xyz'`
2. JavaScript sends nonce with form data
3. Server verifies: "Is this nonce valid?"
4. If invalid → Reject request (security check failed)

**Why it matters:**
- **Prevents CSRF:** Cross-Site Request Forgery attacks
- **WordPress requirement:** All AJAX requests should use nonces
- **Security best practice:** Never trust data from browser

**`wp_send_json_error()`:**
- Sends JSON response: `{success: false, data: {...}}`
- **Exits script** (stops execution)
- Frontend JavaScript receives error response

#### Step 2: Sanitization

```php
$full_name = sanitize_text_field($_POST['full_name'] ?? '');
$email = sanitize_email($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
```

**What is sanitization?**
- **Cleaning input data** to prevent XSS attacks
- Removes HTML tags, special characters
- Validates format (for emails)

**Functions used:**

**`sanitize_text_field()`:**
- Removes HTML tags
- Trims whitespace
- Removes special characters
- Safe for text fields

**`sanitize_email()`:**
- Validates email format
- Removes invalid characters
- Ensures proper email structure

**`??` operator (Null Coalescing):**
- **If `$_POST['full_name']` exists** → Use it
- **If doesn't exist** → Use `''` (empty string)
- Prevents errors if field is missing

**Why NOT sanitize password?**
```php
$password = $_POST['password'] ?? ''; // NOT sanitized
```
- **Passwords need to be hashed** (not stored as plain text)
- Sanitization might change password characters
- We hash it later (one-way encryption)

#### Step 3: Validation

```php
$errors = array();

if (empty($full_name)) {
    $errors['full_name'] = 'Full name is required.';
}

if (empty($email) || !is_email($email)) {
    $errors['email'] = 'Valid email is required.';
} elseif (DoRegister_Database::email_exists($email)) {
    $errors['email'] = 'Email already exists.';
}
```

**What is validation?**
- **Checking if data is correct** before saving
- Ensures required fields are filled
- Ensures data format is correct
- Prevents invalid data in database

**Validation checks:**

**1. Required fields:**
```php
if (empty($full_name)) {
    $errors['full_name'] = 'Full name is required.';
}
```

**2. Email format:**
```php
if (!is_email($email)) {
    $errors['email'] = 'Valid email is required.';
}
```
- `is_email()` = WordPress function that validates email format

**3. Email uniqueness:**
```php
if (DoRegister_Database::email_exists($email)) {
    $errors['email'] = 'Email already exists.';
}
```
- Checks database: "Does this email already exist?"
- Prevents duplicate accounts

**4. Password strength:**
```php
if (empty($password) || strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}
```

**5. Password match:**
```php
if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
```

**6. Phone format:**
```php
if (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
    $errors['phone_number'] = 'Invalid phone number format.';
}
```
- **Regex pattern:** Allows digits, +, -, spaces, parentheses
- Validates phone number format

**Why validate server-side?**
- **Frontend validation can be bypassed** (user can disable JavaScript)
- **Server-side is mandatory** - cannot be bypassed
- **Security:** Always validate on server, even if frontend validates

#### Step 4: Return Errors (If Any)

```php
if (!empty($errors)) {
    wp_send_json_error(array('errors' => $errors, 'message' => 'Please fix the errors below.'));
}
```

**What happens:**
- If validation errors exist → Send them to frontend
- Frontend JavaScript displays errors next to fields
- User can fix errors and resubmit

**Response format:**
```json
{
    "success": false,
    "data": {
        "errors": {
            "email": "Email already exists.",
            "password": "Password must be at least 8 characters."
        },
        "message": "Please fix the errors below."
    }
}
```

#### Step 5: Prepare Data for Database

```php
$user_data = array(
    'full_name' => $full_name,
    'email' => $email,
    'password' => $password,  // Will be hashed in insert_user()
    'phone_number' => $phone_number,
    'country' => $country,
    'city' => $city,
    'gender' => $gender,
    'date_of_birth' => $date_of_birth ? $date_of_birth : null,
    'interests' => $interests,  // Array will be serialized
    'profile_photo' => $profile_photo
);
```

**What this does:**
- Creates array matching database table structure
- Each key = database column name
- Each value = sanitized/validated data

**Special handling:**

**Password:**
- Stored as plain text here (temporarily)
- Will be hashed in `insert_user()` method
- Never stored as plain text in database

**Interests:**
- Array of strings (e.g., `['reading', 'sports']`)
- Will be serialized (converted to string) in database
- Can be unserialized when reading

**Date of birth:**
- Empty string converted to `null` (database-friendly)
- `null` = no value (different from empty string)

#### Step 6: Save to Database

```php
if (!session_id()) {
    session_start();
}

$user_id = DoRegister_Database::insert_user($user_data);
```

**What happens:**
1. **Start session** (if not already started)
   - Needed for storing user login state
   - Session stores user ID and email

2. **Insert user:**
   - Calls database method
   - Password is hashed (inside `insert_user()`)
   - Interests are serialized
   - Returns user ID if successful

#### Step 7: Create Session & Send Response

```php
if ($user_id) {
    $_SESSION['doregister_user_id'] = $user_id;
    $_SESSION['doregister_user_email'] = $email;
    
    wp_send_json_success(array(
        'message' => 'Registration successful!',
        'redirect_url' => home_url('/profile')
    ));
}
```

**What happens:**
1. **Set session variables:**
   - `$_SESSION['doregister_user_id']` = User's ID
   - `$_SESSION['doregister_user_email']` = User's email
   - Used to check if user is logged in

2. **Send success response:**
   - `wp_send_json_success()` = Sends `{success: true, data: {...}}`
   - Includes redirect URL
   - Frontend JavaScript redirects user

**Response format:**
```json
{
    "success": true,
    "data": {
        "message": "Registration successful!",
        "redirect_url": "https://yoursite.com/profile"
    }
}
```

---

## Login Handler

### The `handle_login()` Method

**What it does:**
1. Verifies nonce
2. Sanitizes email input
3. Validates required fields
4. Looks up user by email
5. Verifies password
6. Creates session
7. Sends success response

### Key Differences from Registration

**1. Simpler validation:**
```php
if (empty($email)) {
    $errors['login_email'] = 'Email is required.';
}
if (empty($password)) {
    $errors['login_password'] = 'Password is required.';
}
```
- Only checks if fields are filled
- No format validation (email format checked by `sanitize_email()`)

**2. User lookup:**
```php
$user = DoRegister_Database::get_user_by_email($email);

if (!$user) {
    wp_send_json_error(array('errors' => array('login_email' => 'Invalid email or password.')));
}
```
- **Looks up user** in database by email
- If not found → Generic error message

**3. Password verification:**
```php
if (!DoRegister_Database::verify_password($password, $user->password)) {
    wp_send_json_error(array('errors' => array('login_password' => 'Invalid email or password.')));
}
```
- **Compares** submitted password with stored hash
- Uses `wp_check_password()` (WordPress secure function)
- If doesn't match → Generic error message

**4. Generic error messages:**
```php
'Invalid email or password.'  // Same message for both cases
```

**Why generic messages?**
- **Security:** Prevents email enumeration attacks
- **Email enumeration:** Attacker tries emails to see which exist
- **Generic message:** Doesn't reveal if email exists or password is wrong
- **Best practice:** Always use generic messages for login

**How password verification works:**
1. User submits: `password123`
2. Database has: `$2y$10$hashed_password...` (bcrypt hash)
3. `wp_check_password()` hashes submitted password
4. Compares hashes
5. If match → Login successful

**Password hashing:**
- **One-way encryption:** Cannot be reversed
- **Bcrypt/Argon2:** Secure hashing algorithms
- **WordPress handles:** Automatically uses best algorithm

---

## Logout Handler

### The `handle_logout()` Method

**What it does:**
1. Checks if session exists
2. Unsets session variables
3. Destroys session
4. Sends success response

**The code:**
```php
if (session_id()) {
    unset($_SESSION['doregister_user_id']);
    unset($_SESSION['doregister_user_email']);
    session_destroy();
}

wp_send_json_success(array(
    'message' => 'Logged out successfully.',
    'redirect_url' => home_url('/login')
));
```

**Step-by-step:**

**1. Check session exists:**
```php
if (session_id()) {
```
- `session_id()` returns ID if session exists
- If no session → Skip (nothing to destroy)

**2. Unset variables:**
```php
unset($_SESSION['doregister_user_id']);
unset($_SESSION['doregister_user_email']);
```
- Removes user data from session
- User is no longer "logged in"

**3. Destroy session:**
```php
session_destroy();
```
- Completely destroys session
- Invalidates session ID
- Server forgets about this session

**4. Send response:**
- Success message
- Redirect URL (back to login page)

**Why no nonce check?**
- **Logout is safe:** Worst case = user gets logged out (which they want)
- **No security risk:** Can't cause harm
- **Simpler code:** No need for nonce verification

---

## Photo Upload Handler

### The `handle_photo_upload()` Method

**What it does:**
1. Verifies nonce
2. Checks if file was uploaded
3. Validates file type (images only)
4. Validates file size (max 5MB)
5. Uses WordPress upload handler
6. Creates attachment in media library
7. Generates thumbnails
8. Returns file URL

### Step-by-Step Breakdown

#### Step 1: Security Check

```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
    wp_send_json_error(array('message' => 'Security check failed.'));
}
```

**Same nonce as registration** (because photo upload happens during registration).

#### Step 2: Check File Upload

```php
if (!isset($_FILES['profile_photo'])) {
    wp_send_json_error(array('message' => 'No file uploaded.'));
}
```

**`$_FILES` superglobal:**
- Contains uploaded file information
- Structure: `$_FILES['field_name']`
- Includes: `name`, `type`, `tmp_name`, `size`, `error`

**File information:**
```php
$file = $_FILES['profile_photo'];
// $file['name'] = 'photo.jpg'
// $file['type'] = 'image/jpeg'
// $file['tmp_name'] = '/tmp/phpABC123' (temporary location)
// $file['size'] = 1024000 (bytes)
```

#### Step 3: Validate File Type

```php
$allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');

if (!in_array($file['type'], $allowed_types)) {
    wp_send_json_error(array('message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'));
}
```

**What this does:**
- **MIME type validation:** Checks file type
- **Only allows images:** Prevents executable files (.exe, .php, etc.)
- **Security:** Prevents malicious file uploads

**MIME types:**
- `image/jpeg` = JPEG images
- `image/png` = PNG images
- `image/gif` = GIF images

**Why validate?**
- **Security:** Prevents uploading executable files
- **Data integrity:** Ensures only images are stored
- **User experience:** Clear error messages

#### Step 4: Validate File Size

```php
if ($file['size'] > 5 * 1024 * 1024) {
    wp_send_json_error(array('message' => 'File size exceeds 5MB limit.'));
}
```

**What this does:**
- **5MB limit:** `5 * 1024 * 1024` = 5,242,880 bytes
- **Prevents server overload:** Large files consume resources
- **User experience:** Clear error message

**Why limit size?**
- **Server storage:** Large files fill up disk space
- **Performance:** Large files slow down site
- **Bandwidth:** Large uploads consume bandwidth

#### Step 5: Load WordPress File Functions

```php
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
```

**What these files provide:**

**`file.php`:**
- `wp_handle_upload()` - Handles file uploads securely

**`media.php`:**
- Media library functions
- Attachment creation functions

**`image.php`:**
- Image processing functions
- Thumbnail generation

**Why load these?**
- **Not loaded by default:** Only loaded in admin area
- **AJAX runs in frontend:** Need to load manually
- **WordPress functions:** Use WordPress's secure file handling

#### Step 6: Handle Upload

```php
$upload = wp_handle_upload($file, array('test_form' => false));
```

**What `wp_handle_upload()` does:**
1. **Moves file** from temporary location to uploads folder
2. **Validates file** (security checks)
3. **Returns array** with file info:
   - `$upload['file']` = Full server path
   - `$upload['url']` = Full URL
   - `$upload['type']` = MIME type
   - `$upload['error']` = Error message (if failed)

**`'test_form' => false`:**
- **Skips form validation** (we're using AJAX, not form)
- **Standard for AJAX uploads**

**Check for errors:**
```php
if (isset($upload['error'])) {
    wp_send_json_error(array('message' => $upload['error']));
}
```

#### Step 7: Create Attachment

```php
$attachment = array(
    'post_mime_type' => $upload['type'],
    'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
    'post_content' => '',
    'post_status' => 'inherit'
);

$attach_id = wp_insert_attachment($attachment, $upload['file']);
```

**What this does:**
- **Creates attachment post** in WordPress database
- **Attachments are custom post type** (`attachment`)
- **Stores file metadata** (name, type, location)

**`wp_insert_attachment()`:**
- Creates attachment post
- Returns attachment ID
- Links file to WordPress media library

#### Step 8: Generate Metadata

```php
$attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
wp_update_attachment_metadata($attach_id, $attach_data);
```

**What this does:**
- **Generates thumbnails:** Creates different image sizes
- **WordPress sizes:** thumbnail, medium, large
- **Saves metadata:** Stores size information

**Why generate metadata?**
- **Thumbnails:** Smaller versions for faster loading
- **Responsive images:** Different sizes for different screens
- **WordPress standard:** All media library images have metadata

#### Step 9: Return Success

```php
wp_send_json_success(array(
    'url' => $upload['url'],
    'attachment_id' => $attach_id
));
```

**Response format:**
```json
{
    "success": true,
    "data": {
        "url": "https://yoursite.com/wp-content/uploads/2024/01/photo.jpg",
        "attachment_id": 123
    }
}
```

**Frontend JavaScript:**
- Receives URL
- Displays image preview
- Stores URL for registration form submission

---

## Email Check Handler

### The `check_email_exists()` Method

**What it does:**
1. Sanitizes email input
2. Validates email is provided
3. Checks database if email exists
4. Returns result as JSON

**The code:**
```php
$email = sanitize_email($_POST['email'] ?? '');

if (empty($email)) {
    wp_send_json_error(array('message' => 'Email is required.'));
}

$exists = DoRegister_Database::email_exists($email);

wp_send_json_success(array('exists' => $exists));
```

**When it's called:**
- **Real-time validation:** When user leaves email field (on blur)
- **Instant feedback:** Shows error immediately if email taken
- **Better UX:** User knows before completing entire form

**Response format:**
```json
{
    "success": true,
    "data": {
        "exists": true  // or false
    }
}
```

**Frontend JavaScript:**
- Receives response
- Shows error message if `exists: true`
- Hides error if `exists: false`

**Security consideration:**
- **Email enumeration risk:** Attacker could check which emails are registered
- **Current implementation:** Prioritizes UX over security
- **For higher security:** Add rate limiting or require nonce

---

## Security Measures

### 1. Nonce Verification

**Every AJAX handler checks nonces:**
```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
    wp_send_json_error(array('message' => 'Security check failed.'));
}
```

**Why:**
- **Prevents CSRF:** Cross-Site Request Forgery attacks
- **WordPress requirement:** All AJAX requests should use nonces
- **Security best practice:** Never trust requests without verification

### 2. Input Sanitization

**All input is sanitized:**
```php
$full_name = sanitize_text_field($_POST['full_name'] ?? '');
$email = sanitize_email($_POST['email'] ?? '');
```

**Why:**
- **Prevents XSS:** Cross-Site Scripting attacks
- **Data integrity:** Ensures clean data in database
- **WordPress requirement:** All user input must be sanitized

### 3. Server-Side Validation

**All data is validated:**
```php
if (empty($email) || !is_email($email)) {
    $errors['email'] = 'Valid email is required.';
}
```

**Why:**
- **Frontend can be bypassed:** User can disable JavaScript
- **Server-side is mandatory:** Cannot be bypassed
- **Security:** Always validate on server

### 4. File Upload Security

**Multiple security layers:**
- **MIME type validation:** Only allows images
- **File size limits:** Prevents server overload
- **WordPress functions:** Uses secure WordPress upload handlers
- **Media library integration:** Files stored securely

### 5. Password Security

**Passwords are hashed:**
- **Never stored plain text:** Always hashed
- **WordPress functions:** Uses `wp_hash_password()` and `wp_check_password()`
- **Secure algorithms:** Bcrypt/Argon2 (WordPress chooses best)

### 6. Generic Error Messages

**Login errors are generic:**
```php
'Invalid email or password.'  // Same for both email and password errors
```

**Why:**
- **Prevents email enumeration:** Doesn't reveal if email exists
- **Security best practice:** Don't leak information to attackers

### 7. Capability Checks (Where Needed)

**Some actions check permissions:**
- Not all handlers need this (registration/login are public)
- Admin actions would check `current_user_can()`

---

## Common WordPress AJAX Patterns

### Pattern 1: Register Both Hooks

**Always register both:**
```php
add_action('wp_ajax_{action}', array($this, 'handler'));
add_action('wp_ajax_nopriv_{action}', array($this, 'handler'));
```

**Why:** Works for logged-in and non-logged-in users.

### Pattern 2: Nonce Verification

**Every handler checks nonce:**
```php
if (!wp_verify_nonce($_POST['nonce'], 'action_name')) {
    wp_send_json_error(array('message' => 'Security check failed.'));
}
```

**Why:** Prevents CSRF attacks.

### Pattern 3: Input Sanitization

**Sanitize all input:**
```php
$data = sanitize_text_field($_POST['field'] ?? '');
```

**Why:** Prevents XSS attacks.

### Pattern 4: Server-Side Validation

**Validate all data:**
```php
$errors = array();
if (empty($data)) {
    $errors['field'] = 'Field is required.';
}
if (!empty($errors)) {
    wp_send_json_error(array('errors' => $errors));
}
```

**Why:** Frontend validation can be bypassed.

### Pattern 5: JSON Responses

**Use WordPress functions:**
```php
wp_send_json_success(array('message' => 'Success!'));
wp_send_json_error(array('message' => 'Error!'));
```

**Why:** Standard WordPress way to send JSON responses.

### Pattern 6: Error Handling

**Return errors in consistent format:**
```php
wp_send_json_error(array(
    'errors' => $errors,
    'message' => 'Please fix the errors below.'
));
```

**Why:** Frontend can display errors consistently.

### Pattern 7: Session Management

**Start session if needed:**
```php
if (!session_id()) {
    session_start();
}
```

**Why:** Sessions needed for user authentication.

### Pattern 8: File Upload Security

**Use WordPress functions:**
```php
require_once(ABSPATH . 'wp-admin/includes/file.php');
$upload = wp_handle_upload($file, array('test_form' => false));
```

**Why:** WordPress handles security automatically.

---

## How This File Fits Into the Plugin

### Role in Overall Plugin

**This class is the "request handler" layer:**

```
Plugin Structure:
├── DoRegister.php (Main orchestrator)
├── DoRegister_Database (Data layer)
├── DoRegister_Registration (Frontend form HTML)
├── DoRegister_Login (Frontend form HTML)
├── DoRegister_Profile (Frontend page HTML)
├── DoRegister_Ajax (Request handlers) ← This file
├── DoRegister_Assets (CSS/JS loader)
└── DoRegister_Admin (Admin dashboard)
```

**What it does:**
- **Receives AJAX requests** from frontend JavaScript
- **Processes data** (validates, sanitizes)
- **Interacts with Database** class to save/retrieve data
- **Sends JSON responses** back to frontend
- **Handles authentication** (sessions)

### Data Flow

**Registration flow:**
```
User fills form → JavaScript sends AJAX → handle_registration()
  ├─> Verify nonce
  ├─> Sanitize input
  ├─> Validate data
  ├─> DoRegister_Database::insert_user()
  ├─> Create session
  └─> Send JSON response → JavaScript updates page
```

**Login flow:**
```
User fills form → JavaScript sends AJAX → handle_login()
  ├─> Verify nonce
  ├─> Sanitize email
  ├─> DoRegister_Database::get_user_by_email()
  ├─> Verify password
  ├─> Create session
  └─> Send JSON response → JavaScript redirects
```

### Integration Points

**1. Uses Database Class:**
```php
DoRegister_Database::insert_user($user_data);
DoRegister_Database::get_user_by_email($email);
DoRegister_Database::verify_password($password, $hash);
DoRegister_Database::email_exists($email);
```

**2. Uses WordPress Functions:**
```php
wp_verify_nonce();
wp_send_json_success();
wp_send_json_error();
sanitize_text_field();
sanitize_email();
is_email();
wp_handle_upload();
wp_insert_attachment();
```

**3. Called from Frontend JavaScript:**
```javascript
// JavaScript sends AJAX request
$.ajax({
    url: ajaxurl,
    data: {
        action: 'doregister_register',  // Calls handle_registration()
        nonce: doregisterData.nonce,
        // ... form data
    }
});
```

---

## Key Takeaways

### What You Should Remember

1. **AJAX hooks:** `wp_ajax_*` (logged-in) and `wp_ajax_nopriv_*` (public)
2. **Always register both:** Covers all user types
3. **Nonce verification:** Every handler must verify nonces
4. **Sanitization:** All input must be sanitized
5. **Validation:** Server-side validation is mandatory
6. **JSON responses:** Use `wp_send_json_success()` and `wp_send_json_error()`
7. **Error handling:** Return errors in consistent format
8. **File uploads:** Use WordPress functions for security
9. **Password security:** Always hash passwords, never store plain text
10. **Generic errors:** Use generic messages for login (prevent enumeration)

### Why This Structure Works

- ✅ **Secure:** Multiple security layers (nonces, sanitization, validation)
- ✅ **WordPress standards:** Uses WordPress AJAX patterns
- ✅ **User-friendly:** Real-time feedback, no page reloads
- ✅ **Maintainable:** Clear separation of concerns
- ✅ **Robust:** Handles errors gracefully

### Common Mistakes to Avoid

1. ❌ **Forgetting nonce verification** - CSRF vulnerability
2. ❌ **Not sanitizing input** - XSS vulnerability
3. ❌ **Only frontend validation** - Can be bypassed
4. ❌ **Storing passwords plain text** - Major security risk
5. ❌ **Specific error messages** - Email enumeration risk
6. ❌ **Not registering both hooks** - Doesn't work for all users
7. ❌ **Not handling errors** - Poor user experience

---

## Summary

**`class-doregister-ajax.php` handles all AJAX communication between frontend and backend:**

- **What it does:** Processes AJAX requests (registration, login, logout, file upload, email check)
- **How it works:** Uses WordPress AJAX hooks to receive requests, processes data securely, sends JSON responses
- **Security:** Multiple layers (nonces, sanitization, validation, password hashing)
- **User experience:** Real-time feedback, no page reloads, instant validation
- **WordPress integration:** Follows WordPress AJAX patterns and uses WordPress functions

**In one sentence:** This file acts as the secure bridge between frontend JavaScript and backend PHP, handling all AJAX requests with proper security measures, validation, and error handling, following WordPress best practices.

---

*This explanation is designed for WordPress beginners. For more advanced topics, see the ARCHITECTURE.md and OOP_ANALYSIS.md files.*

