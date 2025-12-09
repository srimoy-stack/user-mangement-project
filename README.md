
# PHP Backend Assignment — Admin Panel + Product Catalog REST API

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
│
├── public/
│   └── index.php
│
├── src/
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Response.php
│   │   ├── Validator.php
│   │   └── Auth.php
│   │
│   ├── Controllers/
│   │   ├── Web/
│   │   │   └── AdminController.php
│   │   └── Api/
│   │       └── ProductController.php
│   │
│   ├── Middleware/
│   │   └── JwtMiddleware.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   └── Product.php
│   │
│   └── Repositories/
│       ├── UserRepository.php
│       └── ProductRepository.php
│
├── migrations/
│   └── 001_create_tables.sql
│
├── postman/
│   └── php-assignment.postman_collection.json
│
├── sql/
│   └── explain_queries.sql
│
├── tools/
│   └── db_test.php
│
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

Login:
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

List Users:
GET /admin/users?page=1&limit=10&q=john&sort=name&dir=asc

Create User:
POST /admin/users
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "9876543210",
  "city": "Delhi"
}

Update User:
PUT /admin/users/{id}

Delete User:
DELETE /admin/users/{id}

User Fields:
id, name, email, phone, city, created_at

------------------------------------------------------------
8. Product API (JWT Protected)
------------------------------------------------------------

Login for JWT:
POST /api/v1/auth/login
{
  "email": "admin@example.com",
  "password": "Admin@123"
}

Response:
{
  "token": "<JWT-TOKEN>",
  "expires_in": 3600
}

Use token:
Authorization: Bearer <JWT>

List Products:
GET /api/v1/products?q=iphone&category=electronics&sort=price&dir=asc&page=1&limit=10

Create Product:
POST /api/v1/products
{
  "title": "iPhone 15",
  "description": "Latest model",
  "price": 79999,
  "category": "electronics"
}

Update Product:
PUT /api/v1/products/{id}

Delete Product:
DELETE /api/v1/products/{id}

Product Fields:
id, title, description, price, category, created_at

------------------------------------------------------------
9. Database Indexes (Required)
------------------------------------------------------------
users.email → UNIQUE
products.title → INDEX
products.category → INDEX
admins.email → UNIQUE

------------------------------------------------------------
10. EXPLAIN Queries (Required by Assignment)
------------------------------------------------------------

User Listing Query:
EXPLAIN SELECT id, name, email, phone, city, created_at
FROM users
WHERE (name LIKE '%john%' OR email LIKE '%john%')
ORDER BY created_at DESC
LIMIT 10 OFFSET 0;

Product Listing Query:
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
Included in:
postman/php-assignment.postman_collection.json

------------------------------------------------------------
12. Security Measures
------------------------------------------------------------
- password_hash/password_verify
- Prepared statements (no SQL injection)
- JWT authentication
- HttpOnly + SameSite session cookies
- Session fixation protection
- No SELECT *
- Input validation

------------------------------------------------------------
13. Testing Steps
------------------------------------------------------------
1. Import Postman collection
2. Run JWT login
3. Use token for Product API
4. Run Admin login
5. Test User CRUD
