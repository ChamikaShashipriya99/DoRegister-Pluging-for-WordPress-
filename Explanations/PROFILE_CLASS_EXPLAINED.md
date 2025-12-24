## class-doregister-profile.php – Profile Page Handler Explained

**Goal of this file:** Render a logged-in user’s profile page on the frontend via a shortcode, using data stored in the custom database table and a PHP session-based login system.

---

## 1. What This File Does (In Simple Terms)

- Provides a **shortcode** `[doregister_profile]` that you can place on a page to show the **current user’s profile**.
- Checks whether the user is **logged in** using PHP sessions.
- If logged in:
  - Looks up the user in the custom table via `DoRegister_Database`.
  - Outputs a nicely formatted, read-only profile page with their data.
- If not logged in:
  - Shows a message and a **link to the login page**.

Think of this class as the plugin’s **“profile page template”** that is only available to authenticated users.

---

## 2. How It Fits Into the Overall Plugin

Within the main plugin boot class (`DoRegister` in `DoRegister.php`), you’ll find:

- `DoRegister_Profile::get_instance();`

That:
- Creates a single instance of `DoRegister_Profile` (Singleton pattern).
- Its constructor registers the `[doregister_profile]` shortcode.

High-level plugin roles:

- `DoRegister_Ajax` – Handles registration/login/logout and sets `$_SESSION['doregister_user_id']`.
- `DoRegister_Database` – Stores and retrieves user data.
- **`DoRegister_Profile` – Uses the session + database to render the profile for the current user.**

So the profile class is the **read-only display layer** for the user’s data after they have registered and logged in.

---

## 3. Singleton Pattern & Shortcode Registration

### 3.1 Singleton Pattern

The class uses the same Singleton pattern as the other main services:

- `private static $instance = null;`
- `public static function get_instance() { ... }`
- `private function __construct() { ... }`

**Why Singleton here?**

- Ensure the `[doregister_profile]` shortcode is **registered only once**.
- Provide a single “service object” to manage profile rendering.
- Keep architecture consistent across plugin components.

### 3.2 Registering the Shortcode

Inside the private constructor:

- `add_shortcode( 'doregister_profile', array( $this, 'render_profile_page' ) );`

**What this does:**

- Registers a new shortcode `[doregister_profile]`.
- Tells WordPress: “When you see this shortcode in content, call `$this->render_profile_page()` and replace the shortcode with whatever HTML that method returns.”

**Shortcode usage:**

- Editor adds `[doregister_profile]` to a page (e.g. `/profile`).
- When the page is rendered, WordPress calls `render_profile_page()`.
- That method returns HTML, and the shortcode is replaced by the profile UI.

---

## 4. Session-Based Authentication Check

### 4.1 Ensuring the Session Is Started

At the start of `render_profile_page()`:

- Checks `session_id()`, and if there’s no active session, calls `session_start()`.

**Why this matters:**

- The plugin uses **raw PHP sessions** (not `wp_signon` / `wp_users`) for its custom auth.
- The current user’s ID is stored in `$_SESSION['doregister_user_id']` during registration/login (in `DoRegister_Ajax`).
- You **must** start the session before accessing `$_SESSION`, or it will always be empty.

### 4.2 Verifying the User Is Logged In

The authentication check:

- Looks for `$_SESSION['doregister_user_id']`.
- If it’s not set:
  - It assumes the user is **not logged in**.
  - It builds a login URL with `home_url('/login')`.
  - Returns an error message with a **login link**, escaping the URL via `esc_url()` (XSS protection).

**Resulting behavior:**

- If an unauthenticated user opens the profile page, they see:
  - “Please login to view your profile.” with a link to the login page.
- This is how the plugin protects the profile page from anonymous access.

---

## 5. Loading User Data from the Database

If the session contains a user ID:

1. Grabs the ID from the session, passes it through `intval()` (simple sanitization, ensures it’s an integer).
2. Calls `DoRegister_Database::get_user_by_id( $user_id )`.
3. If no user is found:
   - Returns a “User not found.” message (covers cases where the user was deleted but the session still exists).
4. If a user object is found:
   - Uses it to build the profile view.

**Why use the database class:**

- Keeps the **data access logic** in a single place.
- This class does not know about SQL; it just asks for a user by ID.
- `get_user_by_id()` already takes care of:
  - Prepared statements.
  - Unserializing interests.

---

## 6. Rendering the Profile Page (Output Buffering)

### 6.1 Output Buffering Pattern

The method uses:

- `ob_start();` before outputting HTML.
- `return ob_get_clean();` at the end.

**Why:**

- Shortcode callbacks must **return** a string, not echo directly.
- Output buffering allows you to write normal PHP/HTML templates and then capture them as a string for WordPress to insert into the content.

### 6.2 Layout Overview

The HTML structure:

- Wrapper: `.doregister-profile-wrapper`
- Header: `.doregister-profile-header` with:
  - Profile photo or placeholder.
  - Name.
  - Email.
- Content sections: `.doregister-profile-content` containing multiple `.doregister-profile-category` blocks:
  1. **Basic Information** – name & email.
  2. **Contact Details** – phone, country, optional city.
  3. **Personal Details** – optional gender, date of birth, interests (as badges).
  4. **Profile Media** – main profile photo (again, larger context).
  5. **Account Information** – “Member Since” date (created_at).
- Actions: `.doregister-profile-actions` with a **Logout** button.

All these CSS classes are styled in `doregister.css` to create a clean, card-like, mobile-friendly layout.

---

## 7. Escaping & Formatting Output (Security & UX)

Throughout the HTML, all user data is carefully escaped:

- `esc_html( $user->full_name )`, `esc_html( $user->email )`, etc.
  - Prevents XSS by stripping dangerous HTML from user-supplied data.
- `esc_url( $user->profile_photo )` for image sources.
- `ucfirst()` on `gender` and interests before escaping:
  - e.g. `male` → `Male`, `reading` → `Reading`.
- `date()` + `strtotime()` to convert database dates into friendly formats:
  - For DOB: `F j, Y` → “January 1, 2024”.
  - For “Member Since”: also formatted nicely.

**Why this matters:**

- This class is **echoing data that ultimately comes from user input** (through the registration form).
- Escaping ensures that even if someone tried to inject HTML/JS into their name, email, city, etc., it will be displayed as plain text, not executed.

---

## 8. Conditional Display of Optional Fields

Several fields are optional and are only shown if they exist:

- City:
  - `if ( $user->city ) { ... }`
- Gender:
  - `if ( $user->gender ) { ... }`
- Date of birth:
  - `if ( $user->date_of_birth ) { ... }`
- Interests:
  - `if ( $user->interests && is_array( $user->interests ) ) { ... }`
    - Iterates over the interests array and displays each as a small badge.
- Profile photo:
  - If missing in the header:
    - Shows an SVG “user” icon as a placeholder.
  - In the “Profile Media” section:
    - Shows “No photo uploaded” text if absent.

**Why:**

- Keeps the UI clean: no empty labels or empty values.
- Reflects the fact that some registration fields were optional.

---

## 9. Logout Button (Front-End Action Hook Point)

At the bottom:

- A button:
  - `<button type="button" class="doregister-btn doregister-btn-logout">Logout</button>`

**Important details:**

- `type="button"`:
  - Does not submit a form by default.
  - Purely a click target for JavaScript.
- Class `doregister-btn-logout`:
  - JavaScript uses this class to attach a click handler that:
    - Sends an AJAX request to the logout handler (`DoRegister_Ajax::handle_logout()`).
    - Clears the session server-side.
    - Redirects the user back to the login page.

Again, this class only handles **markup**; the actual logout logic is elsewhere, maintaining separation of concerns.

---

## 10. WordPress & Plugin Patterns Demonstrated

- **Singleton service class**:
  - Centralizes profile logic in a single instance.
- **Shortcode registration (`add_shortcode`)**:
  - Provides a flexible way to drop the profile page into any content.
- **Session-based auth integration**:
  - Relies on `$_SESSION` plus custom credentials instead of WordPress core user system.
  - Clearly separates “auth state” (session) from “display” (profile).
- **Output buffering for template rendering**:
  - Common pattern in shortcode/template methods.
- **Escaping output (`esc_html`, `esc_url`)**:
  - Standard WordPress security practice for printing user data.
- **Graceful failure**:
  - If not logged in → friendly message and login link.
  - If user ID invalid → “User not found.” message.
- **Conditional rendering of optional fields**:
  - Ensures the UI reflects only stored data.

---

## 11. Big-Picture Summary

- **What this file does:** Defines a `[doregister_profile]` shortcode that shows a logged-in user’s profile using data from the custom table and a PHP session.
- **How it fits into the plugin:** It is the **profile view layer**, relying on the AJAX and Database layers for authentication and data, and on the Assets layer for styling and logout behavior.
- **Why WordPress functions are used:**
  - `add_shortcode` to integrate into content.
  - `home_url` + `esc_url` to create safe login links.
  - `esc_html` / `esc_url` to safely print user data.
- **Common patterns:** Singleton, shortcode-based views, session checks, output buffering, conditional rendering, and strict output escaping for security.


