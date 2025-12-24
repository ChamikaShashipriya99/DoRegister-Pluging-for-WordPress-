# DoRegister Plugin - Object-Oriented Programming Analysis

## Table of Contents
1. [Classes and Objects](#1-classes-and-objects)
2. [Encapsulation](#2-encapsulation)
3. [Abstraction](#3-abstraction)
4. [Inheritance](#4-inheritance)
5. [Polymorphism](#5-polymorphism)
6. [Dependency Management](#6-dependency-management)
7. [Design Patterns](#7-design-patterns)
8. [WordPress-Specific OOP Usage](#8-wordpress-specific-oop-usage)

---

## 1. Classes and Objects

### What Classes Exist

The plugin contains **8 classes**, each with a specific responsibility:

1. **`DoRegister`** - Main plugin orchestrator class
2. **`DoRegister_Database`** - Database operations (Repository pattern)
3. **`DoRegister_Registration`** - Registration form handler
4. **`DoRegister_Login`** - Login form handler
5. **`DoRegister_Profile`** - User profile page handler
6. **`DoRegister_Ajax`** - AJAX request handlers
7. **`DoRegister_Assets`** - CSS/JavaScript asset management
8. **`DoRegister_Admin`** - WordPress admin dashboard

### Class Responsibilities

#### DoRegister (Main Class)
**File:** `DoRegister.php`

**Responsibilities:**
- Plugin initialization coordinator
- Session management
- Component lifecycle management
- Singleton pattern implementation

**Key Methods:**
- `get_instance()` - Returns singleton instance
- `start_session()` - Initializes PHP session
- `init()` - Initializes all plugin components

#### DoRegister_Database (Repository Pattern)
**File:** `includes/class-doregister-database.php`

**Responsibilities:**
- Database table creation and management
- User CRUD operations (Create, Read, Update, Delete)
- Password hashing and verification
- Data serialization/unserialization
- Email uniqueness checking

**Key Methods:**
- `create_table()` - Creates custom database table
- `insert_user()` - Adds new user record
- `get_user_by_email()` - Retrieves user by email
- `get_user_by_id()` - Retrieves user by ID
- `get_all_users()` - Paginated user list
- `delete_users()` - Bulk delete operation
- `verify_password()` - Password verification
- `email_exists()` - Email uniqueness check

#### DoRegister_Registration (View/Controller Hybrid)
**File:** `includes/class-doregister-registration.php`

**Responsibilities:**
- Multi-step registration form HTML generation
- Shortcode registration and handling
- Form structure definition (5 steps)

**Key Methods:**
- `render_registration_form()` - Generates HTML for registration form

#### DoRegister_Login (View/Controller Hybrid)
**File:** `includes/class-doregister-login.php`

**Responsibilities:**
- Login form HTML generation
- Shortcode registration and handling

**Key Methods:**
- `render_login_form()` - Generates HTML for login form

#### DoRegister_Profile (View/Controller Hybrid)
**File:** `includes/class-doregister-profile.php`

**Responsibilities:**
- User profile page HTML generation
- Session-based authentication check
- User data display

**Key Methods:**
- `render_profile_page()` - Generates HTML for profile page

#### DoRegister_Ajax (Controller)
**File:** `includes/class-doregister-ajax.php`

**Responsibilities:**
- AJAX request handling
- Form submission processing
- File upload handling
- Authentication (login/logout)
- Input validation and sanitization

**Key Methods:**
- `handle_registration()` - Processes registration form
- `handle_login()` - Authenticates user
- `handle_logout()` - Destroys session
- `handle_photo_upload()` - Uploads profile photo
- `check_email_exists()` - Real-time email validation

#### DoRegister_Assets (Service Layer)
**File:** `includes/class-doregister-assets.php`

**Responsibilities:**
- CSS/JavaScript enqueuing
- Asset dependency management
- JavaScript localization (passing PHP data to JS)
- Admin-specific styling

**Key Methods:**
- `enqueue_scripts()` - Loads JavaScript files
- `enqueue_styles()` - Loads CSS files
- `enqueue_admin_styles()` - Admin CSS loading
- `get_countries_list()` - Country list for JavaScript

#### DoRegister_Admin (Controller/View Hybrid)
**File:** `includes/class-doregister-admin.php`

**Responsibilities:**
- WordPress admin dashboard creation
- User registration list display
- Bulk delete operations
- Pagination handling
- Admin notices

**Key Methods:**
- `add_admin_menu()` - Creates admin menu
- `render_admin_page()` - Displays user list
- `check_and_create_table()` - Manual table creation
- `show_admin_notices()` - Success/error messages

### How and Where Objects Are Instantiated

#### Singleton Pattern Instantiation

**All classes use the Singleton pattern**, which means objects are instantiated through a static `get_instance()` method rather than using `new`:

```php
// DoRegister.php, Line 195
DoRegister::get_instance();
```

**Singleton Pattern Structure (Example from DoRegister_Registration):**

```php
// Line 35: Private static property to store instance
private static $instance = null;

// Lines 45-53: Static method to get/create instance
public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}

// Line 63: Private constructor prevents direct instantiation
private function __construct() {
    add_shortcode('doregister_form', array($this, 'render_registration_form'));
}
```

**Why Singleton?**
- Prevents multiple instances (e.g., prevents duplicate shortcode registrations)
- Ensures single point of control
- Reduces memory usage
- Prevents duplicate hook registrations

#### Instantiation Chain

**Objects are instantiated in a specific order:**

```php
// DoRegister.php, Lines 162-184
public function init() {
    // 1. Database handler (must be first - other classes depend on it)
    DoRegister_Database::get_instance();
    
    // 2. Registration form handler
    DoRegister_Registration::get_instance();
    
    // 3. Login form handler
    DoRegister_Login::get_instance();
    
    // 4. AJAX handlers
    DoRegister_Ajax::get_instance();
    
    // 5. Assets manager
    DoRegister_Assets::get_instance();
    
    // 6. Admin dashboard (conditional - only in admin area)
    if (is_admin()) {
        DoRegister_Admin::get_instance();
    }
    
    // 7. Profile page handler
    DoRegister_Profile::get_instance();
}
```

**Execution Flow:**
```
WordPress loads DoRegister.php
  └─> DoRegister::get_instance() (Line 195)
      └─> DoRegister::__construct()
          └─> DoRegister::init_hooks()
              └─> add_action('plugins_loaded', 'init')
                  └─> DoRegister::init() (when plugins_loaded fires)
                      └─> Each class::get_instance() creates singleton
```

---

## 2. Encapsulation

### Use of Public / Protected / Private Properties and Methods

**Encapsulation** is the practice of hiding internal implementation details and exposing only what's necessary. This plugin demonstrates encapsulation through visibility modifiers.

#### Private Properties

**All classes use private static properties for singleton instances:**

```php
// DoRegister.php, Line 92
private static $instance = null;
```

**DoRegister_Database uses private static property for caching:**

```php
// class-doregister-database.php, Line 44
private static $table_name = null; // Cached table name
```

**Why Private?**
- Prevents external modification
- Ensures singleton pattern integrity
- Hides implementation details

#### Private Methods

**Constructors are private in all classes (Singleton pattern):**

```php
// DoRegister.php, Line 117
private function __construct() {
    $this->init_hooks();
}
```

**Private helper methods hide implementation details:**

```php
// DoRegister_Assets.php, Line 221
private function get_admin_styles() {
    // Returns CSS string - implementation hidden from outside
    return '/* CSS code */';
}

// DoRegister_Assets.php, Line 337
private function get_countries_list() {
    // Country list generation - implementation hidden
    return array('United States', 'United Kingdom', ...);
}
```

**Why Private Methods?**
- Hide implementation details
- Prevent external calls
- Allow refactoring without breaking external code
- Enforce internal usage only

#### Public Methods

**Public methods provide the class interface:**

```php
// DoRegister.php, Line 102
public static function get_instance() {
    // Public: External code needs to call this
}

// DoRegister.php, Line 147
public function start_session() {
    // Public: Called by WordPress hook system
}

// DoRegister_Registration.php, Line 85
public function render_registration_form() {
    // Public: Called by WordPress shortcode system
}
```

**Public methods are the "contract" - they define what the class can do.**

#### Protected Methods

**This plugin doesn't use `protected` visibility**, which would be used if there were inheritance relationships. Since all classes are independent (no inheritance), `protected` isn't needed.

**If inheritance existed, protected would be used like:**
```php
// Hypothetical example (not in actual code)
protected function validate_input($data) {
    // Child classes could override this
}
```

### How Data Hiding is Achieved

#### 1. Private Constructors

**Prevents direct instantiation:**

```php
// DoRegister_Registration.php, Line 63
private function __construct() {
    // Cannot do: new DoRegister_Registration(); // Error!
    // Must do: DoRegister_Registration::get_instance(); // Correct
}
```

**Benefit:** Forces use of singleton pattern, prevents multiple instances.

#### 2. Private Static Properties

**Hides singleton instance from external access:**

```php
// DoRegister.php, Line 92
private static $instance = null;

// External code cannot access:
// DoRegister::$instance = new DoRegister(); // Error! Property is private
```

**Benefit:** Ensures singleton integrity - only `get_instance()` can create/modify instance.

#### 3. Private Helper Methods

**Hides implementation details:**

```php
// DoRegister_Assets.php, Line 337
private function get_countries_list() {
    // Implementation hidden - could change from hardcoded array
    // to database query or API call without breaking external code
    return array('United States', ...);
}
```

**Benefit:** Allows internal refactoring without affecting external code.

#### 4. Static Methods for Stateless Operations

**Database operations use static methods (no instance state needed):**

```php
// DoRegister_Database.php, Line 115
public static function create_table() {
    // Static method - doesn't need instance state
    // Can be called: DoRegister_Database::create_table()
}
```

**Why Static?**
- No instance state required
- Utility functions (don't need object context)
- Can be called without instantiation

**However, the class still uses singleton for constructor logic:**

```php
// DoRegister_Database.php, Line 77
private function __construct() {
    // Constructor runs when get_instance() is called
    // Sets up table name and ensures table exists
    self::$table_name = $wpdb->prefix . 'doregister_users';
    if (!self::table_exists()) {
        self::create_table();
    }
}
```

---

## 3. Abstraction

### Abstract Classes or Interfaces

**This plugin does NOT use abstract classes or interfaces.** All classes are concrete (fully implemented).

**Why No Abstraction?**
- Simple plugin structure (no need for inheritance)
- Each class has unique responsibilities
- No shared behavior that would benefit from abstraction

### How Implementation Details Are Hidden

**Abstraction is achieved through method interfaces, not abstract classes:**

#### 1. Method Signatures as Contracts

**Public methods define the interface:**

```php
// DoRegister_Registration.php, Line 85
public function render_registration_form() {
    // Implementation hidden - caller doesn't need to know
    // how HTML is generated (ob_start, ob_get_clean, etc.)
    ob_start();
    // ... HTML generation ...
    return ob_get_clean();
}
```

**Caller only needs to know:**
- Method name: `render_registration_form()`
- Return type: `string` (HTML)
- No parameters required

**Implementation details hidden:**
- Output buffering usage
- HTML structure
- CSS classes used

#### 2. Static Method Interfaces

**Database class provides a clean interface:**

```php
// DoRegister_Database.php, Line 311
public static function insert_user($data) {
    // Caller doesn't need to know:
    // - Password hashing implementation
    // - Data serialization
    // - Format array building
    // - SQL query details
    
    // Caller only provides: array of user data
    // Caller receives: user ID or false
}
```

**Hidden Implementation:**
```php
// Lines 353-354: Password hashing
$data['password'] = wp_hash_password($data['password']);

// Lines 362-364: Array serialization
if (is_array($data['interests'])) {
    $data['interests'] = serialize($data['interests']);
}

// Lines 400-404: Database insertion
$result = $wpdb->insert($table_name, $insert_data, $format);
```

**Abstraction Benefit:** Caller doesn't need to know about `wp_hash_password()`, `serialize()`, or `$wpdb->insert()`.

#### 3. WordPress Hook System as Abstraction

**WordPress hooks abstract method calls:**

```php
// DoRegister_Registration.php, Line 66
add_shortcode('doregister_form', array($this, 'render_registration_form'));
```

**WordPress handles:**
- When to call the method
- How to pass parameters
- Error handling
- Multiple callback support

**Plugin code doesn't need to know WordPress internals.**

#### 4. Dependency Abstraction

**Database class abstracts WordPress database details:**

```php
// DoRegister_Database.php, Line 433
public static function get_user_by_email($email) {
    global $wpdb;
    $table_name = self::get_table_name();
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $email)
    );
}
```

**Hidden from caller:**
- `$wpdb` global variable
- Table name construction (`$wpdb->prefix`)
- Prepared statement syntax
- SQL query details

**Caller only needs:**
```php
$user = DoRegister_Database::get_user_by_email('user@example.com');
```

---

## 4. Inheritance

### Parent and Child Class Relationships

**This plugin does NOT use inheritance.** All classes are independent and don't extend other classes.

**Evidence:**
- No `extends` keywords in any class
- No parent class references
- No `parent::` method calls
- No `protected` visibility (which would be used in inheritance)

**Example of what inheritance would look like (NOT in actual code):**

```php
// Hypothetical parent class (doesn't exist)
abstract class DoRegister_Base {
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}

// Hypothetical child class (doesn't exist)
class DoRegister_Registration extends DoRegister_Base {
    // Would inherit get_instance() method
}
```

### Why Inheritance Was NOT Chosen

**1. No Shared Behavior**
- Each class has unique responsibilities
- No common methods that would benefit from inheritance
- Singleton pattern is implemented individually (not shared)

**2. Composition Over Inheritance**
- Classes work together through composition (calling each other's methods)
- Example: `DoRegister_Ajax` calls `DoRegister_Database::insert_user()`
- This is composition, not inheritance

**3. WordPress Plugin Architecture**
- WordPress plugins typically use composition
- Hook system encourages independent classes
- No need for class hierarchies

**4. Simplicity**
- Independent classes are easier to understand
- No need to trace inheritance chains
- Each class is self-contained

**If Inheritance Were Used, It Would Look Like:**

```php
// Hypothetical example (NOT actual code)
abstract class DoRegister_Handler {
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    
    abstract protected function register_hooks();
}

class DoRegister_Registration extends DoRegister_Handler {
    protected function register_hooks() {
        add_shortcode('doregister_form', array($this, 'render_registration_form'));
    }
}
```

**But this isn't needed because:**
- Each class implements singleton differently (if needed)
- No shared hook registration pattern
- Adds complexity without benefit

---

## 5. Polymorphism

### Method Overriding

**No method overriding exists** because there's no inheritance.

### Interface-Based Behavior

**No interfaces are used**, but WordPress hooks provide polymorphic behavior.

### How WordPress Hooks Enable Polymorphic Behavior

**WordPress hooks allow multiple classes to respond to the same event**, which is a form of polymorphism.

#### 1. Action Hooks - Multiple Handlers

**Multiple classes can hook into the same action:**

```php
// DoRegister.php, Line 132
add_action('init', array($this, 'start_session'), 1);

// DoRegister_Assets.php, Line 70
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

// DoRegister_Assets.php, Line 74
add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
```

**Polymorphic Behavior:**
- Multiple classes respond to `wp_enqueue_scripts`
- Each class has different implementation
- WordPress calls all registered callbacks
- This is **runtime polymorphism** (not compile-time)

#### 2. Callable Arrays as Polymorphic Handlers

**WordPress accepts different callable types:**

```php
// Array callable (object method)
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

// Could also be:
// Function name string
add_action('wp_enqueue_scripts', 'my_function');

// Static method
add_action('wp_enqueue_scripts', array('MyClass', 'static_method'));

// Closure
add_action('wp_enqueue_scripts', function() { /* ... */ });
```

**This is polymorphism** - WordPress treats all callable types the same way.

#### 3. AJAX Hooks - Same Method, Different Contexts

**Same method handles both logged-in and non-logged-in users:**

```php
// DoRegister_Ajax.php, Lines 67-68
add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));
```

**Polymorphic Behavior:**
- Same method (`handle_registration`) responds to different hooks
- WordPress routes to the same method based on user state
- Method behavior is the same, but context differs

#### 4. Shortcode System - Polymorphic Rendering

**Different classes provide different shortcode implementations:**

```php
// DoRegister_Registration.php, Line 66
add_shortcode('doregister_form', array($this, 'render_registration_form'));

// DoRegister_Login.php, Line 70
add_shortcode('doregister_login', array($this, 'render_login_form'));

// DoRegister_Profile.php, Line 62
add_shortcode('doregister_profile', array($this, 'render_profile_page'));
```

**Polymorphic Behavior:**
- WordPress shortcode system calls different methods
- All methods return `string` (HTML)
- Same interface, different implementations
- This is **ad-hoc polymorphism** (different classes, same interface)

#### 5. Static Method Polymorphism

**Static methods provide consistent interface with different implementations:**

```php
// DoRegister_Database.php
public static function get_user_by_email($email) { /* ... */ }
public static function get_user_by_id($id) { /* ... */ }
public static function get_all_users($limit, $offset) { /* ... */ }
```

**Polymorphic Behavior:**
- All methods are `get_*` (consistent naming)
- All return user data (consistent return type)
- Different parameters and implementations
- Caller can use any method with same expectation

---

## 6. Dependency Management

### Constructor Injection or Service Usage

**This plugin does NOT use constructor injection.** Instead, it uses:

1. **Static method calls** (no dependency injection)
2. **Global variables** (`global $wpdb`)
3. **Direct class instantiation** (via singleton pattern)
4. **WordPress functions** (no dependency injection)

#### Current Dependency Pattern

**Classes call other classes directly via static methods:**

```php
// DoRegister_Ajax.php, Line 225
$user_id = DoRegister_Database::insert_user($user_data);

// DoRegister_Ajax.php, Line 311
$user = DoRegister_Database::get_user_by_email($email);

// DoRegister_Profile.php, Line 113
$user = DoRegister_Database::get_user_by_id($user_id);
```

**This is tight coupling** - `DoRegister_Ajax` directly depends on `DoRegister_Database`.

#### Global Variable Usage

**Database class uses WordPress global:**

```php
// DoRegister_Database.php, Line 80
global $wpdb;
self::$table_name = $wpdb->prefix . 'doregister_users';
```

**Why Global?**
- WordPress convention
- `$wpdb` is WordPress's database abstraction
- Available throughout WordPress execution

**Alternative (Dependency Injection):**

```php
// Hypothetical example (NOT actual code)
class DoRegister_Database {
    private $wpdb;
    
    public function __construct($wpdb) {
        $this->wpdb = $wpdb; // Dependency injection
    }
}
```

**But this isn't used because:**
- WordPress uses globals extensively
- Singleton pattern conflicts with constructor injection
- Adds complexity without clear benefit

### Coupling Between Classes

#### Tight Coupling Examples

**1. DoRegister_Ajax → DoRegister_Database**

```php
// DoRegister_Ajax.php, Line 156
elseif (DoRegister_Database::email_exists($email)) {
    // Direct static method call - tight coupling
}

// DoRegister_Ajax.php, Line 225
$user_id = DoRegister_Database::insert_user($user_data);
// Direct dependency - cannot easily swap database implementation
```

**2. DoRegister_Admin → DoRegister_Database**

```php
// DoRegister_Admin.php, Line 69
$result = DoRegister_Database::create_table();

// DoRegister_Admin.php, Line 108
if (!DoRegister_Database::table_exists()) {

// DoRegister_Admin.php, Line 196
$users = DoRegister_Database::get_all_users($per_page, $offset);
```

**3. DoRegister_Profile → DoRegister_Database**

```php
// DoRegister_Profile.php, Line 113
$user = DoRegister_Database::get_user_by_id($user_id);
```

#### Loose Coupling Examples

**1. WordPress Hook System**

```php
// DoRegister_Registration.php, Line 66
add_shortcode('doregister_form', array($this, 'render_registration_form'));
```

**Loose Coupling:**
- WordPress doesn't know about `DoRegister_Registration` class
- WordPress only knows about the callable
- Can swap implementation without changing WordPress core

**2. Callable Arrays**

```php
// DoRegister.php, Line 132
add_action('init', array($this, 'start_session'), 1);
```

**Loose Coupling:**
- WordPress doesn't care about the class
- Only needs a callable (function, method, closure)
- Implementation can change without affecting WordPress

#### Dependency Graph

```
DoRegister (Main)
  ├─> DoRegister_Database (called via static methods)
  ├─> DoRegister_Registration (instantiated)
  ├─> DoRegister_Login (instantiated)
  ├─> DoRegister_Ajax (instantiated)
  │     └─> DoRegister_Database (static calls - tight coupling)
  ├─> DoRegister_Assets (instantiated)
  ├─> DoRegister_Admin (instantiated, conditional)
  │     └─> DoRegister_Database (static calls - tight coupling)
  └─> DoRegister_Profile (instantiated)
        └─> DoRegister_Database (static calls - tight coupling)
```

**Coupling Analysis:**
- **Tight Coupling:** AJAX, Admin, Profile → Database (via static methods)
- **Loose Coupling:** All classes → WordPress (via hooks)
- **No Circular Dependencies:** Clean dependency flow

#### Why This Coupling is Acceptable

**1. Small Plugin Scope**
- Only 8 classes
- Clear responsibilities
- Easy to understand dependencies

**2. Static Methods for Utilities**
- Database operations are stateless utilities
- No need for dependency injection
- Static methods are appropriate

**3. WordPress Conventions**
- WordPress plugins typically use direct class calls
- Global variables are WordPress standard
- No need for complex DI containers

**If Dependency Injection Were Used:**

```php
// Hypothetical example (NOT actual code)
class DoRegister_Ajax {
    private $database;
    
    public function __construct(DoRegister_Database $database) {
        $this->database = $database; // Dependency injection
    }
    
    public function handle_registration() {
        $user_id = $this->database->insert_user($user_data);
        // Instead of: DoRegister_Database::insert_user($user_data)
    }
}

// Usage:
$database = DoRegister_Database::get_instance();
$ajax = new DoRegister_Ajax($database);
```

**Benefits of DI (not used here):**
- Easier testing (can inject mock objects)
- More flexible (can swap implementations)
- Clearer dependencies

**Why Not Used:**
- Adds complexity
- Conflicts with singleton pattern
- WordPress doesn't require it
- Plugin is simple enough without it

---

## 7. Design Patterns

### Singleton Pattern

**All 8 classes use the Singleton pattern.**

#### Implementation

```php
// Standard Singleton structure (all classes follow this)
class DoRegister_Registration {
    // 1. Private static property
    private static $instance = null;
    
    // 2. Public static getter
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 3. Private constructor
    private function __construct() {
        // Initialization code
        add_shortcode('doregister_form', array($this, 'render_registration_form'));
    }
}
```

#### Why Singleton Fits This Plugin

**1. Prevents Duplicate Hook Registrations**

```php
// DoRegister_Registration.php, Line 66
add_shortcode('doregister_form', array($this, 'render_registration_form'));
```

**Without Singleton:**
- Multiple instances = multiple shortcode registrations
- Could cause duplicate output or errors

**With Singleton:**
- Only one instance exists
- Shortcode registered once
- Safe to call `get_instance()` multiple times

**2. Ensures Single Point of Control**

```php
// DoRegister.php, Line 162-184
public function init() {
    DoRegister_Database::get_instance();
    DoRegister_Registration::get_instance();
    // ... etc
}
```

**Benefit:** Can safely call `get_instance()` multiple times without creating duplicates.

**3. Memory Efficiency**

- Only one instance per class exists
- Reduces memory usage
- Prevents unnecessary object creation

**4. WordPress Plugin Architecture**

- WordPress plugins are loaded on every request
- Singleton ensures initialization happens once
- Prevents duplicate work

### Repository Pattern

**`DoRegister_Database` implements the Repository pattern.**

#### Pattern Structure

```php
// Repository Pattern: Encapsulates database operations
class DoRegister_Database {
    // All database operations in one class
    public static function create_table() { /* ... */ }
    public static function insert_user($data) { /* ... */ }
    public static function get_user_by_email($email) { /* ... */ }
    public static function get_user_by_id($id) { /* ... */ }
    public static function get_all_users($limit, $offset) { /* ... */ }
    public static function delete_users($ids) { /* ... */ }
    public static function email_exists($email) { /* ... */ }
}
```

#### Why Repository Pattern Fits

**1. Centralizes Database Logic**

- All database operations in one class
- Easy to find and maintain
- Single responsibility (database only)

**2. Abstracts Database Details**

```php
// Caller doesn't need to know:
// - Table name construction
// - SQL query syntax
// - Prepared statement format
// - WordPress $wpdb usage

// Caller only needs:
$user = DoRegister_Database::get_user_by_email('user@example.com');
```

**3. Easy to Test/Mock**

- Can swap database implementation
- All queries in one place
- Easy to mock for testing

**4. Consistent Interface**

- All methods follow same pattern
- Consistent error handling
- Consistent return types

### Service Layer Pattern

**`DoRegister_Assets` implements the Service Layer pattern.**

#### Pattern Structure

```php
// Service Layer: Provides services to other classes
class DoRegister_Assets {
    // Service methods
    public function enqueue_scripts() { /* ... */ }
    public function enqueue_styles() { /* ... */ }
    public function enqueue_admin_styles($hook) { /* ... */ }
    
    // Private helper methods
    private function get_admin_styles() { /* ... */ }
    private function get_countries_list() { /* ... */ }
}
```

#### Why Service Layer Fits

**1. Centralized Asset Management**

- All CSS/JS enqueuing in one class
- Consistent asset loading
- Easy to modify asset URLs/versions

**2. Provides Services to Other Classes**

- Other classes don't need to know about asset loading
- Assets class handles all WordPress enqueue details
- Separation of concerns

**3. Reusable Services**

```php
// DoRegister_Assets.php, Line 337
private function get_countries_list() {
    // Service method - could be used by multiple classes
    return array('United States', ...);
}
```

### MVC-Like Pattern (Hybrid)

**The plugin uses a simplified MVC-like structure:**

- **Model:** `DoRegister_Database` (data layer)
- **View:** `DoRegister_Registration`, `DoRegister_Login`, `DoRegister_Profile` (presentation)
- **Controller:** `DoRegister_Ajax`, `DoRegister_Admin` (business logic)

#### Model (Data Layer)

```php
// DoRegister_Database = Model
class DoRegister_Database {
    // Data operations only
    public static function insert_user($data) { /* ... */ }
    public static function get_user_by_id($id) { /* ... */ }
}
```

#### View (Presentation Layer)

```php
// DoRegister_Registration = View
class DoRegister_Registration {
    // Presentation only
    public function render_registration_form() {
        // Returns HTML string
        return ob_get_clean();
    }
}
```

#### Controller (Business Logic)

```php
// DoRegister_Ajax = Controller
class DoRegister_Ajax {
    // Business logic
    public function handle_registration() {
        // 1. Validate input
        // 2. Process data
        // 3. Call model (Database)
        // 4. Return response
    }
}
```

**Why MVC-Like Pattern Fits:**

- **Separation of Concerns:** Each layer has distinct responsibility
- **Maintainability:** Easy to modify one layer without affecting others
- **Testability:** Can test each layer independently

**Not Pure MVC Because:**
- Views also register hooks (controller responsibility)
- Controllers also generate responses (view responsibility)
- More of a hybrid pattern

### Factory Pattern (Implicit)

**Singleton `get_instance()` methods act as simple factories:**

```php
// Factory-like behavior
public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self(); // Creates instance
    }
    return self::$instance; // Returns instance
}
```

**Why Factory-Like:**
- Creates objects (factory responsibility)
- Hides creation logic (private constructor)
- Returns consistent instances

**Not Full Factory Because:**
- Only creates one type (same class)
- No parameters for different types
- More of a "Singleton Factory"

### Strategy Pattern (WordPress Hooks)

**WordPress hooks enable Strategy pattern-like behavior:**

```php
// Different strategies for same hook
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

// Multiple classes can provide different strategies
// DoRegister_Assets provides asset loading strategy
// Other plugins could provide different strategies
```

**Why Strategy-Like:**
- Multiple implementations for same interface (hook)
- Can swap implementations (different plugins)
- Runtime selection (WordPress chooses which to call)

---

## 8. WordPress-Specific OOP Usage

### Classes Hooked Into Actions/Filters

#### Action Hooks

**1. `init` Hook**

```php
// DoRegister.php, Line 132
add_action('init', array($this, 'start_session'), 1);
```

**OOP Aspect:**
- Object method (`$this->start_session`) hooked into WordPress
- WordPress calls object method, not standalone function
- Demonstrates OOP integration with WordPress

**2. `plugins_loaded` Hook**

```php
// DoRegister.php, Line 135
add_action('plugins_loaded', array($this, 'init'));
```

**OOP Aspect:**
- Object method initializes other objects
- Demonstrates object composition
- Objects work together through WordPress hooks

**3. `wp_enqueue_scripts` Hook**

```php
// DoRegister_Assets.php, Line 70
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

// DoRegister_Assets.php, Line 74
add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
```

**OOP Aspect:**
- Same object, different methods on same hook
- Object encapsulates related functionality
- Methods are part of object's interface

**4. `admin_enqueue_scripts` Hook**

```php
// DoRegister_Assets.php, Line 79
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
```

**OOP Aspect:**
- Conditional behavior (admin only)
- Object method receives parameter (`$hook`)
- Demonstrates method parameters in hooks

**5. `admin_menu` Hook**

```php
// DoRegister_Admin.php, Line 47
add_action('admin_menu', array($this, 'add_admin_menu'));
```

**OOP Aspect:**
- Object creates WordPress admin interface
- Encapsulates admin functionality in class
- Separation of admin code from frontend code

**6. `admin_init` Hook**

```php
// DoRegister_Admin.php, Line 50
add_action('admin_init', array($this, 'check_and_create_table'));
```

**OOP Aspect:**
- Object handles admin initialization
- Method called by WordPress at right time
- Demonstrates lifecycle management

**7. `admin_notices` Hook**

```php
// DoRegister_Admin.php, Line 53
add_action('admin_notices', array($this, 'show_admin_notices'));
```

**OOP Aspect:**
- Object controls when notices appear
- Encapsulates notice logic
- Conditional display (only on specific page)

#### AJAX Hooks

**AJAX handlers demonstrate OOP with WordPress:**

```php
// DoRegister_Ajax.php, Lines 67-68
add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));
```

**OOP Aspects:**
- Object methods handle AJAX requests
- Same method, different hooks (polymorphism)
- Object encapsulates request handling logic
- Methods are part of object's public interface

#### Shortcode Hooks

**Shortcodes demonstrate OOP integration:**

```php
// DoRegister_Registration.php, Line 66
add_shortcode('doregister_form', array($this, 'render_registration_form'));

// DoRegister_Login.php, Line 70
add_shortcode('doregister_login', array($this, 'render_login_form'));

// DoRegister_Profile.php, Line 62
add_shortcode('doregister_profile', array($this, 'render_profile_page'));
```

**OOP Aspects:**
- Object methods generate HTML
- Each class handles its own shortcode
- Encapsulation: HTML generation hidden in methods
- Polymorphism: Different classes, same interface (return string)

### Separation of Concerns

#### Admin vs Frontend Separation

**Conditional Loading:**

```php
// DoRegister.php, Lines 179-181
if (is_admin()) {
    DoRegister_Admin::get_instance();
}
```

**OOP Benefit:**
- Admin code only loads in admin area
- Frontend code unaffected
- Clear separation of concerns

#### Frontend Classes

**1. Registration (Frontend)**

```php
// DoRegister_Registration.php
class DoRegister_Registration {
    // Only frontend registration form
    public function render_registration_form() { /* ... */ }
}
```

**2. Login (Frontend)**

```php
// DoRegister_Login.php
class DoRegister_Login {
    // Only frontend login form
    public function render_login_form() { /* ... */ }
}
```

**3. Profile (Frontend)**

```php
// DoRegister_Profile.php
class DoRegister_Profile {
    // Only frontend profile page
    public function render_profile_page() { /* ... */ }
}
```

#### Admin Class

```php
// DoRegister_Admin.php
class DoRegister_Admin {
    // Only admin dashboard
    public function render_admin_page() { /* ... */ }
    public function add_admin_menu() { /* ... */ }
}
```

**Separation Benefits:**
- Admin code doesn't affect frontend
- Frontend code doesn't affect admin
- Easy to modify one without affecting other
- Clear code organization

#### AJAX Class (Shared)

```php
// DoRegister_Ajax.php
class DoRegister_Ajax {
    // Handles both frontend and admin AJAX
    // But methods are called from frontend JavaScript
    public function handle_registration() { /* ... */ }
    public function handle_login() { /* ... */ }
}
```

**Why Shared:**
- AJAX requests come from frontend
- But could be used by admin too
- Centralized request handling

### Callable Arrays in WordPress

**WordPress accepts callable arrays for OOP integration:**

```php
// Array callable format: array($object, 'method_name')
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

// WordPress internally does:
// $object->method_name()
```

**OOP Benefits:**
- Objects can hook into WordPress
- Methods maintain object context (`$this`)
- Encapsulation preserved
- Object state accessible in callbacks

### Object State in Hooks

**Objects maintain state across hook calls:**

```php
// DoRegister_Assets.php
class DoRegister_Assets {
    private static $instance = null; // State preserved
    
    public function enqueue_scripts() {
        // Can access object state
        // Can call other object methods
        wp_localize_script('doregister-js', 'doregisterData', array(
            'countries' => $this->get_countries_list() // Calls object method
        ));
    }
    
    private function get_countries_list() {
        // Private method - part of object's internal implementation
        return array('United States', ...);
    }
}
```

**OOP Benefit:**
- Object methods can call other object methods
- Private methods hide implementation
- State persists across hook calls
- Encapsulation maintained

---

## Summary

### OOP Concepts Used

✅ **Classes and Objects:** 8 classes, all using Singleton pattern
✅ **Encapsulation:** Private properties/methods, public interfaces
✅ **Abstraction:** Method interfaces hide implementation details
❌ **Inheritance:** Not used (composition preferred)
✅ **Polymorphism:** WordPress hooks enable polymorphic behavior
⚠️ **Dependency Management:** Static method calls (tight coupling, but acceptable)
✅ **Design Patterns:** Singleton, Repository, Service Layer, MVC-like
✅ **WordPress OOP:** Classes integrated via hooks, callable arrays, separation of concerns

### OOP Strengths

1. **Clear Class Responsibilities:** Each class has single, well-defined purpose
2. **Encapsulation:** Private methods/properties hide implementation
3. **Singleton Pattern:** Prevents duplicate instances and hook registrations
4. **Separation of Concerns:** Admin, frontend, and AJAX clearly separated
5. **WordPress Integration:** Clean OOP integration with WordPress hooks

### Areas for Improvement (Not Required, But Possible)

1. **Dependency Injection:** Could inject dependencies instead of static calls
2. **Interfaces:** Could define interfaces for better abstraction
3. **Abstract Base Class:** Could create base class for shared singleton logic
4. **Dependency Container:** Could use DI container for complex dependencies

**However, these improvements aren't necessary** for a plugin of this size and complexity. The current OOP structure is appropriate and follows WordPress best practices.

---

## OOP Theory Mapping

| OOP Concept | Implementation in Plugin | Example |
|------------|------------------------|---------|
| **Class** | 8 concrete classes | `DoRegister`, `DoRegister_Database` |
| **Object** | Singleton instances | `DoRegister::get_instance()` |
| **Encapsulation** | Private properties/methods | `private static $instance` |
| **Abstraction** | Method interfaces | `public function render_registration_form()` |
| **Inheritance** | Not used | N/A |
| **Polymorphism** | WordPress hooks | Multiple classes on `wp_enqueue_scripts` |
| **Composition** | Classes call each other | `DoRegister_Ajax` → `DoRegister_Database` |
| **Singleton** | All classes | `get_instance()` pattern |
| **Repository** | Database class | `DoRegister_Database` |
| **Service Layer** | Assets class | `DoRegister_Assets` |

This plugin demonstrates solid OOP principles while maintaining WordPress compatibility and simplicity.

