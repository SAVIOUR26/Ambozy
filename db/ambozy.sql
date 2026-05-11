-- ================================================================
-- AMBOZY GRAPHICS SOLUTIONS LTD — Database Schema v1.0
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Uganda VAT: 18% | NSSF: 5% employee + 10% employer | PAYE: progressive
-- ================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Admin Users ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(60)    NOT NULL,
  `password_hash` VARCHAR(255)   NOT NULL,
  `full_name`     VARCHAR(120)   NOT NULL,
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `active`        TINYINT(1)     NOT NULL DEFAULT 1,
  `last_login`    DATETIME,
  `created_at`    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin — password: Ambozy@2024 (change immediately after setup)
INSERT IGNORE INTO `admin_users` (`username`,`password_hash`,`full_name`,`role`) VALUES
('admin','$2y$12$LvxK8VJYpBz.kI1LvXlRt.Qk8y0G5Y7K2mXjO3uQ9tVzWnE4pD6Oy','Administrator','admin');

-- ── Clients ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clients` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(20),
  `name`       VARCHAR(200) NOT NULL,
  `company`    VARCHAR(200),
  `email`      VARCHAR(200),
  `phone`      VARCHAR(60),
  `address`    TEXT,
  `tin`        VARCHAR(50)  COMMENT 'URA Tax Identification Number',
  `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
  `notes`      TEXT,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Enquiries (walk-in / contact form) ──────────────────────────
CREATE TABLE IF NOT EXISTS `enquiries` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`   INT UNSIGNED,
  `name`        VARCHAR(200) NOT NULL,
  `email`       VARCHAR(200),
  `company`     VARCHAR(200),
  `phone`       VARCHAR(60),
  `service`     VARCHAR(100),
  `message`     TEXT,
  `status`      ENUM('new','in_progress','converted','closed') DEFAULT 'new',
  `notes`       TEXT,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_enq_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Projects ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `projects` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`      INT UNSIGNED NOT NULL,
  `enquiry_id`     INT UNSIGNED,
  `project_number` VARCHAR(30)  UNIQUE,
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT,
  `service_type`   VARCHAR(100),
  `status`         ENUM('new','in_progress','on_hold','completed','cancelled') DEFAULT 'new',
  `priority`       ENUM('low','medium','high','urgent') DEFAULT 'medium',
  `deadline`       DATE,
  `completed_at`   DATE,
  `notes`          TEXT,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_proj_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoices (issued to clients) ────────────────────────────────
-- amount_paid and balance are maintained by triggers/application after each payment
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(30)  NOT NULL,
  `client_id`      INT UNSIGNED NOT NULL,
  `project_id`     INT UNSIGNED,
  `issue_date`     DATE         NOT NULL,
  `due_date`       DATE         NOT NULL,
  `subtotal`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `vat_rate`       DECIMAL(5,2) NOT NULL DEFAULT 18.00,  -- Uganda standard VAT
  `vat_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status`         ENUM('draft','sent','partial','paid','overdue','cancelled') DEFAULT 'draft',
  `notes`          TEXT,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_num` (`invoice_number`),
  CONSTRAINT `fk_inv_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_inv_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoice Line Items ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `invoice_id`  INT UNSIGNED  NOT NULL,
  `description` VARCHAR(300)  NOT NULL,
  `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments Received (supports partial payments / client credit) ─
CREATE TABLE IF NOT EXISTS `payments_received` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_number` VARCHAR(30),
  `invoice_id`     INT UNSIGNED NOT NULL,
  `client_id`      INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(15,2) NOT NULL,
  `payment_date`   DATE         NOT NULL,
  `method`         ENUM('cash','bank_transfer','mobile_money','cheque','other') NOT NULL DEFAULT 'cash',
  `reference`      VARCHAR(100),
  `notes`          TEXT,
  `recorded_by`    INT UNSIGNED,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pr_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_pr_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Suppliers ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                VARCHAR(20),
  `name`                VARCHAR(200) NOT NULL,
  `contact_person`      VARCHAR(120),
  `email`               VARCHAR(200),
  `phone`               VARCHAR(60),
  `address`             TEXT,
  `tin`                 VARCHAR(50),
  `payment_terms_days`  INT          DEFAULT 30,
  `credit_limit`        DECIMAL(15,2) DEFAULT 0.00,
  `notes`               TEXT,
  `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Supplier Bills (credit purchases from suppliers) ─────────────
CREATE TABLE IF NOT EXISTS `supplier_bills` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bill_number` VARCHAR(30)  NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `bill_date`   DATE         NOT NULL,
  `due_date`    DATE         NOT NULL,
  `subtotal`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `vat_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status`      ENUM('pending','partial','paid','overdue') DEFAULT 'pending',
  `notes`       TEXT,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bill_num` (`bill_number`),
  CONSTRAINT `fk_sb_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Supplier Bill Line Items ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_bill_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `bill_id`     INT UNSIGNED  NOT NULL,
  `description` VARCHAR(300)  NOT NULL,
  `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sbi_bill` FOREIGN KEY (`bill_id`) REFERENCES `supplier_bills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments to Suppliers (partial settlement of credit) ─────────
CREATE TABLE IF NOT EXISTS `payments_to_suppliers` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bill_id`      INT UNSIGNED NOT NULL,
  `supplier_id`  INT UNSIGNED NOT NULL,
  `amount`       DECIMAL(15,2) NOT NULL,
  `payment_date` DATE         NOT NULL,
  `method`       ENUM('cash','bank_transfer','mobile_money','cheque','other') NOT NULL DEFAULT 'cash',
  `reference`    VARCHAR(100),
  `notes`        TEXT,
  `recorded_by`  INT UNSIGNED,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ps_bill`     FOREIGN KEY (`bill_id`)     REFERENCES `supplier_bills`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ps_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Expense Categories ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `expense_categories` (`name`) VALUES
('Fuel & Transport'),('Facilitation & Allowances'),('Utilities (Electricity/Water/Internet)'),
('Office Rent'),('Office Supplies'),('Repairs & Maintenance'),('Marketing & Advertising'),
('Bank Charges'),('Printing Consumables'),('Miscellaneous');

-- ── Expenses ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `expenses` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED,
  `description` VARCHAR(300) NOT NULL,
  `amount`      DECIMAL(15,2) NOT NULL,
  `expense_date` DATE         NOT NULL,
  `paid_by`     ENUM('cash','bank_transfer','mobile_money','petty_cash') DEFAULT 'cash',
  `receipt_ref` VARCHAR(100),
  `approved_by` VARCHAR(100),
  `notes`       TEXT,
  `recorded_by` INT UNSIGNED,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_exp_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Employees ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employees` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`    VARCHAR(150) NOT NULL,
  `position`     VARCHAR(100),
  `email`        VARCHAR(200),
  `phone`        VARCHAR(60),
  `national_id`  VARCHAR(50),
  `nssf_number`  VARCHAR(50),
  `tin`          VARCHAR(50)  COMMENT 'Employee TIN for PAYE',
  `gross_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `bank_name`    VARCHAR(100),
  `bank_account` VARCHAR(50),
  `start_date`   DATE,
  `end_date`     DATE,
  `active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `notes`        TEXT,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Runs (monthly batch) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period_month`         TINYINT      NOT NULL,
  `period_year`          SMALLINT     NOT NULL,
  `run_date`             DATE         NOT NULL,
  `status`               ENUM('draft','approved','paid') DEFAULT 'draft',
  `total_gross`          DECIMAL(15,2) DEFAULT 0.00,
  `total_paye`           DECIMAL(15,2) DEFAULT 0.00,
  `total_nssf_employee`  DECIMAL(15,2) DEFAULT 0.00,
  `total_nssf_employer`  DECIMAL(15,2) DEFAULT 0.00,
  `total_net`            DECIMAL(15,2) DEFAULT 0.00,
  `notes`                TEXT,
  `created_at`           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_period` (`period_month`,`period_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Items (one row per employee per run) ──────────────────
-- PAYE computed per Uganda Income Tax Act; NSSF = 5% employee / 10% employer
CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_run_id`  INT UNSIGNED NOT NULL,
  `employee_id`     INT UNSIGNED NOT NULL,
  `gross_salary`    DECIMAL(15,2) NOT NULL,
  `paye`            DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `nssf_employee`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `nssf_employer`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `other_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `net_pay`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_ref`     VARCHAR(100),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_run` (`payroll_run_id`,`employee_id`),
  CONSTRAINT `fk_pi_run` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_emp` FOREIGN KEY (`employee_id`)    REFERENCES `employees`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Statutory Payment Types ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `statutory_types` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `authority`   VARCHAR(100),
  `frequency`   ENUM('monthly','quarterly','annually','ad_hoc') DEFAULT 'monthly',
  `description` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `statutory_types` (`name`,`authority`,`frequency`,`description`) VALUES
('PAYE (Income Tax)',     'Uganda Revenue Authority',      'monthly',    'Monthly PAYE remittance to URA'),
('VAT Return',           'Uganda Revenue Authority',      'monthly',    'Monthly VAT return & payment to URA (18%)'),
('NSSF — Total',         'National Social Security Fund', 'monthly',    '15% of gross payroll (employee 5% + employer 10%)'),
('Local Service Tax',    'KCCA / Local Government',       'annually',   'Annual local service tax'),
('Withholding Tax (WHT)','Uganda Revenue Authority',      'ad_hoc',     'WHT deducted on applicable payments');

-- ── Statutory Payments Made ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `statutory_payments` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `statutory_type_id`  INT UNSIGNED NOT NULL,
  `period_month`       TINYINT,
  `period_year`        SMALLINT     NOT NULL,
  `amount`             DECIMAL(15,2) NOT NULL,
  `payment_date`       DATE         NOT NULL,
  `reference`          VARCHAR(100),
  `payroll_run_id`     INT UNSIGNED,
  `notes`              TEXT,
  `recorded_by`        INT UNSIGNED,
  `created_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sp_type` FOREIGN KEY (`statutory_type_id`) REFERENCES `statutory_types`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sp_run`  FOREIGN KEY (`payroll_run_id`)     REFERENCES `payroll_runs`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Loans (taken by the company) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `loans` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`           VARCHAR(30),
  `lender_name`         VARCHAR(200) NOT NULL,
  `loan_type`           ENUM('bank','microfinance','sacco','personal','other') DEFAULT 'bank',
  `principal`           DECIMAL(15,2) NOT NULL,
  `interest_rate`       DECIMAL(5,2)  NOT NULL DEFAULT 0.00,  -- annual %
  `term_months`         INT           NOT NULL,
  `disbursement_date`   DATE          NOT NULL,
  `maturity_date`       DATE,
  `monthly_installment` DECIMAL(15,2) DEFAULT 0.00,
  `total_repaid`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance_outstanding` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `purpose`             TEXT,
  `status`              ENUM('active','settled','defaulted') DEFAULT 'active',
  `notes`               TEXT,
  `created_at`          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Loan Repayments ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `loan_repayments` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id`        INT UNSIGNED NOT NULL,
  `payment_date`   DATE         NOT NULL,
  `principal_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `interest_paid`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_paid`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `method`         ENUM('cash','bank_transfer','mobile_money','cheque','other') DEFAULT 'bank_transfer',
  `reference`      VARCHAR(100),
  `notes`          TEXT,
  `recorded_by`    INT UNSIGNED,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lr_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
