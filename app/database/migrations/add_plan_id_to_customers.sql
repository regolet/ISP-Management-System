-- Add plan_id column to customers table
ALTER TABLE customers
ADD COLUMN plan_id INT,
ADD CONSTRAINT fk_customer_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL;
