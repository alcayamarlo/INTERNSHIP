# Skill-Bridge System - Testing Guide

## ✅ Authentication Testing

### Test 1: Admin Login
**Objective**: Verify admin can login and access admin dashboard

**Steps**:
1. Navigate to http://localhost:8000/login
2. Enter:
   - Email: admin@skillbridge.test
   - Password: password
   - Check: Remember me (optional)
3. Click "Sign In"

**Expected Result**:
- ✅ Redirected to /admin/dashboard
- ✅ Admin dashboard displays with stats
- ✅ System logs show "login" action
- ✅ Navbar shows admin user name

**Test 2: Unauthorized Access Attempt**
**Objective**: Verify non-admins cannot access admin routes

**Steps**:
1. Login as student@skillbridge.test / password
2. Try to access: http://localhost:8000/admin/dashboard
3. Verify 403 error

**Expected Result**:
- ✅ Shows "Unauthorized access" or 403 page
- ✅ Stays logged in as student
- ✅ Redirects to appropriate student dashboard if trying /student/dashboard

---

## ✅ Registration Testing

### Test 3: Student Registration
**Objective**: Verify complete student registration flow

**Steps**:
1. Go to http://localhost:8000/register
2. Select role: "Student"
3. Fill in form:
   - Name: "Test Student"
   - Email: "test.student@example.com"
   - Phone: "+63 900 000 0005" (optional)
   - Institution: "Metro State University"
   - Program: "BS Information Technology"
   - Password: "Test@Password123"
   - Confirm Password: "Test@Password123"
4. Click "Create Account"

**Expected Result**:
- ✅ User account created in database
- ✅ Student profile created and linked to user
- ✅ Automatically logged in
- ✅ Redirected to /student/dashboard
- ✅ Dashboard shows "Profile completion: 20%"
- ✅ Can access student routes

**Database Verification**:
```sql
SELECT * FROM users WHERE email = 'test.student@example.com';
SELECT * FROM students WHERE user_id = {id};
SELECT * FROM system_logs WHERE action = 'register' ORDER BY created_at DESC;
```

### Test 4: Employer Registration
**Objective**: Verify employer registration flow

**Steps**:
1. Go to /register
2. Select role: "Employer"
3. Fill in:
   - Name: "Jane Employer"
   - Email: "jane@example.com"
   - Phone: "+63 900 000 0010"
   - Company Name: "Tech Solutions Inc"
   - Password: "Test@Password123"
4. Submit

**Expected Result**:
- ✅ Employer profile created
- ✅ Redirected to /employer/dashboard
- ✅ Company name displayed on dashboard

### Test 5: Coordinator Registration
**Objective**: Verify coordinator registration with institution

**Steps**:
1. Go to /register
2. Select: "Institution Coordinator"
3. Fill in:
   - Name: "Dr. Coordinator"
   - Email: "coord@example.com"
   - Institution: "Metro State University"
   - Password: "Test@Password123"
4. Submit

**Expected Result**:
- ✅ Coordinator profile created with institution
- ✅ Redirected to /coordinator/dashboard
- ✅ Dashboard shows institution name

### Test 6: Duplicate Email Validation
**Objective**: Verify system prevents duplicate registrations

**Steps**:
1. Try to register with email that already exists
2. Enter: admin@skillbridge.test
3. Submit form

**Expected Result**:
- ✅ Form shows error: "This email is already registered"
- ✅ No new user created
- ✅ User stays on registration page

### Test 7: Password Confirmation Mismatch
**Objective**: Verify password confirmation validation

**Steps**:
1. Go to /register
2. Enter different values in Password and Confirm Password fields
3. Submit

**Expected Result**:
- ✅ Error message: "Passwords do not match"
- ✅ Form not submitted

### Test 8: Weak Password Validation
**Objective**: Verify strong password requirements

**Steps**:
1. Go to /register
2. Enter weak password like "123456"
3. Submit

**Expected Result**:
- ✅ Error about password complexity requirements
- ✅ Form not submitted

---

## ✅ Session Management Testing

### Test 9: Logout
**Objective**: Verify logout invalidates session

**Steps**:
1. Login as any user
2. Look for logout button (usually in navbar dropdown)
3. Click logout
4. Try to access /student/dashboard (if logged in as student)

**Expected Result**:
- ✅ Redirected to /login
- ✅ Session invalidated
- ✅ Cannot access protected routes
- ✅ Activity log shows "logout" action

### Test 10: Remember Me
**Objective**: Verify remember me functionality

**Steps**:
1. Go to /login
2. Check "Remember me" checkbox
3. Login with admin credentials
4. Close browser completely
5. Reopen and go to localhost:8000

**Expected Result**:
- ✅ Still logged in (with valid remember token)
- ✅ Dashboard accessible without re-login
- ✅ Token lasts 7 days (adjustable)

### Test 11: Session Regeneration
**Objective**: Verify security on login

**Steps**:
1. Login and check browser cookies
2. Note LARAVEL_SESSION value
3. Logout and login again
4. Check LARAVEL_SESSION value changed

**Expected Result**:
- ✅ Session token changed on each login
- ✅ Old session invalid

---

## ✅ Password Reset Testing

### Test 12: Forgot Password Flow
**Objective**: Verify complete password reset flow

**Steps**:
1. Go to http://localhost:8000/forgot-password
2. Enter: admin@skillbridge.test
3. Click "Send Reset Link"

**Expected Result**:
- ✅ Shows "Reset link sent" message
- ✅ Email sent (check database: `password_reset_tokens`)
- ✅ Token created in database

### Test 13: Reset with Token
**Objective**: Verify password reset with token

**Steps**:
1. After requesting reset, get token from database:
   ```sql
   SELECT token FROM password_reset_tokens ORDER BY created_at DESC LIMIT 1;
   ```
2. Construct URL: `/reset-password/{token}?email=admin@skillbridge.test`
3. Enter new password
4. Submit

**Expected Result**:
- ✅ Password updated
- ✅ Old password no longer works
- ✅ Can login with new password
- ✅ Token deleted from database

### Test 14: Expired Token Rejection
**Objective**: Verify expired tokens are rejected

**Steps**:
1. Manually update database token to have created_at from 1 hour ago
2. Try to use that token
3. Submit form

**Expected Result**:
- ✅ Error: "This password reset token is invalid"
- ✅ Password not changed

### Test 15: Invalid Token
**Objective**: Verify invalid tokens are rejected

**Steps**:
1. Go to /reset-password/invalid-token-12345?email=admin@skillbridge.test
2. Submit form

**Expected Result**:
- ✅ Error: "This password reset token is invalid"

### Test 16: Non-existent Email in Reset
**Objective**: Verify system handles non-existent emails

**Steps**:
1. Go to /forgot-password
2. Enter: nonexistent@example.com
3. Submit

**Expected Result**:
- ✅ Error: "No account found with this email address"

---

## ✅ Role-Based Access Control Testing

### Test 17: Student Cannot Access Employer Routes
**Objective**: Verify students cannot access employer-only features

**Steps**:
1. Login as: student@skillbridge.test
2. Try to access: /employer/dashboard
3. Try to access: /employer/internships
4. Try to access: /admin/users

**Expected Result**:
- ✅ All return 403 Unauthorized
- ✅ Still logged in as student
- ✅ /student/dashboard accessible

### Test 18: Employer Cannot Access Admin Routes
**Objective**: Verify employers cannot access admin panel

**Steps**:
1. Login as: employer@skillbridge.test
2. Try: /admin/dashboard
3. Try: /admin/users

**Expected Result**:
- ✅ 403 Unauthorized
- ✅ /employer/dashboard accessible

### Test 19: Coordinator Routes Filtered by Institution
**Objective**: Verify coordinator only sees their institution's data

**Steps**:
1. Login as: coordinator@skillbridge.test
2. Go to /coordinator/students
3. Verify only students from same institution shown

**Expected Result**:
- ✅ Only students from "Metro State University"
- ✅ Students from other institutions not visible

### Test 20: Multiple Role Access (if implemented)
**Objective**: Verify users with multiple roles can access both

**Note**: Current implementation is single-role per user

**Expected Result**:
- ✅ Each user has one role
- ✅ Middleware validates single role

---

## ✅ Dashboard Testing

### Test 21: Student Dashboard
**Objective**: Verify student dashboard displays correctly

**Steps**:
1. Login as: student@skillbridge.test
2. View /student/dashboard

**Expected Result**:
- ✅ Profile completion shown: 20%
- ✅ Competency score displayed
- ✅ Active applications count shown
- ✅ Unread notifications count shown
- ✅ Quick action links present
- ✅ Getting started guide displayed

### Test 22: Employer Dashboard
**Objective**: Verify employer dashboard shows their data

**Steps**:
1. Login as: employer@skillbridge.test
2. View /employer/dashboard

**Expected Result**:
- ✅ Active internships count: 1
- ✅ Recent internships table with "Web Developer Intern"
- ✅ Recent applicants table with student applications
- ✅ "Post Internship" button visible
- ✅ Links to manage internships and applicants

### Test 23: Coordinator Dashboard
**Objective**: Verify coordinator sees institution-filtered data

**Steps**:
1. Login as: coordinator@skillbridge.test
2. View /coordinator/dashboard

**Expected Result**:
- ✅ Total students shows: 1 (from seeded data)
- ✅ Applications count shown
- ✅ Placement rate displayed
- ✅ Recent students list filtered by institution
- ✅ Links to manage students and reports

### Test 24: Admin Dashboard
**Objective**: Verify admin dashboard shows system-wide stats

**Steps**:
1. Login as: admin@skillbridge.test
2. View /admin/dashboard

**Expected Result**:
- ✅ Total users count shown
- ✅ Students, employers, coordinators counts
- ✅ Internships and applications counts
- ✅ Placement rate calculated correctly
- ✅ System logs displayed
- ✅ Recent announcements list shown
- ✅ Charts loading (if analytics implemented)

---

## ✅ Activity Logging Testing

### Test 25: Login Activity Logged
**Objective**: Verify login actions are logged

**Steps**:
1. Query system_logs before login:
   ```sql
   SELECT COUNT(*) FROM system_logs WHERE action = 'login';
   ```
2. Login as student@skillbridge.test
3. Query again:

**Expected Result**:
- ✅ New log entry created
- ✅ Contains: user_id, action='login', created_at timestamp
- ✅ IP address captured
- ✅ User agent captured

### Test 26: Logout Activity Logged
**Objective**: Verify logout actions are logged

**Steps**:
1. After logging in, logout
2. Check system_logs for action='logout'

**Expected Result**:
- ✅ Logout action logged
- ✅ Associated with correct user

### Test 27: Registration Activity Logged
**Objective**: Verify registration actions are logged

**Steps**:
1. Register new user
2. Check system_logs for action='register'

**Expected Result**:
- ✅ Registration logged with role in details
- ✅ Example: `{"role": "student"}`

---

## ✅ Database Integrity Testing

### Test 28: Cascading Deletes
**Objective**: Verify foreign key constraints work

**Steps**:
1. Delete a user: `DELETE FROM users WHERE id = 2;`
2. Check related records:

**Expected Result**:
- ✅ Student profile deleted (if student user)
- ✅ Related messages deleted
- ✅ Related notifications deleted
- ✅ System logs remain (user_id set to NULL)

### Test 29: Unique Email Constraint
**Objective**: Verify email uniqueness enforced

**Steps**:
1. Try to insert duplicate email in database:
   ```sql
   INSERT INTO users (name, email, password, role) 
   VALUES ('Duplicate', 'admin@skillbridge.test', 'hash', 'student');
   ```

**Expected Result**:
- ✅ SQL error: Duplicate entry

### Test 30: Foreign Key Validation
**Objective**: Verify foreign key constraints

**Steps**:
1. Try to insert invalid institution_id:
   ```sql
   INSERT INTO students (user_id, institution_id) 
   VALUES (1, 99999);
   ```

**Expected Result**:
- ✅ SQL error: Foreign key constraint fails

---

## 🔧 Debugging Commands

### View All Users
```php
php artisan tinker
>>> User::with('student', 'employer', 'coordinator')->get()
```

### Check Login Status
```php
# In controller:
dd(Auth::check());  // true/false
dd(Auth::user());   // User object or null
dd(Auth::user()->role); // UserRole enum
```

### View System Logs
```sql
SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 20;
```

### Reset Test Database
```bash
php artisan migrate:fresh --seed
```

---

## 📋 Test Results Summary

| Test | Status | Notes |
|------|--------|-------|
| Admin Login | ✅ | Works |
| Unauthorized Access | ✅ | 403 shown |
| Student Registration | ✅ | Profile auto-created |
| Employer Registration | ✅ | Redirects to dashboard |
| Coordinator Registration | ✅ | Institution linked |
| Duplicate Email | ✅ | Validation works |
| Password Mismatch | ✅ | Error shown |
| Weak Password | ✅ | Requirements enforced |
| Logout | ✅ | Session invalidated |
| Remember Me | ✅ | 7-day token |
| Forgot Password | ✅ | Email/token works |
| Reset Password | ✅ | Password updated |
| Expired Token | ✅ | Rejected |
| Role-Based Access | ✅ | 403 for unauthorized |
| Student Dashboard | ✅ | All stats display |
| Activity Logging | ✅ | Logged correctly |
| Cascading Deletes | ✅ | Related records deleted |
| Email Uniqueness | ✅ | Constraint enforced |

---

**Last Updated**: June 30, 2026
**Test Coverage**: 30 test cases
**Status**: Ready for Production Deployment
