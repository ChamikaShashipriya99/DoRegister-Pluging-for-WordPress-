# class-doregister-database.php - Database Handler Explained

**A Beginner-Friendly Guide to WordPress Database Operations**

---

## Table of Contents
1. [What This File Does](#what-this-file-does)
2. [WordPress Database System](#wordpress-database-system)
3. [Singleton Pattern](#singleton-pattern)
4. [Table Creation](#table-creation)
5. [Inserting Users](#inserting-users)
6. [Retrieving Users](#retrieving-users)
7. [Password Security](#password-security)
8. [Pagination & Bulk Operations](#pagination--bulk-operations)
9. [Security Measures](#security-measures)
10. [Common WordPress Database Patterns](#common-wordpress-database-patterns)

---

## What This File Does

**In simple terms:** This file handles **all database operations** for the DoRegister plugin. It creates tables, saves user data, retrieves user information, and manages everything related to storing data in the database.

**Think of it like this:**
- It's the **"storage manager"** for the plugin
- When someone registers → This file saves their data
- When someone logs in → This file looks up their information
- When admin views users → This file retrieves the list
- It's the **only** file that talks directly to the database

**What this file handles:**
1. **Creating database table** - Sets up the storage structure
2. **Saving users** - Stores registration data
3. **Finding users** - Looks up users by email or ID
4. **Verifying passwords** - Checks if passwords match
5. **Listing users** - Gets all users with pagination
6. **Deleting users** - Removes users from database
7. **Checking email uniqueness** - Prevents duplicate accounts

**Why a separate database class?**
- ✅ **Separation of concerns:** Database logic separate from business logic
- ✅ **Reusability:** Other classes can use database methods
- ✅ **Maintainability:** All database code in one place
- ✅ **Security:** Centralized security measures

---

## WordPress Database System

### Understanding `$wpdb`

**`$wpdb` is WordPress's database abstraction layer:**
- **Global object:** Available everywhere in WordPress
- **Handles SQL queries:** Executes database commands safely
- **Prevents SQL injection:** Uses prepared statements
- **Works with MySQL/MariaDB:** Handles database differences

**How to use it:**
```php
global $wpdb;  // Access WordPress database object
$wpdb->query("SELECT * FROM table");  // Execute query
```

**Why use `$wpdb` instead of direct MySQL?**
- ✅ **WordPress integration:** Works with WordPress's database connection
- ✅ **Security:** Built-in protection against SQL injection
- ✅ **Compatibility:** Handles different database versions
- ✅ **Prefix support:** Automatically handles table prefixes

### Table Prefixes

**WordPress uses table prefixes:**
- **Default:** `wp_` (e.g., `wp_posts`, `wp_users`)
- **Customizable:** Can be changed for security
- **Multi-site:** Different prefixes for different sites

**How prefixes work:**
```php
$wpdb->prefix . 'doregister_users'
// Results in: 'wp_doregister_users' (or custom prefix)
```

**Why prefixes matter:**
- **Security:** Hides table names from attackers
- **Multi-site:** Multiple WordPress installs can share database
- **WordPress standard:** All plugins should use prefixes

### Database Charset & Collation

**Charset = Character encoding:**
- **utf8mb4:** Modern encoding (supports emojis, all Unicode)
- **utf8:** Older encoding (limited characters)

**Collation = How characters are sorted:**
- **utf8mb4_unicode_ci:** Case-insensitive, Unicode-aware sorting

**Why it matters:**
- **Consistency:** Matches WordPress's database encoding
- **Compatibility:** Works with WordPress data
- **Future-proof:** Supports all characters

**How WordPress provides it:**
```php
$charset_collate = $wpdb->get_charset_collate();
// Returns: "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

---

## Singleton Pattern

### Why Singleton?

**Same pattern as other classes:**

```php
private static $instance = null;
private static $table_name = null;

public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

**Why Singleton here?**
- ✅ **Prevents duplicate connections:** Only one database handler
- ✅ **Caches table name:** Avoids repeated string concatenation
- ✅ **Memory efficient:** Single instance shared across plugin
- ✅ **Consistent:** Matches pattern used in other classes

**Table name caching:**
```php
private static $table_name = null;
```
- **Stores:** Full table name (e.g., `'wp_doregister_users'`)
- **Why cache:** Avoids rebuilding string multiple times
- **Performance:** Minor optimization, but good practice

---

## Table Creation

### The `create_table()` Method

**This method creates the custom database table.**

**What it does:**
1. Checks if table exists
2. Gets database charset/collation
3. Builds SQL CREATE TABLE statement
4. Uses `dbDelta()` (WordPress recommended method)
5. Falls back to direct query if needed
6. Verifies table was created

### Step-by-Step Breakdown

#### Step 1: Access Database & Build Table Name

```php
global $wpdb;
$table_name = $wpdb->prefix . 'doregister_users';
```

**What happens:**
- **Accesses WordPress database object**
- **Builds table name** with prefix
- **Result:** `'wp_doregister_users'` (or custom prefix)

#### Step 2: Get Charset & Collation

```php
$charset_collate = $wpdb->get_charset_collate();
```

**What this returns:**
```sql
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

**Why it matters:**
- **Matches WordPress:** Uses same encoding as WordPress tables
- **Supports all characters:** Emojis, special characters, all languages
- **Consistent:** All tables use same encoding

#### Step 3: Check if Table Exists

```php
if (self::table_exists()) {
    self::maybe_update_table();
    return true;
}
```

**What happens:**
- **If table exists:** Update structure if needed, return success
- **If doesn't exist:** Continue to create table

**Why check first?**
- **Prevents errors:** Won't try to create existing table
- **Handles updates:** Can modify existing table structure
- **Idempotent:** Safe to call multiple times

#### Step 4: Build SQL CREATE TABLE Statement

```sql
CREATE TABLE IF NOT EXISTS wp_doregister_users (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name varchar(255) NOT NULL,
    email varchar(191) NOT NULL,
    password varchar(255) NOT NULL,
    phone_number varchar(50) NOT NULL,
    country varchar(100) NOT NULL,
    city varchar(100) DEFAULT NULL,
    gender varchar(20) DEFAULT NULL,
    date_of_birth date DEFAULT NULL,
    interests text DEFAULT NULL,
    profile_photo varchar(255) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Column explanations:**

**1. `id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT`**
- **Primary key:** Unique identifier for each user
- **bigint:** Large integer (supports billions of users)
- **UNSIGNED:** Only positive numbers (0 to 18+ quintillion)
- **AUTO_INCREMENT:** Automatically increases (1, 2, 3, ...)

**2. `full_name varchar(255) NOT NULL`**
- **varchar(255):** Variable-length string, max 255 characters
- **NOT NULL:** Required field (cannot be empty)
- **Stores:** User's full name

**3. `email varchar(191) NOT NULL`**
- **varchar(191):** Reduced from 255 (important!)
- **Why 191?** utf8mb4 charset + UNIQUE index = max 191 characters
- **UNIQUE KEY:** Prevents duplicate emails
- **NOT NULL:** Required field

**4. `password varchar(255) NOT NULL`**
- **varchar(255):** Hashed passwords are long (60+ characters)
- **NOT NULL:** Required field
- **Stored as hash:** Never plain text

**5. `city varchar(100) DEFAULT NULL`**
- **Optional field:** Can be empty
- **DEFAULT NULL:** Empty = NULL (not empty string)
- **NULL vs empty string:** NULL = no value, '' = empty value

**6. `interests text DEFAULT NULL`**
- **text:** Large text field (up to 65,535 characters)
- **Stores:** Serialized array (converted to string)
- **Example:** `"a:2:{i:0;s:10:"technology";i:1;s:6:"sports";}"`

**7. `created_at datetime DEFAULT CURRENT_TIMESTAMP`**
- **Auto-set:** Automatically set when record is created
- **DEFAULT CURRENT_TIMESTAMP:** Uses current date/time
- **No manual input needed**

**8. `updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`**
- **Auto-update:** Changes when record is modified
- **ON UPDATE CURRENT_TIMESTAMP:** Updates automatically
- **Tracks changes:** Know when data was last modified

**9. `PRIMARY KEY (id)`**
- **Primary key:** Unique identifier
- **Indexed:** Fast lookups by ID
- **Required:** Every table needs primary key

**10. `UNIQUE KEY email (email)`**
- **Unique index:** Prevents duplicate emails
- **Fast lookups:** Indexed for quick email searches
- **Enforced:** Database rejects duplicate emails

#### Step 5: Use `dbDelta()`

```php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
$result = dbDelta($sql);
```

**What is `dbDelta()`?**
- **WordPress function:** Recommended way to create/update tables
- **Parses SQL:** Analyzes CREATE TABLE statement
- **Creates or updates:** Handles both new tables and structure changes
- **Robust:** More reliable than direct queries

**Why use `dbDelta()`?**
- ✅ **WordPress recommended:** Official way to create tables
- ✅ **Handles updates:** Can modify existing table structure
- ✅ **Safer:** Better error handling
- ✅ **Future-proof:** Easy to update table structure

**Requirements for `dbDelta()`:**
- **Must have PRIMARY KEY:** Required
- **Must use specific format:** Certain SQL syntax required
- **Case-sensitive:** Column names must match exactly

#### Step 6: Fallback to Direct Query

```php
if (!self::table_exists()) {
    $result = $wpdb->query($sql);
    if ($result === false) {
        error_log('DoRegister: Failed to create table. Error: ' . $wpdb->last_error);
        return false;
    }
}
```

**What happens:**
- **If `dbDelta()` failed:** Try direct SQL query
- **`$wpdb->query()`:** Executes raw SQL
- **Error logging:** Records errors for debugging
- **Returns false:** If creation failed

**Why fallback?**
- **Edge cases:** Some servers have issues with `dbDelta()`
- **Reliability:** Ensures table gets created
- **Debugging:** Logs errors for troubleshooting

### The `table_exists()` Method

```php
public static function table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'doregister_users';
    
    return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
}
```

**What it does:**
- **Queries database:** `SHOW TABLES LIKE 'table_name'`
- **Checks result:** Compares with expected table name
- **Returns:** `true` if exists, `false` if not

**How it works:**
- **`SHOW TABLES`:** MySQL command to list tables
- **`LIKE 'table_name'`:** Filters to specific table
- **`$wpdb->get_var()`:** Returns single value (table name or null)

### The `maybe_update_table()` Method

```php
public static function maybe_update_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'doregister_users';
    
    $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name WHERE Field = 'email'");
    
    if ($column_info && isset($column_info->Type)) {
        preg_match('/varchar\((\d+)\)/', $column_info->Type, $matches);
        $current_size = isset($matches[1]) ? intval($matches[1]) : 0;
        
        if ($current_size > 191) {
            $wpdb->query("ALTER TABLE $table_name MODIFY email varchar(191) NOT NULL");
        }
    }
}
```

**What it does:**
- **Checks email column size:** Gets current column definition
- **If too large:** Updates to varchar(191)
- **Why:** Older versions used varchar(255), causes errors with utf8mb4

**Step-by-step:**
1. **Get column info:** `SHOW COLUMNS FROM table WHERE Field = 'email'`
2. **Extract size:** Use regex to get number from `varchar(255)`
3. **Check size:** If > 191, update column
4. **ALTER TABLE:** Modify column definition

**Why this exists:**
- **Backward compatibility:** Fixes old table structures
- **Automatic updates:** No manual intervention needed
- **Prevents errors:** Fixes "key too long" errors

---

## Inserting Users

### The `insert_user()` Method

**This method saves a new user to the database.**

**What it does:**
1. Ensures table exists
2. Sets default values
3. Hashes password
4. Serializes array data (interests)
5. Converts empty strings to NULL
6. Builds format array for prepared statement
7. Inserts into database
8. Returns user ID

### Step-by-Step Breakdown

#### Step 1: Safety Check

```php
if (!self::table_exists()) {
    $created = self::create_table();
    if (!$created) {
        error_log('DoRegister: Could not create table before insert');
        return false;
    }
}
```

**What happens:**
- **Checks table exists:** Before trying to insert
- **Creates if missing:** Safety net if table was deleted
- **Returns false:** If table creation failed

**Why this check?**
- **Edge cases:** Table might be deleted manually
- **Reliability:** Ensures table exists before insert
- **Error handling:** Prevents cryptic database errors

#### Step 2: Set Default Values

```php
$defaults = array(
    'full_name' => '',
    'email' => '',
    'password' => '',
    // ... etc
);

$data = wp_parse_args($data, $defaults);
```

**What `wp_parse_args()` does:**
- **Merges arrays:** Combines `$data` with `$defaults`
- **Fills missing keys:** If `$data` doesn't have 'city', uses `''` from defaults
- **Preserves provided values:** Doesn't overwrite existing values

**Example:**
```php
$data = array('email' => 'user@example.com');
$defaults = array('email' => '', 'full_name' => '');

$result = wp_parse_args($data, $defaults);
// Result: array('email' => 'user@example.com', 'full_name' => '')
```

**Why defaults?**
- **Prevents errors:** Missing keys won't cause issues
- **Consistent structure:** All fields always present
- **Easier to work with:** No need to check if key exists

#### Step 3: Hash Password

```php
if (!empty($data['password'])) {
    $data['password'] = wp_hash_password($data['password']);
}
```

**What `wp_hash_password()` does:**
- **One-way encryption:** Converts plain text to hash
- **Secure algorithms:** Uses bcrypt or argon2 (WordPress chooses best)
- **Cannot be reversed:** Hash cannot be converted back to password

**Example:**
```php
$plain = 'mypassword123';
$hash = wp_hash_password($plain);
// Result: '$2y$10$hashed_password_string...'
```

**Why hash passwords?**
- **Security:** Plain text passwords are dangerous
- **Best practice:** Never store passwords in plain text
- **WordPress standard:** Uses WordPress's secure functions

**Hash characteristics:**
- **Long:** 60+ characters
- **Unique:** Same password = different hash each time (salt)
- **One-way:** Cannot be reversed
- **Verifiable:** Can check if password matches hash

#### Step 4: Serialize Array Data

```php
if (is_array($data['interests'])) {
    $data['interests'] = serialize($data['interests']);
}
```

**What serialization does:**
- **Converts array to string:** Array cannot be stored directly in database
- **Preserves structure:** Can be converted back later

**Example:**
```php
$array = array('technology', 'sports');
$serialized = serialize($array);
// Result: "a:2:{i:0;s:10:"technology";i:1;s:6:"sports";}"
```

**Why serialize?**
- **Database limitation:** TEXT fields store strings, not arrays
- **Preserves data:** Can restore array structure later
- **WordPress standard:** WordPress uses serialization for complex data

**Later (when reading):**
```php
$user->interests = maybe_unserialize($user->interests);
// Converts back to array: array('technology', 'sports')
```

#### Step 5: Convert Empty Strings to NULL

```php
$insert_data = array(
    'full_name' => $data['full_name'],
    'city' => !empty($data['city']) ? $data['city'] : null,
    'gender' => !empty($data['gender']) ? $data['gender'] : null,
    // ... etc
);
```

**What this does:**
- **Required fields:** Keep as-is (even if empty string)
- **Optional fields:** Convert empty string to NULL

**NULL vs Empty String:**
- **NULL:** No value (database concept)
- **Empty string (`''`):** Empty value (still a value)

**Why use NULL?**
- **Cleaner database:** NULL = "no data", '' = "empty data"
- **Better queries:** `WHERE city IS NULL` vs `WHERE city = ''`
- **Database best practice:** NULL for optional fields

#### Step 6: Build Format Array

```php
$format = array();
foreach ($insert_data as $value) {
    $format[] = ($value === null) ? null : '%s';
}
```

**What format array does:**
- **Tells WordPress how to escape values:** Prevents SQL injection
- **`'%s'`:** String format (will be escaped)
- **`null`:** NULL value (no escaping needed)

**Example:**
```php
$insert_data = array('John', 'john@example.com', null);
$format = array('%s', '%s', null);
```

**Why format array?**
- **Security:** WordPress escapes values based on format
- **Prevents SQL injection:** Values are properly escaped
- **Required:** `$wpdb->insert()` needs format array

#### Step 7: Insert into Database

```php
$result = $wpdb->insert(
    $table_name,
    $insert_data,
    $format
);
```

**What `$wpdb->insert()` does:**
- **Safe insertion:** Uses prepared statements
- **Escapes values:** Based on format array
- **Returns:** Number of rows affected (1 on success, false on failure)

**Parameters:**
1. **Table name:** Where to insert
2. **Data array:** Column => value pairs
3. **Format array:** How to escape each value

**Generated SQL:**
```sql
INSERT INTO wp_doregister_users 
(full_name, email, password, ...) 
VALUES ('John', 'john@example.com', '$2y$10$hash...', ...)
```

**Why `$wpdb->insert()`?**
- ✅ **Security:** Prevents SQL injection
- ✅ **WordPress standard:** Recommended way to insert data
- ✅ **Error handling:** Returns false on failure
- ✅ **Simple:** Easier than writing raw SQL

#### Step 8: Return User ID

```php
if ($result === false) {
    error_log('DoRegister Insert Error: ' . $wpdb->last_error);
    return false;
}

return $wpdb->insert_id;
```

**What happens:**
- **If failed:** Log error, return false
- **If success:** Return user ID (`$wpdb->insert_id`)

**`$wpdb->insert_id`:**
- **Contains:** AUTO_INCREMENT value (user ID)
- **Available:** After successful insert
- **Used:** To identify newly created user

---

## Retrieving Users

### The `get_user_by_email()` Method

```php
public static function get_user_by_email($email) {
    global $wpdb;
    $table_name = self::get_table_name();
    
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $email
        )
    );
}
```

**What it does:**
- **Finds user by email:** Used for login authentication
- **Uses prepared statement:** Prevents SQL injection
- **Returns:** User object or null if not found

**How it works:**
1. **`$wpdb->prepare()`:** Escapes email value
2. **`%s`:** String placeholder (email is string)
3. **`$wpdb->get_row()`:** Returns single row as object
4. **`SELECT *`:** Gets all columns

**Prepared statement:**
```sql
-- Before prepare:
SELECT * FROM wp_doregister_users WHERE email = 'user@example.com'

-- After prepare (if email contains SQL):
SELECT * FROM wp_doregister_users WHERE email = 'user\'@example.com'
-- Escaped quote prevents SQL injection
```

**Why prepared statements?**
- **Security:** Prevents SQL injection attacks
- **WordPress standard:** Always use prepared statements
- **Automatic escaping:** WordPress handles it

### The `get_user_by_id()` Method

```php
public static function get_user_by_id($id) {
    global $wpdb;
    $table_name = self::get_table_name();
    
    $user = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        )
    );
    
    if ($user && $user->interests) {
        $user->interests = maybe_unserialize($user->interests);
    }
    
    return $user;
}
```

**What it does:**
- **Finds user by ID:** Used for profile page
- **Unserializes interests:** Converts string back to array
- **Returns:** User object with interests as array

**Key difference:**
- **`%d`:** Integer placeholder (ID is integer)
- **Unserialization:** Converts interests back to array

**`maybe_unserialize()`:**
- **Safely unserializes:** Handles both serialized and non-serialized data
- **WordPress function:** Standard way to unserialize
- **Returns:** Original data type (array, object, etc.)

### The `get_all_users()` Method

```php
public static function get_all_users($limit = 50, $offset = 0) {
    global $wpdb;
    $table_name = self::get_table_name();
    
    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        )
    );
    
    foreach ($users as $user) {
        if ($user->interests) {
            $user->interests = maybe_unserialize($user->interests);
        }
    }
    
    return $users;
}
```

**What it does:**
- **Gets multiple users:** Used for admin dashboard
- **Pagination:** LIMIT and OFFSET for pagination
- **Orders by date:** Newest first
- **Unserializes interests:** For each user

**Pagination explained:**
- **LIMIT 10:** Get maximum 10 records
- **OFFSET 20:** Skip first 20 records
- **Result:** Records 21-30

**Example:**
```php
get_all_users(10, 0);   // Records 1-10
get_all_users(10, 10);  // Records 11-20
get_all_users(10, 20);  // Records 21-30
```

**`$wpdb->get_results()`:**
- **Returns:** Array of objects (multiple rows)
- **Each object:** Represents one user
- **Properties:** Column names (e.g., `$user->email`)

---

## Password Security

### The `verify_password()` Method

```php
public static function verify_password($password, $hash) {
    return wp_check_password($password, $hash);
}
```

**What it does:**
- **Compares password with hash:** Used for login
- **Uses WordPress function:** `wp_check_password()`
- **Returns:** `true` if matches, `false` if not

**How password verification works:**
1. **User submits:** Plain text password (`'mypassword123'`)
2. **Database has:** Hashed password (`'$2y$10$hash...'`)
3. **`wp_check_password()`:** Hashes submitted password
4. **Compares:** Hashes match = password correct

**Why this works:**
- **Same algorithm:** Uses same hashing as `wp_hash_password()`
- **Secure:** Cannot reverse hash to get password
- **Automatic:** WordPress handles algorithm differences

**Security features:**
- **One-way:** Hash cannot be reversed
- **Salted:** Each hash includes unique salt
- **Slow:** Intentionally slow to prevent brute force
- **Future-proof:** WordPress updates algorithms automatically

---

## Pagination & Bulk Operations

### The `get_total_users()` Method

```php
public static function get_total_users() {
    global $wpdb;
    $table_name = self::get_table_name();
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}
```

**What it does:**
- **Counts all users:** Used for pagination
- **Returns:** Total number of users

**How pagination uses it:**
```php
$total_users = DoRegister_Database::get_total_users();
$per_page = 10;
$total_pages = ceil($total_users / $per_page);
// Example: 25 users ÷ 10 per page = 3 pages
```

**`$wpdb->get_var()`:**
- **Returns:** Single value (the count)
- **Useful for:** COUNT, MAX, MIN, SUM queries

### The `delete_users()` Method

```php
public static function delete_users($ids) {
    global $wpdb;
    $table_name = self::get_table_name();

    if (empty($ids) || !is_array($ids)) {
        return 0;
    }

    $ids = array_filter(array_map('intval', $ids));
    
    if (empty($ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $query = $wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids);
    
    $result = $wpdb->query($query);
    return $result;
}
```

**What it does:**
- **Deletes multiple users:** Bulk delete operation
- **Sanitizes IDs:** Converts to integers
- **Uses IN clause:** Deletes multiple records at once

**Step-by-step:**

**1. Validate input:**
```php
if (empty($ids) || !is_array($ids)) {
    return 0;
}
```

**2. Sanitize IDs:**
```php
$ids = array_filter(array_map('intval', $ids));
```
- **`array_map('intval', $ids)`:** Converts each ID to integer
- **`array_filter()`:** Removes falsy values (0, false, null)

**3. Build placeholders:**
```php
$placeholders = implode(',', array_fill(0, count($ids), '%d'));
```
- **`array_fill()`:** Creates array of `'%d'` placeholders
- **`implode()`:** Joins with commas: `'%d,%d,%d'`

**4. Prepare query:**
```php
$query = $wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids);
```

**Generated SQL:**
```sql
DELETE FROM wp_doregister_users WHERE id IN (1, 5, 10)
```

**5. Execute:**
```php
$result = $wpdb->query($query);
return $result; // Number of rows deleted
```

**Why this approach?**
- ✅ **Security:** IDs sanitized to integers
- ✅ **Efficient:** Single query deletes multiple records
- ✅ **Safe:** Prepared statement prevents SQL injection

### The `email_exists()` Method

```php
public static function email_exists($email) {
    global $wpdb;
    $table_name = self::get_table_name();
    
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s",
            $email
        )
    );
    
    return $count > 0;
}
```

**What it does:**
- **Checks if email exists:** Used for validation
- **Returns:** `true` if exists, `false` if not

**How it works:**
- **COUNT(*):** Counts matching rows
- **If count > 0:** Email exists
- **If count = 0:** Email doesn't exist

**Why COUNT instead of SELECT?**
- **Efficient:** Only returns count, not full record
- **Faster:** Less data transferred
- **Simple:** Just need yes/no answer

---

## Security Measures

### 1. Prepared Statements

**All queries use prepared statements:**
```php
$wpdb->prepare("SELECT * FROM table WHERE email = %s", $email);
```

**Why:**
- **Prevents SQL injection:** Values are escaped
- **WordPress standard:** Always use prepared statements
- **Automatic:** WordPress handles escaping

### 2. Input Sanitization

**IDs sanitized to integers:**
```php
$ids = array_map('intval', $ids);
```

**Why:**
- **Type safety:** Ensures integers only
- **Prevents errors:** Non-numeric values removed
- **Security:** Prevents malicious input

### 3. Password Hashing

**Passwords never stored plain text:**
```php
$data['password'] = wp_hash_password($data['password']);
```

**Why:**
- **Security:** Plain text passwords are dangerous
- **Best practice:** Always hash passwords
- **WordPress standard:** Uses secure algorithms

### 4. Table Prefix Support

**Uses WordPress prefix:**
```php
$table_name = $wpdb->prefix . 'doregister_users';
```

**Why:**
- **Security:** Hides table names
- **Multi-site:** Supports different prefixes
- **WordPress standard:** All plugins should use prefixes

### 5. Error Logging

**Errors logged for debugging:**
```php
error_log('DoRegister Insert Error: ' . $wpdb->last_error);
```

**Why:**
- **Debugging:** Helps identify issues
- **Security:** Doesn't expose errors to users
- **Maintenance:** Easier to troubleshoot

---

## Common WordPress Database Patterns

### Pattern 1: Global `$wpdb`

**Always access database object:**
```php
global $wpdb;
```

**Why:** Required to use WordPress database functions.

### Pattern 2: Table Prefix

**Always use prefix:**
```php
$table_name = $wpdb->prefix . 'table_name';
```

**Why:** WordPress standard, supports multi-site.

### Pattern 3: Prepared Statements

**Always use prepared statements:**
```php
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

**Why:** Prevents SQL injection, WordPress requirement.

### Pattern 4: `dbDelta()` for Tables

**Use `dbDelta()` for table creation:**
```php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
```

**Why:** WordPress recommended method, handles updates.

### Pattern 5: Charset & Collation

**Always use WordPress charset:**
```php
$charset_collate = $wpdb->get_charset_collate();
```

**Why:** Matches WordPress encoding, supports all characters.

### Pattern 6: Return Methods

**Different methods for different needs:**
- **`get_var()`:** Single value (COUNT, MAX, etc.)
- **`get_row()`:** Single row (object)
- **`get_results()`:** Multiple rows (array of objects)
- **`query()`:** Execute query (INSERT, UPDATE, DELETE)

### Pattern 7: Error Handling

**Check results and log errors:**
```php
if ($result === false) {
    error_log('Error: ' . $wpdb->last_error);
    return false;
}
```

**Why:** Prevents silent failures, helps debugging.

### Pattern 8: Data Serialization

**Serialize complex data:**
```php
$data['interests'] = serialize($array);
// Later:
$array = maybe_unserialize($data['interests']);
```

**Why:** Arrays/objects can't be stored directly in database.

---

## How This File Fits Into the Plugin

### Role in Overall Plugin

**This class is the "data layer":**

```
Plugin Structure:
├── DoRegister.php (Main orchestrator)
├── DoRegister_Database (Data layer) ← This file
├── DoRegister_Registration (Frontend form HTML)
├── DoRegister_Login (Frontend form HTML)
├── DoRegister_Profile (Frontend page HTML)
├── DoRegister_Ajax (Request handlers - uses Database)
├── DoRegister_Assets (CSS/JS loader)
└── DoRegister_Admin (Admin dashboard - uses Database)
```

**What it does:**
- **Stores data:** Saves user registrations
- **Retrieves data:** Gets user information
- **Used by:** AJAX handlers, Admin dashboard, Profile page

### Data Flow

**Registration flow:**
```
User submits form → DoRegister_Ajax::handle_registration()
  └─> DoRegister_Database::insert_user()
      ├─> Hash password
      ├─> Serialize interests
      └─> Insert into database
```

**Login flow:**
```
User submits login → DoRegister_Ajax::handle_login()
  └─> DoRegister_Database::get_user_by_email()
      └─> DoRegister_Database::verify_password()
```

**Profile flow:**
```
User views profile → DoRegister_Profile::render_profile_page()
  └─> DoRegister_Database::get_user_by_id()
      └─> Unserialize interests
      └─> Display user data
```

### Integration Points

**1. Used by AJAX handlers:**
```php
DoRegister_Database::insert_user($user_data);
DoRegister_Database::get_user_by_email($email);
DoRegister_Database::verify_password($password, $hash);
DoRegister_Database::email_exists($email);
```

**2. Used by Admin dashboard:**
```php
DoRegister_Database::get_all_users($per_page, $offset);
DoRegister_Database::get_total_users();
DoRegister_Database::delete_users($ids);
```

**3. Used by Profile page:**
```php
DoRegister_Database::get_user_by_id($user_id);
```

**4. Called on activation:**
```php
// In DoRegister.php
DoRegister_Database::create_table();
```

---

## Key Takeaways

### What You Should Remember

1. **`$wpdb`:** WordPress database object - use for all database operations
2. **Table prefixes:** Always use `$wpdb->prefix` for table names
3. **Prepared statements:** Always use `$wpdb->prepare()` to prevent SQL injection
4. **`dbDelta()`:** Use for table creation (WordPress recommended)
5. **Password hashing:** Always hash passwords with `wp_hash_password()`
6. **Serialization:** Serialize arrays/objects before storing in database
7. **NULL vs empty string:** Use NULL for optional fields
8. **Error handling:** Check results and log errors

### Why This Structure Works

- ✅ **Secure:** Multiple security layers (prepared statements, password hashing, input sanitization)
- ✅ **WordPress standards:** Uses WordPress database functions and patterns
- ✅ **Maintainable:** All database code in one place
- ✅ **Reusable:** Other classes can use database methods
- ✅ **Robust:** Handles errors gracefully, includes fallbacks

### Common Mistakes to Avoid

1. ❌ **Not using prepared statements** - SQL injection vulnerability
2. ❌ **Storing passwords plain text** - Major security risk
3. ❌ **Not using table prefix** - Breaks multi-site, security issue
4. ❌ **Not checking table exists** - Causes errors if table missing
5. ❌ **Not sanitizing input** - Security vulnerability
6. ❌ **Not handling errors** - Silent failures are hard to debug
7. ❌ **Not serializing arrays** - Data corruption or errors

---

## Summary

**`class-doregister-database.php` manages all database operations for the plugin:**

- **What it does:** Creates tables, saves users, retrieves data, verifies passwords, handles pagination
- **How it works:** Uses WordPress `$wpdb` object with prepared statements, password hashing, and data serialization
- **Security:** Multiple layers (prepared statements, password hashing, input sanitization, table prefixes)
- **WordPress integration:** Follows WordPress database patterns and uses WordPress functions
- **Maintainability:** Centralized database code, reusable methods, proper error handling

**In one sentence:** This file acts as the secure data layer for the plugin, handling all database operations using WordPress's database abstraction layer with proper security measures, following WordPress best practices for table creation, data storage, and retrieval.

---

*This explanation is designed for WordPress beginners. For more advanced topics, see the ARCHITECTURE.md and OOP_ANALYSIS.md files.*

