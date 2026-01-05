# Frontend Profile Edit - Implementation Parts

This document breaks down the profile editing feature into manageable, sequential parts that can be implemented one at a time.

---

## 📦 Part 1: Backend Foundation - Database Update Method

### Files to Modify:
- `includes/class-doregister-database.php`

### What to Add:
- New method: `update_user($user_id, $data)`
  - Updates user data in database
  - Handles password hashing (if password provided)
  - Handles interests serialization
  - Updates `updated_at` timestamp
  - Returns true/false

### Testing:
- Test updating individual fields
- Test updating multiple fields
- Test password update
- Test with invalid user ID

**Estimated Time**: 30-45 minutes

---

## 📦 Part 2: Backend Foundation - AJAX Handler

### Files to Modify:
- `includes/class-doregister-ajax.php`

### What to Add:
- New method: `handle_profile_update()`
- Register AJAX actions in constructor
- Nonce verification
- User authentication check
- Input sanitization
- Field validation
- Call database update method
- Return JSON response

### Testing:
- Test AJAX endpoint
- Test validation errors
- Test successful update
- Test security (nonce, user verification)

**Estimated Time**: 1-2 hours

---

## 📦 Part 3: Frontend HTML - Edit Button & Mode Toggle

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- "Edit Profile" button in view mode
- Basic mode toggle (view/edit states)
- CSS classes for mode switching
- JavaScript to toggle between modes

### Testing:
- Button appears in view mode
- Clicking button switches to edit mode
- Can cancel back to view mode

**Estimated Time**: 30-45 minutes

---

## 📦 Part 4: Frontend HTML - Basic Information Fields

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- Convert Full Name from `<span>` to `<input type="text">`
- Convert Email from `<span>` to `<input type="email">`
- Add form wrapper
- Add nonce field
- Show/hide based on edit mode

### Testing:
- Fields appear in edit mode
- Fields are hidden in view mode
- Fields are pre-filled with current data

**Estimated Time**: 30 minutes

---

## 📦 Part 5: Frontend HTML - Contact Details Fields

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- Convert Phone Number to `<input type="tel">`
- Convert Country to searchable dropdown (reuse registration component)
- Convert City to `<input type="text">`
- Pre-fill with current data

### Testing:
- All fields editable
- Country dropdown works
- Data pre-fills correctly

**Estimated Time**: 45 minutes

---

## 📦 Part 6: Frontend HTML - Personal Details Fields

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- Convert Gender to radio buttons
- Convert Date of Birth to `<input type="date">`
- Convert Interests to checkboxes
- Handle optional fields (show/hide based on data)

### Testing:
- Radio buttons work
- Date picker works
- Checkboxes work
- Optional fields handle null/empty correctly

**Estimated Time**: 45 minutes

---

## 📦 Part 7: Frontend HTML - Profile Photo Update

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- File input for photo upload
- Preview current photo
- Preview new photo before save
- "Remove Photo" option (optional)
- Reuse photo upload component from registration

### Testing:
- Photo upload works
- Preview shows correctly
- Can remove photo

**Estimated Time**: 1 hour

---

## 📦 Part 8: Frontend HTML - Password Change (Optional)

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- "Change Password" checkbox/toggle
- Password input (hidden by default)
- Confirm Password input
- Show/hide based on toggle
- Only required if user wants to change password

### Testing:
- Toggle shows/hides password fields
- Fields only required when toggled on
- Validation works

**Estimated Time**: 30-45 minutes

---

## 📦 Part 9: Frontend HTML - Save & Cancel Buttons

### Files to Modify:
- `includes/class-doregister-profile.php`

### What to Add:
- "Save Changes" button
- "Cancel" button
- Button container/styling
- Form wrapper for submission

### Testing:
- Buttons appear in edit mode
- Cancel returns to view mode
- Save button triggers form submission

**Estimated Time**: 15-30 minutes

---

## 📦 Part 10: JavaScript - Mode Toggle & Form Population

### Files to Modify:
- `assets/js/doregister.js`

### What to Add:
- `initProfileEdit()` function
- `toggleEditMode()` function
- `populateEditForm()` function
- Event handlers for Edit/Cancel buttons
- Show/hide view/edit sections

### Testing:
- Clicking Edit switches to edit mode
- Form fields are populated with current data
- Clicking Cancel returns to view mode
- Data persists when canceling

**Estimated Time**: 1 hour

---

## 📦 Part 11: JavaScript - Form Validation

### Files to Modify:
- `assets/js/doregister.js`

### What to Add:
- `validateProfileField()` function
- Reuse validation logic from registration
- Real-time validation on blur
- Field-specific validation rules
- Error message display

### Testing:
- Validation works for all fields
- Error messages appear correctly
- Errors clear when fixed
- Required fields validated

**Estimated Time**: 1-2 hours

---

## 📦 Part 12: JavaScript - Form Submission

### Files to Modify:
- `assets/js/doregister.js`

### What to Add:
- `submitProfileUpdate()` function
- Collect all form data
- Validate all fields before submission
- Send AJAX request
- Handle success response
- Handle error response
- Refresh profile data on success

### Testing:
- Form submits correctly
- Success message displays
- Profile refreshes after save
- Error messages display correctly
- Invalid data is rejected

**Estimated Time**: 1-2 hours

---

## 📦 Part 13: JavaScript - Photo Upload Handler

### Files to Modify:
- `assets/js/doregister.js`

### What to Add:
- Reuse `handlePhotoUpload()` from registration
- Handle photo preview
- Update photo in form data
- Handle photo removal (optional)

### Testing:
- Photo upload works
- Preview shows correctly
- Photo updates on save

**Estimated Time**: 30-45 minutes

---

## 📦 Part 14: CSS - Edit Mode Styling

### Files to Modify:
- `assets/css/doregister.css`

### What to Add:
- `.doregister-profile-edit-mode` styles
- `.doregister-profile-view-mode` styles
- Edit button styling
- Save/Cancel button styling
- Form field styling (reuse registration styles)
- Smooth transitions between modes

### Testing:
- Edit mode looks good
- View mode unchanged
- Buttons styled correctly
- Form fields match registration form style

**Estimated Time**: 1-2 hours

---

## 📦 Part 15: CSS - Form Fields Styling

### Files to Modify:
- `assets/css/doregister.css`

### What to Add:
- Input field styles in profile context
- Checkbox/radio styles
- Date picker styles
- Photo upload area styles
- Error message styles
- Responsive styles for mobile

### Testing:
- All form elements styled correctly
- Mobile responsive
- Matches registration form style

**Estimated Time**: 1 hour

---

## 📦 Part 16: Assets - Nonce Localization

### Files to Modify:
- `includes/class-doregister-assets.php`

### What to Add:
- Add profile update nonce to `wp_localize_script()`
- Make nonce available to JavaScript

### Testing:
- Nonce available in JavaScript
- AJAX requests include nonce

**Estimated Time**: 15 minutes

---

## 📦 Part 17: Integration & Testing

### What to Do:
- Test complete flow (edit → save → view)
- Test all field updates
- Test validation
- Test error handling
- Test security
- Test mobile responsiveness
- Fix any bugs

### Testing Checklist:
- [ ] Edit mode toggle works
- [ ] All fields editable
- [ ] Form pre-fills correctly
- [ ] Validation works (frontend)
- [ ] Validation works (backend)
- [ ] Save updates database
- [ ] Success message displays
- [ ] Profile refreshes after save
- [ ] Cancel works correctly
- [ ] Password change works
- [ ] Photo upload works
- [ ] Email uniqueness works
- [ ] User can only edit own profile
- [ ] CSRF protection works
- [ ] Mobile responsive

**Estimated Time**: 2-3 hours

---

## 🎯 Recommended Implementation Order

### Phase 1: Backend (Parts 1-2)
**Goal**: Get backend working first
- Part 1: Database update method
- Part 2: AJAX handler

### Phase 2: Basic Edit Mode (Parts 3-4)
**Goal**: Basic edit functionality
- Part 3: Edit button & mode toggle
- Part 4: Basic information fields

### Phase 3: Complete Form (Parts 5-9)
**Goal**: All fields editable
- Part 5: Contact details fields
- Part 6: Personal details fields
- Part 7: Profile photo update
- Part 8: Password change
- Part 9: Save & Cancel buttons

### Phase 4: JavaScript (Parts 10-13)
**Goal**: Make it functional
- Part 10: Mode toggle & form population
- Part 11: Form validation
- Part 12: Form submission
- Part 13: Photo upload handler

### Phase 5: Styling (Parts 14-15)
**Goal**: Make it look good
- Part 14: Edit mode styling
- Part 15: Form fields styling

### Phase 6: Polish (Parts 16-17)
**Goal**: Final touches
- Part 16: Nonce localization
- Part 17: Integration & testing

---

## ⏱️ Total Estimated Time

- **Backend**: 2-3 hours
- **Frontend HTML**: 3-4 hours
- **JavaScript**: 3-4 hours
- **CSS**: 2-3 hours
- **Testing**: 2-3 hours

**Total**: ~12-17 hours

---

## 🚀 Quick Start (Minimal Viable Product)

If you want a basic working version first:

1. **Part 1**: Database update method
2. **Part 2**: AJAX handler
3. **Part 3**: Edit button
4. **Part 4**: Basic fields (name, email)
5. **Part 9**: Save/Cancel buttons
6. **Part 10**: Mode toggle
7. **Part 12**: Form submission
8. **Part 14**: Basic styling

This gives you a working edit feature for basic fields, then you can add more fields incrementally.

---

## 📝 Notes

- Each part can be implemented and tested independently
- Parts build on each other, so follow the order
- Reuse code from registration form where possible
- Test each part before moving to the next
- Keep security in mind throughout

