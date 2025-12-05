# Library Manager – WordPress Plugin (Custom Table + REST API + React Admin)

A fully functional WordPress plugin that manages a library of books using:
- A custom database table
- Secure and validated REST API endpoints
- Modern React-based single-page admin interface


**Author:** Akash Kumar Bhowmik  
**Version:** 1.0.0  

---

## Features

- Custom table `wp_library_books` created on activation using `dbDelta()`
- Full CRUD REST API under `/wp-json/library/v1`
- Secure: nonce verification, capability checks (`edit_posts`), input sanitization/validation
- React SPA admin dashboard (no external CDN dependencies)
- Production-ready React build bundled with the plugin
- Clean, modular, and WordPress coding standards compliant PHP code
- Bonus features implemented:
  - Pagination & filtering in GET /books
  - WP-CLI command: `wp library import sample.json`

---

## Installation Steps

1. **Download or clone the repository**

   git clone https://github.com/akashbhowmik54/library-manager.git

Option A – Install via ZIP
Zip the library-manager folder
In WordPress Admin → Plugins → Add New → Upload Plugin → Choose the ZIP → Install & Activate

Option B – Manual install
Copy the library-manager folder into /wp-content/plugins/
Activate the plugin from the WordPress Plugins page

Upon activation, the plugin automatically creates the wp_library_books table.
Go to 'Library Manager' in the WordPress admin menu to access the React admin app.


How to Build the React App (Development)
The React source code is located in admin/build/.
Bashcd library-manager/admin/build

# Install dependencies
npm install

# Start development server (hot reload)
npm start

# Build for production (outputs to admin/build/assets)
npm run build
The production build is automatically enqueued by the plugin. After rebuilding, simply refresh the Dashboard page.
No external CDN scripts are used – fully compliant with assignment requirements.

REST API Documentation
Base URL: /wp-json/library/v1

status – available | borrowed | unavailable
author – string filter
year – publication year (integer)
page – pagination page (default: 1)
per_page – items per page (default: 20)

# Example Responses

JSON[
  {
    "id": 5,
    "title": "1984",
    "author": "George Orwell",
    "publication_year": 1949,
    "status": "available",
    "description": "Dystopian social science fiction...",
    "created_at": "2025-12-05T10:00:00",
    "updated_at": "2025-12-05T12:30:00"
  }
]
All endpoints return proper HTTP status codes (200, 201, 204, 400, 403, 404, 500) and structured JSON.

Database Table Schema

Table name: wp_library_books (or with custom prefix)
All queries use $wpdb->prepare() and prepared statements.

# Bonus Features Implemented

Filtering on book list
Search bar in React admin
Status color coding
WP-CLI command:Bashwp library import sample.json