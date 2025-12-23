# DoRegister - Advanced User Registration System

A comprehensive WordPress plugin providing a multi-step user registration system with custom authentication, frontend login, and user profile management.

## Features

### Multi-Step Registration Form (5 Steps)
- **Step 1: Basic Information** - Full Name, Email, Password, Confirm Password
- **Step 2: Contact Details** - Phone Number, Country (searchable dropdown), City
- **Step 3: Personal Details** - Gender, Date of Birth, Interests (checkboxes)
- **Step 4: Profile Media** - Profile Photo Upload (AJAX)
- **Step 5: Review & Confirm** - Summary of all entered data

### Key Features
- ✅ Custom step/tab structure with progress bar
- ✅ Real-time jQuery-based validation
- ✅ Auto-save to localStorage (restores on page refresh)
- ✅ AJAX-powered form submission
- ✅ Custom database table (not using wp_users)
- ✅ Custom login system (frontend-only)
- ✅ Frontend user profile page
- ✅ Admin dashboard showing all registrations
- ✅ Navigation links between login and registration forms
- ✅ Password strength meter
- ✅ Image preview before upload
- ✅ Country searchable dropdown

## Installation

1. Upload the `DoRegister` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create a custom database table on activation

## Usage

### Shortcodes

Use these shortcodes in your WordPress pages:

- `[doregister_form]` - Display the registration form
- `[doregister_login]` - Display the login form
- `[doregister_profile]` - Display the user profile page (requires login)

### Page Setup

Create three pages in WordPress:
1. **Registration Page** - Add shortcode `[doregister_form]`
2. **Login Page** - Add shortcode `[doregister_login]`
3. **Profile Page** - Add shortcode `[doregister_profile]`

### Admin Access

After activation, you'll find "DoRegister" in the WordPress admin menu. Click it to view all user registrations.

## Database

The plugin creates a custom table `wp_doregister_users` with the following structure:
- User ID
- Full Name
- Email (unique)
- Password (hashed)
- Phone Number
- Country
- City
- Gender
- Date of Birth
- Interests (serialized)
- Profile Photo URL
- Created/Updated timestamps

**Note:** WordPress admin users are NOT stored in this table. This is a completely separate authentication system.

## Technical Details

### Architecture
- Full OOP structure
- Separate classes for each component
- WordPress coding standards compliant
- Proper nonce verification
- Sanitization and escaping

### JavaScript
- jQuery-based (no frameworks)
- Modular, reusable functions
- Event delegation
- localStorage integration

### Security
- Nonces for all AJAX actions
- Input sanitization
- Output escaping
- Password hashing (WordPress native)
- File upload validation

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- jQuery (included with WordPress)

---

Made By Chamika Shashipriya Under DoAcadamy Module 2 Assignment 2 of Full-Stack Web Developer Industrial Training Program