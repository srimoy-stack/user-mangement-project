PHP Backend Assignment — Admin Panel + Product Catalog REST API

This project implements the backend system defined in the assignment, including:

- Admin authentication (Session login + JWT login)
- User Management (CRUD + Search + Pagination + Sorting)
- Product Catalog REST API (CRUD + Filters + Search + Pagination + Sorting)
- Secure authentication using password_hash/password_verify
- JWT authentication using Authorization Bearer Token
- Secure sessions (HttpOnly, SameSite, session fixation protection)
- Prepared SQL statements (No SELECT *)
- Database indexing + EXPLAIN query analysis
- Postman Collection for full API testing
- Clean architecture using Vanilla PHP, PDO, FastRoute

All assignment deliverables are included.

------------------------------------------------------------
1. Requirements
------------------------------------------------------------
- PHP >= 8.0
- MySQL >= 5.7
- Composer >= 2.0

------------------------------------------------------------
2. Installation
------------------------------------------------------------
git clone <your-repo-url>
cd php-assignmnet
composer install

Create a .env file:

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=assignment_db
DB_USERNAME=root
DB_PASSWORD=
JWT_SECRET=your_secret_key_here
JWT_TTL=3600
SESSION_COOKIE_SECURE=false

------------------------------------------------------------
3. Run Server
------------------------------------------------------------
php -S localhost:8080 -t public

Base URL:
http://localhost:8080

------------------------------------------------------------
4. Folder Structure
------------------------------------------------------------
php-assignmnet/
├── public/
│   └── index.php
├── src/
│   ├── Core/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   └── Repositories/
├── migrations/
├── postman/
├── sql/
├── tools/
└── README.md

------------------------------------------------------------
5. Database Setup
------------------------------------------------------------
CREATE DATABASE assignment_db;

mysql -u root assignment_db < migrations/001_create_tables.sql

Generate admin password hash:
php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);"

Insert admin:
INSERT INTO admins (name, email, password)
VALUES ('Super Admin', 'admin@example.com', '<HASH>');

Login Credentials:
email: admin@example.com
password: Admin@123

------------------------------------------------------------
6. Admin Authentication (Session)
------------------------------------------------------------
POST /admin/login

Request:
{
  "email": "admin@example.com",
  "password": "Admin@123"
}

Response:
{
  "message": "Login successful",
  "admin": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com"
  }
}

------------------------------------------------------------
7. User Management (Admin Protected)
------------------------------------------------------------
List:
GET /admin/users?page=1&limit=10&q=john&sort=name&dir=asc

Create:
POST /admin/users

Show:
GET /admin/users/{id}

Update:
PUT /admin/users/{id}

Delete:
DELETE /admin/users/{id}

------------------------------------------------------------
8. Product API (JWT Protected)
------------------------------------------------------------
Login:
POST /api/auth/login

Use token:
Authorization: Bearer <JWT>

List:
GET /api/products

Create:
POST /api/products

Show:
GET /api/products/{id}

Update:
PUT /api/products/{id}

Delete:
DELETE /api/products/{id}

------------------------------------------------------------
9. Database Indexes
------------------------------------------------------------
users.email → UNIQUE  
products.title → INDEX  
products.category → INDEX  
admins.email → UNIQUE  

------------------------------------------------------------
10. EXPLAIN Queries
------------------------------------------------------------
User Listing:
EXPLAIN SELECT id, name, email, phone, city, created_at
FROM users
WHERE (name LIKE '%john%' OR email LIKE '%john%')
ORDER BY created_at DESC
LIMIT 10 OFFSET 0;

Product Listing:
EXPLAIN SELECT id, title, description, price, category, created_at
FROM products
WHERE (title LIKE '%iphone%' OR description LIKE '%iphone%')
  AND category = 'electronics'
ORDER BY price ASC
LIMIT 10 OFFSET 0;

Fetch Product by ID:
EXPLAIN SELECT id, title, description, price, category, created_at
FROM products
WHERE id = 10;

------------------------------------------------------------
11. Postman Collection
------------------------------------------------------------
Included in postman/php-assignment.postman_collection.json

------------------------------------------------------------
12. Security Measures
------------------------------------------------------------
- password_hash/password_verify
- Prepared statements (secure)
- JWT authentication
- HttpOnly + SameSite cookies
- Strong session protection
- No SELECT *
- Input validation

------------------------------------------------------------
13. Testing Steps
------------------------------------------------------------
1. Run Admin login
2. Test User CRUD
3. Run JWT login
4. Use token for Product API
5. Verify Product CRUD