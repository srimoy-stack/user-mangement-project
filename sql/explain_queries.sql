-- ===========================================================
--  EXPLAIN QUERIES — REQUIRED BY ASSIGNMENT
--  These demonstrate indexing, filtering, sorting & pagination.
-- ===========================================================


-- ===========================================================
-- 1. EXPLAIN — User Listing (Search + Pagination + Sorting)
--    Matches the paginated user query used in UserRepository
-- ===========================================================

EXPLAIN SELECT 
    id, name, email, phone, city, created_at
FROM users
WHERE (name LIKE '%john%' OR email LIKE '%john%')
ORDER BY created_at DESC
LIMIT 10 OFFSET 0;


-- ===========================================================
-- 2. EXPLAIN — Product Listing (Search + Category Filter + Sorting)
--    Matches ProductRepository->paginate()
-- ===========================================================

EXPLAIN SELECT 
    id, title, description, price, category, created_at
FROM products
WHERE (title LIKE '%iphone%' OR description LIKE '%iphone%')
  AND category = 'electronics'
ORDER BY price ASC
LIMIT 10 OFFSET 0;


-- ===========================================================
-- 3. EXPLAIN — Fetch Single Product by ID
--    This demonstrates PRIMARY KEY lookup performance
-- ===========================================================

EXPLAIN SELECT 
    id, title, description, price, category, created_at
FROM products
WHERE id = 10;


-- ===========================================================
-- 4. EXPLAIN — Users COUNT Query (Pagination support)
-- ===========================================================

EXPLAIN SELECT 
    COUNT(*) 
FROM users
WHERE (name LIKE '%john%' OR email LIKE '%john%');


-- ===========================================================
-- 5. EXPLAIN — Products COUNT Query (Search + Category Filter)
-- ===========================================================

EXPLAIN SELECT 
    COUNT(*)
FROM products
WHERE (title LIKE '%iphone%' OR description LIKE '%iphone%')
  AND category = 'electronics';
