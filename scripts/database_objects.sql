-- Table for auditing borrow_items changes
CREATE TABLE IF NOT EXISTS borrow_items_audit (
    audit_id SERIAL PRIMARY KEY,
    borrow_id INT,
    equipment_id INT,
    operation_type VARCHAR(10) NOT NULL,
    old_quantity INT,
    new_quantity INT,
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- View: Borrowing details
CREATE OR REPLACE VIEW vw_borrowing_details AS
SELECT
    t.borrow_id,
    b.borrower_no,
    b.full_name,
    b.borrower_type,
    e.property_no,
    e.equipment_name,
    bi.quantity AS borrowed_quantity,
    t.borrow_date,
    t.due_date,
    t.status
FROM borrow_transactions t
JOIN borrowers b ON t.borrower_id = b.borrower_id
JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
JOIN equipment e ON bi.equipment_id = e.equipment_id;

-- View: Equipment usage and availability classification
CREATE OR REPLACE VIEW vw_equipment_usage AS
SELECT
    e.equipment_id,
    e.property_no,
    e.equipment_name,
    e.quantity_available,
    COUNT(DISTINCT bi.borrow_id) AS total_borrowing_transactions,
    COALESCE(SUM(bi.quantity), 0) AS total_quantities_borrowed,
    CASE
        WHEN e.quantity_available > 10 THEN 'High Availability'
        WHEN e.quantity_available BETWEEN 1 AND 10 THEN 'Moderate Availability'
        ELSE 'Low / Out of Stock'
    END AS availability_classification
FROM equipment e
LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
GROUP BY e.equipment_id, e.property_no, e.equipment_name, e.quantity_available;

-- Function: total borrowed quantity for one equipment (excluding cancelled)
CREATE OR REPLACE FUNCTION fn_total_borrowed(p_equipment_id INT)
RETURNS INT AS $$
BEGIN
    RETURN COALESCE(
        (
            SELECT SUM(bi.quantity)
            FROM borrow_items bi
            JOIN borrow_transactions bt ON bi.borrow_id = bt.borrow_id
            WHERE bi.equipment_id = p_equipment_id
              AND bt.status <> 'Cancelled'
        ),
        0
    );
END;
$$ LANGUAGE plpgsql;

-- Function: check if equipment can satisfy requested quantity
CREATE OR REPLACE FUNCTION fn_check_equipment_availability(p_equipment_id INT, p_requested_qty INT)
RETURNS BOOLEAN AS $$
DECLARE
    v_available INT;
BEGIN
    SELECT quantity_available INTO v_available
    FROM equipment
    WHERE equipment_id = p_equipment_id;

    IF NOT FOUND THEN
        RAISE NOTICE 'Equipment ID % does not exist.', p_equipment_id;
        RETURN FALSE;
    END IF;

    IF v_available >= p_requested_qty THEN
        RETURN TRUE;
    END IF;

    RETURN FALSE;
END;
$$ LANGUAGE plpgsql;

-- Trigger function: validate quantity before insert/update
CREATE OR REPLACE FUNCTION trg_validate_borrow_quantity_func()
RETURNS TRIGGER AS $$
DECLARE
    v_available INT;
    v_eq_name VARCHAR;
BEGIN
    SELECT quantity_available, equipment_name
    INTO v_available, v_eq_name
    FROM equipment
    WHERE equipment_id = NEW.equipment_id;

    IF TG_OP = 'UPDATE' THEN
        v_available := v_available + OLD.quantity;
    END IF;

    IF v_available < NEW.quantity THEN
        RAISE EXCEPTION
            'Validation Error: Insufficient stock for "%". Requested: %, Available: %',
            v_eq_name, NEW.quantity, v_available;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validate_borrow_quantity ON borrow_items;
CREATE TRIGGER trg_validate_borrow_quantity
    BEFORE INSERT OR UPDATE ON borrow_items
    FOR EACH ROW
    EXECUTE FUNCTION trg_validate_borrow_quantity_func();

-- Trigger function: audit insert/update/delete on borrow_items
CREATE OR REPLACE FUNCTION trg_borrow_items_audit_func()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO borrow_items_audit (borrow_id, equipment_id, operation_type, old_quantity, new_quantity)
        VALUES (NEW.borrow_id, NEW.equipment_id, 'INSERT', NULL, NEW.quantity);
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO borrow_items_audit (borrow_id, equipment_id, operation_type, old_quantity, new_quantity)
        VALUES (NEW.borrow_id, NEW.equipment_id, 'UPDATE', OLD.quantity, NEW.quantity);
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO borrow_items_audit (borrow_id, equipment_id, operation_type, old_quantity, new_quantity)
        VALUES (OLD.borrow_id, OLD.equipment_id, 'DELETE', OLD.quantity, NULL);
        RETURN OLD;
    END IF;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_borrow_items_audit ON borrow_items;
CREATE TRIGGER trg_borrow_items_audit
    AFTER INSERT OR UPDATE OR DELETE ON borrow_items
    FOR EACH ROW
    EXECUTE FUNCTION trg_borrow_items_audit_func();
