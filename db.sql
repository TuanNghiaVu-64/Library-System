-- =============================================================
-- Public Library Database
-- PostgreSQL
-- =============================================================

-- Extensions
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- =============================================================
-- TYPES / ENUMS
-- =============================================================

CREATE TYPE role_name_enum AS ENUM ('guest', 'librarian', 'admin');
CREATE TYPE subscription_status AS ENUM ('active', 'paused', 'cancelled');
CREATE TYPE book_condition AS ENUM ('new', 'good', 'fair', 'poor');
CREATE TYPE payment_status_enum AS ENUM ('unpaid', 'paid', 'waived');
CREATE TYPE payment_type_enum AS ENUM ('subscription', 'late_fee');
CREATE TYPE payment_method_enum AS ENUM ('cash', 'card', 'online');

-- =============================================================
-- TABLES
-- =============================================================

CREATE TABLE roles (
    role_id     SERIAL PRIMARY KEY,
    role_name   role_name_enum NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE accounts (
    account_id    SERIAL PRIMARY KEY,
    role_id       INT NOT NULL REFERENCES roles(role_id),
    created_by    INT REFERENCES accounts(account_id) ON DELETE SET NULL,
    first_name    VARCHAR(255) NOT NULL,
    last_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(512) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE library_cards (
    card_id     SERIAL PRIMARY KEY,
    account_id  INT NOT NULL UNIQUE REFERENCES accounts(account_id) ON DELETE CASCADE,
    card_number VARCHAR(20) NOT NULL UNIQUE,
    issued_date DATE NOT NULL DEFAULT CURRENT_DATE,
    expiry_date DATE NOT NULL,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_card_dates CHECK (expiry_date > issued_date)
);

CREATE TABLE subscriptions (
    subscription_id SERIAL PRIMARY KEY,
    card_id         INT NOT NULL REFERENCES library_cards(card_id),
    billing_date    DATE NOT NULL,
    monthly_fee     NUMERIC(8,2) NOT NULL CHECK (monthly_fee >= 0),
    status          subscription_status NOT NULL DEFAULT 'active',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE book_categories (
    category_id SERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE books (
    book_id      SERIAL PRIMARY KEY,
    category_id  INT NOT NULL REFERENCES book_categories(category_id),
    added_by     INT NOT NULL REFERENCES accounts(account_id),
    title        VARCHAR(1000) NOT NULL,
    author       VARCHAR(512) NOT NULL,
    isbn         VARCHAR(20) UNIQUE,
    publisher    VARCHAR(255),
    publish_year SMALLINT CHECK (publish_year BETWEEN 1000 AND EXTRACT(YEAR FROM NOW())::INT + 1),
    description  TEXT,
    added_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE book_copies (
    copy_id      SERIAL PRIMARY KEY,
    book_id      INT NOT NULL REFERENCES books(book_id) ON DELETE CASCADE,
    condition    book_condition NOT NULL DEFAULT 'good',
    is_available BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE borrow_records (
    borrow_id            SERIAL PRIMARY KEY,
    copy_id              INT NOT NULL REFERENCES book_copies(copy_id),
    card_id              INT NOT NULL REFERENCES library_cards(card_id),
    issued_by            INT NOT NULL REFERENCES accounts(account_id),
    borrow_date          DATE NOT NULL DEFAULT CURRENT_DATE,
    expected_return_date DATE NOT NULL,
    actual_return_date   DATE,
    is_returned          BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT chk_return_date    CHECK (expected_return_date > borrow_date),
    CONSTRAINT chk_actual_return  CHECK (actual_return_date IS NULL OR actual_return_date >= borrow_date)
);

CREATE TABLE late_fees (
    fee_id         SERIAL PRIMARY KEY,
    borrow_id      INT NOT NULL UNIQUE REFERENCES borrow_records(borrow_id),
    days_overdue   INT NOT NULL CHECK (days_overdue > 0),
    fee_amount     NUMERIC(8,2) NOT NULL CHECK (fee_amount >= 0),
    payment_status payment_status_enum NOT NULL DEFAULT 'unpaid',
    charged_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    paid_at        TIMESTAMPTZ
);

CREATE TABLE payments (
    payment_id      SERIAL PRIMARY KEY,
    card_id         INT NOT NULL REFERENCES library_cards(card_id),
    subscription_id INT REFERENCES subscriptions(subscription_id),
    fee_id          INT REFERENCES late_fees(fee_id),
    amount          NUMERIC(8,2) NOT NULL CHECK (amount > 0),
    payment_type    payment_type_enum NOT NULL,
    method          payment_method_enum,
    paid_at         TIMESTAMPTZ,
    CONSTRAINT chk_payment_source CHECK (
        (payment_type = 'subscription' AND subscription_id IS NOT NULL) OR
        (payment_type = 'late_fee'     AND fee_id IS NOT NULL)
    )
);

-- =============================================================
-- INDEXES
-- =============================================================

CREATE INDEX idx_accounts_role        ON accounts(role_id);
CREATE INDEX idx_accounts_email       ON accounts(email);
CREATE INDEX idx_library_cards_acct   ON library_cards(account_id);
CREATE INDEX idx_subscriptions_card   ON subscriptions(card_id);
CREATE INDEX idx_books_category       ON books(category_id);
CREATE INDEX idx_books_isbn           ON books(isbn);
CREATE INDEX idx_books_author         ON books(author);
CREATE INDEX idx_book_copies_book     ON book_copies(book_id);
CREATE INDEX idx_book_copies_avail    ON book_copies(book_id, is_available);
CREATE INDEX idx_borrow_copy          ON borrow_records(copy_id);
CREATE INDEX idx_borrow_card          ON borrow_records(card_id);
CREATE INDEX idx_borrow_unreturned    ON borrow_records(is_returned, expected_return_date) WHERE is_returned = FALSE;
CREATE INDEX idx_late_fees_status     ON late_fees(payment_status);
CREATE INDEX idx_payments_card        ON payments(card_id);
CREATE INDEX idx_payments_paid_at     ON payments(paid_at);

-- =============================================================
-- FUNCTIONS & TRIGGERS
-- =============================================================

-- Auto-update updated_at on accounts
CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_accounts_updated_at
    BEFORE UPDATE ON accounts
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

-- Mark copy unavailable when borrowed
CREATE OR REPLACE FUNCTION fn_borrow_mark_unavailable()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    UPDATE book_copies SET is_available = FALSE WHERE copy_id = NEW.copy_id;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_borrow_take_copy
    AFTER INSERT ON borrow_records
    FOR EACH ROW EXECUTE FUNCTION fn_borrow_mark_unavailable();

-- Mark copy available again when returned
CREATE OR REPLACE FUNCTION fn_return_mark_available()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    IF NEW.is_returned = TRUE AND OLD.is_returned = FALSE THEN
        UPDATE book_copies SET is_available = TRUE WHERE copy_id = NEW.copy_id;
        -- Auto-create a late fee if overdue
        IF NEW.actual_return_date > NEW.expected_return_date THEN
            INSERT INTO late_fees (borrow_id, days_overdue, fee_amount)
            VALUES (
                NEW.borrow_id,
                (NEW.actual_return_date - NEW.expected_return_date),
                (NEW.actual_return_date - NEW.expected_return_date) * 5.00
            );
        END IF;
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_return_copy
    AFTER UPDATE ON borrow_records
    FOR EACH ROW EXECUTE FUNCTION fn_return_mark_available();

-- =============================================================
-- SEED DATA
-- =============================================================

INSERT INTO roles (role_name, description) VALUES
    ('admin',     'Manages accounts, system configuration'),
    ('librarian', 'Manages books, processes borrows and returns'),
    ('guest',     'Library member — can borrow and return books');

INSERT INTO book_categories (name) VALUES
    ('Fiction'),
    ('Non-Fiction'),
    ('Science'),
    ('History'),
    ('Technology'),
    ('Biography'),
    ('Children'),
    ('Philosophy');

-- Default admin account (password: Admin@1234 — change before production)
INSERT INTO accounts (role_id, first_name, last_name, email, password_hash)
VALUES (
    (SELECT role_id FROM roles WHERE role_name = 'admin'),
    'System', 'Admin',
    'admin@library.local',
    crypt('Admin@1234', gen_salt('bf'))
);

-- Librarian account
INSERT INTO accounts (role_id, first_name, last_name, email, password_hash)
VALUES (
    (SELECT role_id FROM roles WHERE role_name = 'librarian'),
    'Sarah', 
    'Connor', 
    'sarah.c@library.local', 
    crypt('Librarian@2026', gen_salt('bf'))
);

-- Guest account
INSERT INTO accounts (role_id, first_name, last_name, email, password_hash)
VALUES (
    (SELECT role_id FROM roles WHERE role_name = 'guest'),
    'John', 
    'Doe', 
    'john.doe@email.com', 
    crypt('MemberPass123', gen_salt('bf'))
);

-- Guest library card
INSERT INTO library_cards (account_id, card_number, expiry_date)
VALUES (
    (SELECT account_id FROM accounts WHERE email = 'john.doe@email.com'),
    'CARD-998877',
    '2027-12-31'
);

-- Active subscription for guest card
INSERT INTO subscriptions (card_id, billing_date, monthly_fee, status)
VALUES (
    (SELECT card_id FROM library_cards WHERE card_number = 'CARD-998877'),
    CURRENT_DATE,
    10.00,
    'active'
);