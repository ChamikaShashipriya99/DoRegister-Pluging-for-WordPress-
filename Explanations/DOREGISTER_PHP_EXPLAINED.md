# DoRegister.php - Main Plugin File Explained

**A Beginner-Friendly Guide to Understanding WordPress Plugin Architecture**

---

## Table of Contents
1. [What This File Does](#what-this-file-does)
2. [Plugin Header & Security](#plugin-header--security)
3. [Plugin Constants](#plugin-constants)
4. [Including Class Files](#including-class-files)
5. [Activation & Deactivation Hooks](#activation--deactivation-hooks)
6. [The Main Plugin Class](#the-main-plugin-class)
7. [How It All Works Together](#how-it-all-works-together)
8. [Common WordPress Patterns](#common-wordpress-patterns)

---

## What This File Does

**In simple terms:** This file is the **"front door"** of the DoRegister plugin. It's the first file WordPress reads when loading your plugin.

**Think of it like this:**
- It's like the **main entrance** to a building
- It tells WordPress: "Hey, I'm a plugin!"
- It sets up the foundation for everything else
- It starts the plugin running

**What happens when WordPress loads this file:**
1. WordPress reads the plugin header (recognizes it as a plugin)
2. Defines constants (paths, URLs, version)
3. Includes all other class files
4. Registers activation/deactivation hooks
5. Creates the main plugin class instance
6. The plugin starts working!

---

## Plugin Header & Security

### The Plugin Header

```php
/**
 * Plugin Name: DoRegister
 * Description: Advanced multi-step user registration system...
 * Author: Chamika Shashipriya
 * Author URI: https://my-portfolio-html-css-js-sigma.vercel.app/
 * 
 * @package DoRegister
 * @since 1.0.0
 */
```

**What it does:**
- This is a **special comment block** that WordPress reads
- It tells WordPress: "This folder contains a plugin"
- The `Plugin Name:` line is **required** - without it, WordPress won't recognize this as a plugin

**Where you see it:**
- Go to WordPress Admin → Plugins
- You'll see "DoRegister" in the list
- The description appears below the name
- The author info is shown in plugin details

**Why it matters:**
- Without this header, WordPress **ignores** the folder completely
- It's like a "Hello, I'm a plugin!" introduction to WordPress

### The Security Guard

```php
if (!defined('ABSPATH')) {
    exit;
}
```

**What it does:**
- Checks if `ABSPATH` is defined
- `ABSPATH` is a WordPress constant that means "WordPress is loaded"
- If someone tries to open this file directly (bypassing WordPress), the script stops

**Real-world scenario:**
- **Good:** User visits `yoursite.com` → WordPress loads → Plugin runs ✅
- **Bad:** Someone tries `yoursite.com/wp-content/plugins/DoRegister/DoRegister.php` → Script exits ❌

**Why it matters:**
- **Security:** Prevents direct access where WordPress security isn't active
- **Prevents errors:** WordPress functions won't work if WordPress isn't loaded
- **Standard practice:** Almost every WordPress plugin/theme file has this

**This is called a "direct access guard"** - it guards against direct file access.

---

## Plugin Constants

```php
define('DOREGISTER_VERSION', '1.0.0');
define('DOREGISTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOREGISTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOREGISTER_PLUGIN_FILE', __FILE__);
```

### What Constants Are

**Think of constants as "labels" for values that never change:**
- Like a permanent sticky note: "PLUGIN_VERSION = 1.0.0"
- Once defined, you can use `DOREGISTER_VERSION` anywhere in your plugin
- The value stays the same throughout the plugin's execution

### Each Constant Explained

**1. `DOREGISTER_VERSION`**
```php
define('DOREGISTER_VERSION', '1.0.0');
```
- **Value:** `'1.0.0'` (the version number)
- **Used for:** Cache busting (forcing browsers to reload CSS/JS when plugin updates)
- **Example:** When you update to version 1.0.1, browsers download fresh files

**2. `DOREGISTER_PLUGIN_DIR`**
```php
define('DOREGISTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
```
- **Value:** Server path like `/var/www/html/wp-content/plugins/DoRegister/`
- **Used for:** Including PHP files (`require_once DOREGISTER_PLUGIN_DIR . 'includes/file.php'`)
- **`__FILE__`:** Magic constant = full path to current file
- **`plugin_dir_path()`:** WordPress function that gets the plugin folder path

**3. `DOREGISTER_PLUGIN_URL`**
```php
define('DOREGISTER_PLUGIN_URL', plugin_dir_url(__FILE__));
```
- **Value:** URL like `https://yoursite.com/wp-content/plugins/DoRegister/`
- **Used for:** Enqueuing CSS/JS files (`wp_enqueue_script('handle', DOREGISTER_PLUGIN_URL . 'assets/js/file.js')`)
- **`plugin_dir_url()`:** WordPress function that gets the plugin folder URL

**4. `DOREGISTER_PLUGIN_FILE`**
```php
define('DOREGISTER_PLUGIN_FILE', __FILE__);
```
- **Value:** Full path to `DoRegister.php` file
- **Used for:** Activation/deactivation hooks (`register_activation_hook(DOREGISTER_PLUGIN_FILE, 'function')`)
- **Why:** WordPress needs to know which file is the "main" plugin file

### Why Use Constants?

**Instead of writing this everywhere:**
```php
require_once '/var/www/html/wp-content/plugins/DoRegister/includes/file.php';
```

**You write this:**
```php
require_once DOREGISTER_PLUGIN_DIR . 'includes/file.php';
```

**Benefits:**
- ✅ **Easier to read:** Shorter, clearer code
- ✅ **Easier to maintain:** Change path in one place
- ✅ **Portable:** Works on any server (paths are calculated automatically)
- ✅ **Standard practice:** Most WordPress plugins do this

---

## Including Class Files

```php
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-registration.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-login.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-ajax.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-assets.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-admin.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-profile.php';
```

### What `require_once` Does

**`require_once` means:**
- "Load this file"
- "But only load it once" (even if called multiple times)
- "If file doesn't exist, stop everything" (fatal error)

**Alternative:** `include_once` would continue even if file is missing (just a warning)

### Why Load Files Here?

**This is the "loading dock" for all plugin classes:**

1. **Database class** - Handles all database operations
2. **Registration class** - Creates the registration form
3. **Login class** - Creates the login form
4. **AJAX class** - Handles AJAX requests
5. **Assets class** - Loads CSS and JavaScript
6. **Admin class** - Creates admin dashboard
7. **Profile class** - Creates user profile page

**Think of it like:**
- Loading all the tools before starting work
- Each class is a "tool" that does a specific job
- They're all loaded here so they're available when needed

### Why Database Class is Loaded Twice?

**Notice:** Database class is loaded **before** the activation hook AND in the main includes section.

**First load (line 31):**
```php
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
```
- **Why:** Activation hook needs it (line 45 calls `DoRegister_Database::create_table()`)
- **When:** Before activation hook is registered

**Second load (in activation function, line 42):**
```php
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
```
- **Why:** Safety check (in case activation runs before main file loads)
- **When:** Only if activation hook fires

**`require_once` prevents errors:** Even if called twice, file only loads once.

---

## Activation & Deactivation Hooks

### What Are Hooks?

**WordPress hooks are like "event listeners":**
- You tell WordPress: "When X happens, do Y"
- WordPress calls your function at the right time
- You don't control WHEN it runs - WordPress does

**Two types:**
- **Actions:** "Do something" (like run a function)
- **Filters:** "Change something" (like modify text)

### Activation Hook

```php
function doregister_activate() {
    require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
    DoRegister_Database::create_table();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'doregister_activate');
```

**What `register_activation_hook()` does:**
- Tells WordPress: "When this plugin is activated, run `doregister_activate()`"
- **`__FILE__`:** Which plugin file (this file)
- **`'doregister_activate'`:** Function name to call

**When does it run?**
- User clicks "Activate" in WordPress admin → Plugins page
- Runs **once** when activated
- Does **NOT** run on every page load

**What `doregister_activate()` does:**

1. **Loads database class** (safety check)
   ```php
   require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
   ```

2. **Creates database table**
   ```php
   DoRegister_Database::create_table();
   ```
   - Creates `wp_doregister_users` table
   - Stores user registration data
   - Only runs if table doesn't exist

3. **Flushes rewrite rules**
   ```php
   flush_rewrite_rules();
   ```
   - Refreshes WordPress URL structure
   - Ensures permalinks work correctly
   - Important if plugin adds custom URLs later

**Why activation hook?**
- **One-time setup:** Create tables, set default options
- **Separate from normal operation:** Setup code doesn't run every page load
- **User-friendly:** Happens automatically when plugin is activated

### Deactivation Hook

```php
function doregister_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'doregister_deactivate');
```

**What `register_deactivation_hook()` does:**
- Tells WordPress: "When this plugin is deactivated, run `doregister_deactivate()`"
- Runs **once** when user clicks "Deactivate"

**What `doregister_deactivate()` does:**
- **Flushes rewrite rules** (cleans up URL structure)

**What it does NOT do:**
- ❌ Does NOT delete database table (data is preserved)
- ❌ Does NOT delete user registrations
- ❌ Does NOT remove uploaded files

**Why?**
- **User data preservation:** Deactivation shouldn't delete user data
- **Reactivation:** User can reactivate plugin and data is still there
- **Cleanup only:** Removes plugin's impact on WordPress (rewrite rules)

**If you wanted to delete data on deactivation:**
```php
// Example (NOT in actual code)
function doregister_deactivate() {
    DoRegister_Database::drop_table(); // Would delete table
    flush_rewrite_rules();
}
```

**But this plugin doesn't do that** - it preserves data.

---

## The Main Plugin Class

### What is a Class?

**A class is like a blueprint for an object:**
- Defines what an object can do (methods/functions)
- Defines what data it stores (properties/variables)
- You create "instances" (objects) from the class

**Real-world analogy:**
- **Class:** Blueprint for a car
- **Object:** An actual car built from that blueprint
- **Methods:** What the car can do (drive, brake, turn)
- **Properties:** What the car has (color, speed, fuel level)

### The DoRegister Class Structure

```php
class DoRegister {
    private static $instance = null;
    
    public static function get_instance() { /* ... */ }
    private function __construct() { /* ... */ }
    private function init_hooks() { /* ... */ }
    public function start_session() { /* ... */ }
    public function init() { /* ... */ }
}
```

**This class uses the Singleton pattern** - ensures only one instance exists.

### Singleton Pattern Explained

**The Singleton pattern means:**
- "Only create ONE instance of this class"
- "If someone asks for an instance, give them the same one"
- "Prevent creating multiple instances"

**How it works:**

**1. Private static property:**
```php
private static $instance = null;
```
- Stores the single instance
- `private` = can't access from outside class
- `static` = belongs to class, not individual objects
- `null` = no instance created yet

**2. Public static getter method:**
```php
public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```
- **`public static`:** Can call without creating object (`DoRegister::get_instance()`)
- **Checks:** "Do we have an instance?"
- **Creates:** If no instance, create one (`new self()`)
- **Returns:** Always returns the same instance

**3. Private constructor:**
```php
private function __construct() {
    $this->init_hooks();
}
```
- **`private`:** Can't call `new DoRegister()` from outside
- **`__construct()`:** Special method that runs when object is created
- **Forces:** Must use `get_instance()` to create object

**Why Singleton?**
- ✅ **Prevents duplicates:** Only one instance exists
- ✅ **Prevents duplicate hooks:** Hooks registered once
- ✅ **Memory efficient:** Doesn't create multiple objects
- ✅ **Centralized control:** One place manages plugin initialization

### The Constructor

```php
private function __construct() {
    $this->init_hooks();
}
```

**What happens:**
1. Object is created (via `get_instance()`)
2. Constructor runs automatically
3. Calls `$this->init_hooks()`
4. Hooks are registered with WordPress

**`$this` means:** "This object" (the current instance)

### Registering WordPress Hooks

```php
private function init_hooks() {
    add_action('init', array($this, 'start_session'), 1);
    add_action('plugins_loaded', array($this, 'init'));
}
```

**What `add_action()` does:**
- Tells WordPress: "When X happens, call this function"
- **First parameter:** Hook name (`'init'`, `'plugins_loaded'`)
- **Second parameter:** What to call (function name or array)
- **Third parameter:** Priority (optional, default 10)

**Array syntax `array($this, 'method')`:**
- **`$this`:** The current object
- **`'method'`:** Method name to call
- **Means:** "Call `$this->method()` when hook fires"

**Two hooks registered:**

**1. `init` hook:**
```php
add_action('init', array($this, 'start_session'), 1);
```
- **When:** Early in WordPress initialization
- **Priority:** `1` (runs very early)
- **Calls:** `$this->start_session()`
- **Why early:** Sessions must start before other code uses them

**2. `plugins_loaded` hook:**
```php
add_action('plugins_loaded', array($this, 'init'));
```
- **When:** After all plugins are loaded
- **Priority:** Default (10)
- **Calls:** `$this->init()`
- **Why:** Ensures WordPress and other plugins are ready

### Starting PHP Sessions

```php
public function start_session() {
    if (!session_id()) {
        session_start();
    }
}
```

**What sessions are:**
- **Server-side storage** for user data
- **Persists across page loads** (until browser closes)
- **Stored on server** (not in browser cookies)

**What this code does:**
- **Checks:** "Is a session already started?" (`session_id()`)
- **Starts:** If no session, start one (`session_start()`)
- **Skips:** If session exists, do nothing

**Why this plugin uses sessions:**
- Stores logged-in user ID: `$_SESSION['doregister_user_id']`
- Used by profile page to check if user is logged in
- Used by AJAX handlers to identify user

**Why check first?**
- **Prevents errors:** Starting session twice causes errors
- **Headers already sent:** Must start before any output
- **Safe:** Can call this multiple times safely

### Initializing Plugin Components

```php
public function init() {
    DoRegister_Database::get_instance();
    DoRegister_Registration::get_instance();
    DoRegister_Login::get_instance();
    DoRegister_Ajax::get_instance();
    DoRegister_Assets::get_instance();

    if (is_admin()) {
        DoRegister_Admin::get_instance();
    }

    DoRegister_Profile::get_instance();
}
```

**This is the "orchestration" method** - it starts all plugin components.

**What each line does:**

**1. Database Handler:**
```php
DoRegister_Database::get_instance();
```
- Creates database class instance
- Ensures database table exists
- Provides methods for database operations

**2. Registration Form:**
```php
DoRegister_Registration::get_instance();
```
- Creates registration class instance
- Registers `[doregister_form]` shortcode
- Form appears when shortcode is used

**3. Login Form:**
```php
DoRegister_Login::get_instance();
```
- Creates login class instance
- Registers `[doregister_login]` shortcode
- Login form appears when shortcode is used

**4. AJAX Handlers:**
```php
DoRegister_Ajax::get_instance();
```
- Creates AJAX class instance
- Registers AJAX endpoints
- Handles form submissions, file uploads, etc.

**5. Assets Manager:**
```php
DoRegister_Assets::get_instance();
```
- Creates assets class instance
- Registers hooks to load CSS/JS
- Manages frontend and admin styles

**6. Admin Dashboard (Conditional):**
```php
if (is_admin()) {
    DoRegister_Admin::get_instance();
}
```
- **`is_admin()`:** WordPress function - returns `true` if in admin area
- **Only loads:** If user is in WordPress admin
- **Why conditional:** Saves resources on frontend pages
- Creates admin menu and dashboard

**7. Profile Page:**
```php
DoRegister_Profile::get_instance();
```
- Creates profile class instance
- Registers `[doregister_profile]` shortcode
- Profile page appears when shortcode is used

**Why call `get_instance()`?**
- Each class uses Singleton pattern
- `get_instance()` creates instance if needed
- If instance exists, returns existing one
- Safe to call multiple times

---

## How It All Works Together

### Execution Flow

**Here's what happens when WordPress loads this plugin:**

```
1. WordPress reads DoRegister.php
   ├─> Reads plugin header (recognizes as plugin)
   ├─> Checks security guard (ABSPATH)
   └─> Defines constants

2. Includes database class
   └─> Makes DoRegister_Database class available

3. Registers activation hook
   └─> WordPress remembers: "When activated, run doregister_activate()"

4. Registers deactivation hook
   └─> WordPress remembers: "When deactivated, run doregister_deactivate()"

5. Includes all other class files
   └─> Makes all classes available (but not instantiated yet)

6. Creates DoRegister instance
   └─> DoRegister::get_instance()
       ├─> Creates object (if first time)
       ├─> Runs constructor
       │   └─> Calls init_hooks()
       │       ├─> Registers 'init' hook → start_session()
       │       └─> Registers 'plugins_loaded' hook → init()
       └─> Returns instance

7. WordPress continues loading...

8. WordPress fires 'init' hook
   └─> Calls DoRegister->start_session()
       └─> Starts PHP session

9. WordPress fires 'plugins_loaded' hook
   └─> Calls DoRegister->init()
       ├─> Creates Database instance
       ├─> Creates Registration instance
       ├─> Creates Login instance
       ├─> Creates AJAX instance
       ├─> Creates Assets instance
       ├─> Creates Admin instance (if in admin)
       └─> Creates Profile instance

10. Plugin is fully loaded and ready!
```

### Visual Timeline

```
WordPress Loads
    │
    ├─> DoRegister.php executed
    │   ├─> Constants defined
    │   ├─> Classes included
    │   └─> DoRegister::get_instance() called
    │       └─> Hooks registered
    │
    ├─> WordPress core loads
    │
    ├─> 'init' hook fires
    │   └─> start_session() runs
    │
    ├─> All plugins loaded
    │
    └─> 'plugins_loaded' hook fires
        └─> init() runs
            └─> All plugin components initialized
```

---

## Common WordPress Patterns

### Pattern 1: Plugin Header

**Every WordPress plugin needs this:**
```php
/**
 * Plugin Name: Your Plugin Name
 */
```

**Why:** WordPress scans for this to find plugins.

### Pattern 2: Direct Access Guard

**Almost every plugin file starts with:**
```php
if (!defined('ABSPATH')) {
    exit;
}
```

**Why:** Security - prevents direct file access.

### Pattern 3: Constants for Paths

**Most plugins define:**
```php
define('PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));
```

**Why:** Reusable paths, easier maintenance.

### Pattern 4: Activation/Deactivation Hooks

**Standard setup:**
```php
register_activation_hook(__FILE__, 'activate_function');
register_deactivation_hook(__FILE__, 'deactivate_function');
```

**Why:** One-time setup and cleanup.

### Pattern 5: Singleton Main Class

**Common structure:**
```php
class PluginName {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Setup code
    }
}
```

**Why:** Prevents duplicate instances and hook registrations.

### Pattern 6: Hook Registration in Constructor

**Typical approach:**
```php
private function __construct() {
    add_action('init', array($this, 'method'));
    add_action('plugins_loaded', array($this, 'init'));
}
```

**Why:** Hooks registered when object is created.

### Pattern 7: Conditional Admin Loading

**Common practice:**
```php
if (is_admin()) {
    // Load admin-only code
}
```

**Why:** Saves resources on frontend pages.

### Pattern 8: Separation of Concerns

**File structure:**
- Main file: Sets up plugin, defines constants
- Class files: Handle specific functionality
- Each class: Single responsibility

**Why:** Easier to maintain, understand, and test.

---

## Key Takeaways

### What You Should Remember

1. **Plugin Header:** Required for WordPress to recognize plugin
2. **Security Guard:** Prevents direct file access
3. **Constants:** Reusable paths and version info
4. **Activation Hook:** One-time setup (create tables, etc.)
5. **Singleton Pattern:** Ensures only one instance exists
6. **WordPress Hooks:** Integrate plugin with WordPress lifecycle
7. **Separation:** Main file orchestrates, classes do the work

### Why This Structure Works

- ✅ **Clear organization:** Easy to find where things happen
- ✅ **WordPress standards:** Follows common plugin patterns
- ✅ **Maintainable:** Easy to modify individual parts
- ✅ **Scalable:** Easy to add new features
- ✅ **Secure:** Prevents common security issues

### Next Steps for Learning

1. **Read the class files:** See how each class implements its responsibility
2. **Study WordPress hooks:** Learn more about `add_action()` and `add_filter()`
3. **Understand Singleton:** Research the Singleton design pattern
4. **Practice:** Try creating your own simple plugin using these patterns

---

## Summary

**DoRegister.php is the "conductor" of the plugin orchestra:**

- It doesn't play music itself (doesn't render forms or handle AJAX)
- But it tells everyone **when** to start playing
- It sets up the stage (defines constants, includes files)
- It coordinates all the musicians (initializes all classes)
- It follows WordPress's rules (uses hooks properly)

**In one sentence:** This file registers the plugin with WordPress, sets up the foundation, and orchestrates the initialization of all plugin components at the right time in WordPress's lifecycle.

---

*This explanation is designed for WordPress beginners. For more advanced topics, see the ARCHITECTURE.md and OOP_ANALYSIS.md files.*

