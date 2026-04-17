<?php
// ============================================================
//  DROPPERS CAFÉ — WALLET HELPER
//  Include: require_once dirname(__DIR__) . '/includes/wallet_helper.php';
// ============================================================

// ── Auto-create tables ──────────────────────────────────────
function wallet_ensure_tables($conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS `wallet` (
        `id`         INT(11)       NOT NULL AUTO_INCREMENT,
        `customer_id`INT(11)       NOT NULL,
        `balance`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS `wallet_transactions` (
        `id`          INT(11)       NOT NULL AUTO_INCREMENT,
        `customer_id` INT(11)       NOT NULL,
        `type`        ENUM('credit','debit') NOT NULL,
        `amount`      DECIMAL(10,2) NOT NULL,
        `description` VARCHAR(255)  NOT NULL DEFAULT '',
        `ref_id`      VARCHAR(100)  DEFAULT NULL,
        `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Get wallet balance ──────────────────────────────────────
function wallet_balance($conn, int $customer_id): float {
    $stmt = $conn->prepare("SELECT balance FROM wallet WHERE customer_id=?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? floatval($row['balance']) : 0.0;
}

// ── Credit (add money) ──────────────────────────────────────
function wallet_credit($conn, int $customer_id, float $amount, string $desc, string $ref_id = ''): bool {
    // Upsert wallet row
    $conn->query("INSERT INTO wallet (customer_id, balance) VALUES ($customer_id, 0)
                  ON DUPLICATE KEY UPDATE customer_id=customer_id");

    $stmt = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE customer_id=?");
    $stmt->bind_param("di", $amount, $customer_id);
    $stmt->execute();

    $new_balance = wallet_balance($conn, $customer_id);
    $log = $conn->prepare("INSERT INTO wallet_transactions (customer_id, type, amount, description, ref_id, balance_after) VALUES (?,?,?,?,?,?)");
    $log->bind_param("isdssd", $customer_id, 'credit', $amount, $desc, $ref_id, $new_balance);
    return $log->execute();
}

// ── Debit (deduct money) ────────────────────────────────────
function wallet_debit($conn, int $customer_id, float $amount, string $desc, string $ref_id = ''): bool {
    $balance = wallet_balance($conn, $customer_id);
    if($balance < $amount) return false; // insufficient balance

    $stmt = $conn->prepare("UPDATE wallet SET balance = balance - ? WHERE customer_id=?");
    $stmt->bind_param("di", $amount, $customer_id);
    $stmt->execute();

    $new_balance = wallet_balance($conn, $customer_id);
    $log = $conn->prepare("INSERT INTO wallet_transactions (customer_id, type, amount, description, ref_id, balance_after) VALUES (?,?,?,?,?,?)");
    $log->bind_param("isdssd", $customer_id, 'debit', $amount, $desc, $ref_id, $new_balance);
    return $log->execute();
}

// ── Transaction history ─────────────────────────────────────
function wallet_transactions($conn, int $customer_id, int $limit = 20): array {
    $stmt = $conn->prepare("SELECT * FROM wallet_transactions WHERE customer_id=? ORDER BY id DESC LIMIT ?");
    $stmt->bind_param("ii", $customer_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>