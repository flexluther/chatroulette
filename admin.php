<?php
/**
 * Admin Panel for Chatroulette
 * Handles user management and banning functionality
 */

// Database configuration
$host = 'localhost';
$dbname = 'chatroulette';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

/**
 * Function to ban persistent tracking
 * This would be implemented elsewhere in the system
 */
function banPersistentTracking($persistent_cookie_id, $ip) {
    // Implementation would go here
    return true;
}

/**
 * Function to unban persistent tracking
 * This would be implemented elsewhere in the system
 */
function unbanPersistentTracking($persistent_cookie_id, $ip) {
    // Implementation would go here
    return true;
}

/**
 * Handle ban actions from admin panel
 */
function handleBanActions($pdo, $actionType, $target) {
    if ($actionType === 'ban_all') {
        // Ban all matching fingerprints
        $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 1 WHERE ip = :ip OR strict_hash = :strict_hash OR medium_hash = :medium_hash OR loose_hash = :loose_hash OR fingerprint_hash = :fingerprint_hash');
        $stmt2->execute([
            'ip' => $target['ip'],
            'strict_hash' => $target['strict_hash'],
            'medium_hash' => $target['medium_hash'],
            'loose_hash' => $target['loose_hash'],
            'fingerprint_hash' => $target['fingerprint_hash']
        ]);
    } elseif ($actionType === 'ban_ip') {
        // Ban by IP only
        $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 1 WHERE ip = :ip');
        $stmt2->execute(['ip' => $target['ip']]);
    } elseif ($actionType === 'ban_persistent') {
        // FIXED: Ban using persistent cookie tracking - only target specific device
        if (!empty($target['persistent_cookie_id'])) {
            // Primary method: Ban only records with the specific persistent cookie ID
            $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 1 WHERE persistent_cookie_id = :persistent_cookie_id');
            $stmt2->execute(['persistent_cookie_id' => $target['persistent_cookie_id']]);
            
            // Also ban in persistent tracking system
            if (function_exists('banPersistentTracking')) {
                banPersistentTracking($target['persistent_cookie_id'], $target['ip']);
            }
        } else {
            // Fallback: If no persistent cookie ID, ban by fingerprint hash only (most specific)
            if (!empty($target['fingerprint_hash'])) {
                $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 1 WHERE fingerprint_hash = :fingerprint_hash');
                $stmt2->execute(['fingerprint_hash' => $target['fingerprint_hash']]);
            } else {
                // Last resort: Ban by strict hash only
                $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 1 WHERE strict_hash = :strict_hash');
                $stmt2->execute(['strict_hash' => $target['strict_hash']]);
            }
        }
    } elseif ($actionType === 'unban') {
        // FIXED: More precise unban logic - check if this is a persistent ban first
        if (!empty($target['persistent_cookie_id'])) {
            // Primary method: Unban only records with the specific persistent cookie ID
            $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 0 WHERE persistent_cookie_id = :persistent_cookie_id');
            $stmt2->execute(['persistent_cookie_id' => $target['persistent_cookie_id']]);
            
            // Also unban in persistent tracking system
            if (function_exists('unbanPersistentTracking')) {
                unbanPersistentTracking($target['persistent_cookie_id'], $target['ip']);
            }
        } else {
            // Fallback: Broader unban for other ban types (IP, fingerprint, etc.)
            $stmt2 = $pdo->prepare('UPDATE fingerprints SET banned = 0 WHERE ip = :ip OR strict_hash = :strict_hash OR medium_hash = :medium_hash OR loose_hash = :loose_hash OR fingerprint_hash = :fingerprint_hash');
            $stmt2->execute([
                'ip' => $target['ip'],
                'strict_hash' => $target['strict_hash'],
                'medium_hash' => $target['medium_hash'],
                'loose_hash' => $target['loose_hash'],
                'fingerprint_hash' => $target['fingerprint_hash']
            ]);
        }
    }
}

// Example usage and admin interface
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$db_connected) {
        echo "<div style='color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 4px;'>
                <strong>Database not connected:</strong> $db_error<br>
                <em>This is a demonstration. In production, ensure database is properly configured.</em>
              </div>";
    } else {
        $action = $_POST['action'];
        $target = [
            'ip' => $_POST['ip'] ?? '',
            'strict_hash' => $_POST['strict_hash'] ?? '',
            'medium_hash' => $_POST['medium_hash'] ?? '',
            'loose_hash' => $_POST['loose_hash'] ?? '',
            'fingerprint_hash' => $_POST['fingerprint_hash'] ?? '',
            'persistent_cookie_id' => $_POST['persistent_cookie_id'] ?? ''
        ];
        
        handleBanActions($pdo, $action, $target);
        echo "<div style='color: green; background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 4px;'>
                Action '$action' completed successfully.
              </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chatroulette Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 200px; }
        input[type="text"] { width: 300px; padding: 5px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .ban-btn { background-color: #dc3545; color: white; }
        .unban-btn { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <h1>Chatroulette Admin Panel</h1>
    
    <?php if (!$db_connected): ?>
    <div style="color: #856404; background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 4px; border: 1px solid #ffeaa7;">
        <strong>⚠️ Database not connected:</strong> <?php echo htmlspecialchars($db_error); ?><br>
        <em>This is a demonstration interface. In production, ensure database is properly configured.</em>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <h2>User Information</h2>
        <div class="form-group">
            <label>IP Address:</label>
            <input type="text" name="ip" placeholder="192.168.1.1">
        </div>
        <div class="form-group">
            <label>Strict Hash:</label>
            <input type="text" name="strict_hash" placeholder="strict_hash_value">
        </div>
        <div class="form-group">
            <label>Medium Hash:</label>
            <input type="text" name="medium_hash" placeholder="medium_hash_value">
        </div>
        <div class="form-group">
            <label>Loose Hash:</label>
            <input type="text" name="loose_hash" placeholder="loose_hash_value">
        </div>
        <div class="form-group">
            <label>Fingerprint Hash:</label>
            <input type="text" name="fingerprint_hash" placeholder="fingerprint_hash_value">
        </div>
        <div class="form-group">
            <label>Persistent Cookie ID:</label>
            <input type="text" name="persistent_cookie_id" placeholder="persistent_cookie_id">
        </div>
        
        <h2>Actions</h2>
        <p><strong>BAN ALL:</strong> Bans all fingerprints matching any criteria (broadest ban)</p>
        <p><strong>BAN IP:</strong> Bans only the specific IP address</p>
        <p><strong>BAN PERSISTENT:</strong> Bans only the specific device using persistent cookie ID (most precise)</p>
        <p><strong>UNBAN:</strong> Removes ban (precise if persistent cookie ID provided, otherwise broad)</p>
        
        <button type="submit" name="action" value="ban_all" class="ban-btn">BAN ALL</button>
        <button type="submit" name="action" value="ban_ip" class="ban-btn">BAN IP</button>
        <button type="submit" name="action" value="ban_persistent" class="ban-btn">BAN PERSISTENT</button>
        <button type="submit" name="action" value="unban" class="unban-btn">UNBAN</button>
    </form>
</body>
</html>