# Ase230-Final
# Event Ticketing System
Project Video: https://youtu.be/PEbM7H4Ifg4
A full-stack event management and ticketing platform built with PHP, MySQL, and Bootstrap. Allows users to browse events with advanced filtering, view event details with real-time ticket availability, and provides organizers with a complete dashboard to manage events and view sales.

---

## 🎯 Features

### 1. **Event Listings Page** (`Php_sri/events/events.php`)
- Browse all approved events with dynamic data from the database
- **Filter by:**
  - Category (validated against Categories table whitelist)
  - Location/City (validated against Locations table whitelist)
  - Date (validates yyyy-mm-dd format with regex)
- **Sort by:**
  - Event Date (default)
  - Event Title
- **Pagination:** 12 events per page with page navigation
- **Input Validation:** All filters sanitized and validated against whitelists
- **Security:** SQL injection prevention using prepared statements
- **Styling:** Global CSS + Event-specific CSS

### 2. **Event Details Page** (`Php_sri/events/event_details.php`)
- Display full event information including:
  - Event title, description, and date/time
  - Organizer name (from Users table)
  - Location details (address, city, state, postal code)
  - Category (with fallback to "Uncategorized" if not set)
  - **Real-time Ticket Availability:**
    - Total capacity (SUM from TicketTypes)
    - Tickets sold (COUNT from Tickets)
    - Remaining tickets available
    - Starting price (MIN from TicketTypes)
  - Breadcrumb navigation back to events
- **Input Validation:** Event ID validated as numeric using `ctype_digit()`
- **Approval Check:** Only shows approved events
- **Error Handling:** Try/catch with logging for database errors, 404 on invalid ID
- **Security:** All output escaped with `htmlspecialchars()`
- **Styling:** Global CSS + Event-specific CSS

### 3. **Organizer Dashboard** (`Php_sri/Organizer/organiser_dashboard.php`)
View and manage all events created by the organizer with:
- **Search:** Search events by title
- **Filter:** Filter by approval status (Pending, Approved, Rejected)
- **Sort:** Sort by Date, Title, or Status
- **Pagination:** 10 events per page
- **Ticket Stats:** Shows total capacity and tickets sold for each event
- **Responsive Table:** Mobile-friendly layout
- **Input Validation:** Whitelist checks for status and sort, LIKE query for search
- **Styling:** Global CSS + Organizer-specific CSS

#### 3a. **Create Event** (`Php_sri/Organizer/create_event.php`)
- Secure event creation form with:
  - **CSRF Protection:** Session-based token validation
  - **Form Fields:**
    - Title * (required)
    - Description (optional)
    - Category (optional, validated against whitelist)
    - Location * (required, validated against whitelist)
    - Date & Time * (required, validates datetime-local format)
    - Duration in minutes (optional, validates non-negative)
  - **Error Handling:** Try/catch around database operations
  - **Validation:** All inputs validated, old values preserved on error
  - **Redirect:** Successfully created events redirect to organiser_dashboard.php
  - **Styling:** Global CSS + Organizer-specific CSS

#### 3b. **Edit Event** (`Php_sri/Organizer/edit_event.php`)
- Update event details with:
  - **Ownership Check:** Verifies event belongs to organizer (OrganizerID match)
  - **Validation:** Whitelist checks for category and location
  - **Prepared Statements:** All database operations use parameterized queries
  - **Form Pre-fill:** Shows current event data
  - **Error Handling:** Authorization checks and database error handling
  - **Redirect:** Successfully updated events redirect to organiser_dashboard.php
  - **Styling:** Global CSS + Organizer-specific CSS

#### 3c. **Delete Event** (`Php_sri/Organizer/delete_event.php`)
- Secure event deletion with:
  - **ID Validation:** Numeric check using `ctype_digit()`
  - **Ownership Verification:** Only organizer can delete their event
  - **Prepared Statement:** Safe database deletion
  - **Redirect:** Redirects to organiser_dashboard.php with deleted=1 flag

#### 3d. **View Sales** (`Php_sri/Organizer/View_sales.php`)
- Display ticket sales for a specific event:
  - **Ticket Details:** Barcode, customer name, purchase price, order date
  - **Ownership Check:** Verifies organizer owns the event
  - **Authorization:** Prevents unauthorized access to other organizer's sales
  - **Responsive Table:** Shows all ticket sales for the event
  - **Styling:** Global CSS + Organizer-specific CSS

---

## 📁 Project Structure

```
sri/
├── Php_sri/
│   ├── lib/
│   │   └── db.php                 # Database connection (PDO)
│   ├── events/
│   │   ├── events.php             # Event listings with filters
│   │   └── event_details.php      # Event details & ticket info
│   └── Organizer/
│       ├── organiser_dashboard.php # Organizer's event management
│       ├── create_event.php        # Create new event
│       ├── edit_event.php          # Edit existing event
│       ├── delete_event.php        # Delete event
│       └── View_sales.php          # View ticket sales
├── Css_sri/
│   ├── global.css                 # Site-wide styles
│   ├── event.css                  # Event page specific styles
│   └── organisers.css             # Organizer pages styles
├── database/
│   ├── -- 1.sql                   # Core database schema (Users, Events, Locations, etc.)
│   ├── -- Populate Users.sql      # Sample user data
│   └── -- 2_Add_Categories.sql    # Categories table & mappings (NEW)
└── README.md                      # This file
```

---

## 🗄️ Database Schema

### Core Tables (-- 1.sql)
- **Users:** Organizers and customers
- **Events:** Event information
- **Locations:** Venue locations
- **TicketTypes:** Ticket categories with pricing
- **Tickets:** Individual tickets sold
- **Orders:** Customer orders

### New Migration (-- 2_Add_Categories.sql)
Added **Categories table** with the following changes:
- Created `Categories` table with `CategoryID` and `Name` fields
- Added `CategoryID` column to `Events` table as a foreign key
- Sample categories: Concerts, Conferences, Sports, Theater, etc.
- Sample event category mappings

**Why this was added:**
- Events can now be organized by category (Concerts, Conferences, Sports, Theater, etc.)
- Improved data normalization — categories are no longer hardcoded strings in Events table
- Enables filtering events by category on the listings page
- Better scalability — can manage categories separately without duplicating data
- Join performance — LEFT JOIN to Categories table allows optional category assignment

---

## 🔐 Security Measures

### Input Validation
- ✅ **Whitelist Validation:** Filters (category, location, sort) validated against database-derived whitelists
- ✅ **Type Validation:** `ctype_digit()` for numeric IDs
- ✅ **Format Validation:** Regex for dates (yyyy-mm-dd), datetime-local parsing
- ✅ **Length Checks:** Search queries capped at 255 characters

### SQL Injection Prevention
- ✅ **Prepared Statements:** All queries use `$pdo->prepare()` with `?` placeholders
- ✅ **Bound Parameters:** User input bound as parameters, never concatenated into SQL

### Output Security
- ✅ **HTML Escaping:** All output escaped with `htmlspecialchars()`
- ✅ **XSS Prevention:** Dynamic content properly escaped before rendering

### CSRF Protection
- ✅ **Session Tokens:** `create_event.php` and `edit_event.php` generate and validate CSRF tokens
- ✅ **Token Verification:** `hash_equals()` used for constant-time comparison

### Authorization
- ✅ **Organizer Ownership Check:** Edit, delete, and view sales verify `OrganizerID` matches organizer
- ✅ **Approval Status Check:** Event details only show approved events to public
- ✅ **Unauthorized Access Prevention:** Returns errors or 404 for unauthorized requests

### Error Handling
- ✅ **Try/Catch Blocks:** Database operations wrapped in exception handling
- ✅ **Error Logging:** Exceptions logged to PHP error log, never exposed to user
- ✅ **Graceful Degradation:** Fallbacks for missing data (e.g., "Uncategorized" for null category)

---

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL) or similar local development environment
- PHP 7.4+
- MySQL 5.7+

### 1. Clone the Repository
```bash
git clone <repository-url>
cd sri
```

### 2. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services

### 3. Create the Database
Open PowerShell and run:
```powershell
# Create database
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS myticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Import Database Schema and Sample Data
```powershell
# Import core schema
& "C:\xampp\mysql\bin\mysql.exe" -u root myticket < "database\-- 1.sql"

# Import sample users data
& "C:\xampp\mysql\bin\mysql.exe" -u root myticket < "database\-- Populate Users.sql"

# Import categories and mappings (NEW)
& "C:\xampp\mysql\bin\mysql.exe" -u root myticket < "database\-- 2_Add_Categories.sql"
```

### 5. Copy Project to XAMPP Web Root
```powershell
Copy-Item -Recurse -Force "." "C:\xampp\htdocs\sri"
```

### 6. Verify Database Connection
- Check `Php_sri/lib/db.php` has correct credentials:
  - Host: `localhost`
  - Database: `myticket`
  - User: `root`
  - Password: (empty by default)

### 7. Access the Application
Open your browser and navigate to:

**Event Listings:**
```
http://localhost/sri/Php_sri/events/events.php
```

**Event Details (example):**
```
http://localhost/sri/Php_sri/events/event_details.php?id=1
```

**Organizer Dashboard:**
```
http://localhost/sri/Php_sri/Organizer/organiser_dashboard.php
```

---

## 📊 Database Connection

**File:** `Php_sri/lib/db.php`

This file establishes a PDO (PHP Data Objects) connection to the MySQL database:
- **Host:** `localhost` (your local machine)
- **Database:** `myticket`
- **User:** `root` (default XAMPP user)
- **Password:** Empty (default XAMPP config)
- **Charset:** `utf8mb4` (full Unicode support)

**Error Handling:**
- Exceptions are thrown on connection failure
- Error messages logged instead of exposed to users

**Usage:**
All PHP files require this file:
```php
<?php
require "../lib/db.php";
// Now $pdo is available for queries
```

---

## 🎨 CSS Architecture

### `global.css`
Site-wide styles used by all pages:
- Body background, fonts, colors
- Bootstrap overrides
- Breadcrumb styling
- Button styles
- Card shadows
- Table improvements
- Badge styling
- Utility spacing

### `event.css`
Event-specific styles for listings and details pages:
- Event card titles and layout
- Event image placeholder box
- Filter form styling
- Event detail boxes
- Category badge colors
- Event description typography

### `organisers.css`
Organizer dashboard and management page styles:
- Page title styling
- Table styles and hover effects
- Action button sizing
- Form control styling
- Card radius and shadows
- Stats badges
- Page spacing

All CSS links use absolute paths `/Css_sri/` for consistent loading across all pages.

---

## 🔄 Key Features Implemented

| Feature | Location | Status |
|---------|----------|--------|
| Event Listings with Filters | `events.php` | ✅ Complete |
| Event Details & Availability | `event_details.php` | ✅ Complete |
| Organizer Dashboard | `organiser_dashboard.php` | ✅ Complete |
| Create Event | `create_event.php` | ✅ Complete |
| Edit Event | `edit_event.php` | ✅ Complete |
| Delete Event | `delete_event.php` | ✅ Complete |
| View Sales | `View_sales.php` | ✅ Complete |
| Category Support | `-- 2_Add_Categories.sql` | ✅ Complete |
| Input Validation | All files | ✅ Complete |
| SQL Injection Prevention | All files | ✅ Complete |
| CSRF Protection | create/edit forms | ✅ Complete |
| Error Handling | All files | ✅ Complete |

---

## 📝 TODO / Future Enhancements

- [ ] **Authentication System:** Replace hardcoded `$organizerID = 2` with session-based login
- [ ] **Admin Dashboard:** Approve/reject pending events
- [ ] **Payment Integration:** Add ticket purchase and payment processing
- [ ] **Email Notifications:** Send confirmation emails to buyers and organizers
- [ ] **Advanced Filtering:** Add more filter options (price range, event type, etc.)
- [ ] **User Ratings & Reviews:** Allow customers to rate and review events
- [ ] **Bulk Operations:** Allow organizers to bulk update/delete events
- [ ] **Analytics:** Dashboard showing sales trends, popular events, etc.
- [ ] **Mobile App:** React Native or Flutter mobile application
- [ ] **API:** RESTful API for third-party integrations

---

## 👥 Team Collaboration

### For New Team Members

When cloning this repo, follow the **Setup Instructions** above. Each developer will:
1. Import the database schema locally
2. Configure their own XAMPP setup
3. Run the SQL migrations to populate sample data

**Database Note:** The `database/` folder contains SQL files that define the schema and seed data. These should be committed to the repo so all team members have the same database structure.

**Hardcoded OrganizerID:** Currently, organizer pages use `$organizerID = 2` for testing. This will be replaced with session-based authentication once the login system is implemented.

---

## 🐛 Troubleshooting

### CSS Not Loading
- Verify files are at `C:\xampp\htdocs\sri\Css_sri\`
- Check browser console for 404 errors
- Ensure XAMPP is serving `/Css_sri/` path correctly

### Database Connection Error
- Confirm MySQL is running in XAMPP
- Check credentials in `Php_sri/lib/db.php`
- Verify `myticket` database exists: `show databases;` in phpMyAdmin

### Forms Not Submitting
- Check PHP error log: `C:\xampp\apache\logs\error.log`
- Verify CSRF token is present in form (view page source)
- Check that required fields are filled

### Session Errors
- Ensure `session_start()` is called before any output (first line in PHP files)
- Verify PHP sessions directory is writable
- Check PHP error log for session-related errors

---

## 📚 Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **ORM/Database Access:** PDO (PHP Data Objects)
- **Frontend:** HTML5, CSS3, Bootstrap 5
- **Server:** Apache (via XAMPP)
- **Version Control:** Git

---

## 📄 License

This project is proprietary and intended for internal use only.

---

## ✍️ Author

Developed as part of the Event Ticketing System project.
