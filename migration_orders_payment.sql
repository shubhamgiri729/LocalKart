-- Run this once against your existing database. Only the orders table is
-- touched — no data is dropped.

-- Track how an order was paid, and Razorpay's own IDs for that payment
-- (useful for support/refunds — you can look a payment up in the Razorpay
-- dashboard directly from these).
ALTER TABLE orders
    ADD COLUMN payment_method ENUM('razorpay', 'cod') NOT NULL DEFAULT 'cod' AFTER total,
    ADD COLUMN razorpay_order_id VARCHAR(64) NULL AFTER payment_method,
    ADD COLUMN razorpay_payment_id VARCHAR(64) NULL AFTER razorpay_order_id;

-- Pre-existing bug fix, unrelated to Razorpay but in the same table: the
-- status enum was missing 'dispatched', which status.php actually sets.
-- Skip this line if you already ran migration_helpdesk_vendor.sql or
-- otherwise already fixed this.
ALTER TABLE orders
    MODIFY COLUMN status ENUM('pending', 'dispatched', 'delivered') DEFAULT 'pending';
