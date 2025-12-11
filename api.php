<?php
// MAJLISH E SARKAR - API 

/**
 * This file handles all backend logic:
 * Dashboard
 * attendence system
 * payments
 * memmber's management
 * menus system
 * settings
 * 
 * API is action base:
 *  ?action=dashboard_stats
 * ?action=mark_attendence 
 * etc.
 */
 

//Load config, DB connection, helper function, and session system
require_once "configuration.php";

//tell the browser this API returns json type
header("Content-Type: application/json; charset=utf-8");


//Extract common user information from session
$action     = $_POST['action'] ?? $_GET['action'] ?? ''; //api function to execute
$userId     = getUserId(); //current  log in user data
$userRole   = getRole(); //'admin' or 'member'
$memberId   = getMemberId(); //member table ID (if exists)


// protect all private API calls
if ($action !== "login" && $action !== "check_cutoff") {
    login_Need(); //ensures user is logged in
}


//try -catch block
//every error is caugt and returned as JSON
try {

// DASHBOARD (ADMIN + MEMBER)
if ($action === "dashboard_stats") {
    /**
     * ADMIN DASHBOARD STATS 
     * 
     * Returns:
     * - Total members
     * - Today's meals breakdown
     * - Total meals count
     * - Total revenue
     * - Total meal cost
     * - Net balance (profit/loss)
     */

    if ($userRole === "admin") {

        //count active members
        $totalMembers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member' AND status='active'")->fetchColumn();
        //today's meal counts(breakfast, dinner, lunch)
        $today = $pdo->query("
            SELECT 
                SUM(CASE WHEN meal_type='breakfast' AND status='yes' THEN quantity ELSE 0 END) AS breakfast,
                SUM(CASE WHEN meal_type='lunch'     AND status='yes' THEN quantity ELSE 0 END) AS lunch,
                SUM(CASE WHEN meal_type='dinner'    AND status='yes' THEN quantity ELSE 0 END) AS dinner,
                SUM(CASE WHEN status='yes' THEN quantity ELSE 0 END) AS total
            FROM meal_attendance
            WHERE date = CURDATE()
        ")->fetch();

        // load cost-per-meal from settings
        $costPerMeal  = (float)getSetting("cost_per_meal", 60);
        
        // calculate total revenue (sum of all payments)
        $totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();

        // Total meals count (all time)
        $totalMeals   = (int)   $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM meal_attendance WHERE status='yes'")
                                    ->fetchColumn();
        //total meal cost 
        $totalCost = $totalMeals * $costPerMeal;
        
        
        // Return admin dashboard JSON
        echo json_encode([
            "success" => true,
            "data" => [
                "total_members"   => $totalMembers,
                "today_breakfast" => (int)$today['breakfast'],
                "today_lunch"     => (int)$today['lunch'],
                "today_dinner"    => (int)$today['dinner'],
                "today_meals"     => (int)$today['total'],
                "total_revenue"   => $totalRevenue,
                "total_meals_cost"=> $totalCost,
                "net_balance"     => $totalRevenue - $totalCost,
                "cost_per_meal"   => $costPerMeal
            ]
        ]);
        exit;
    }

    /** 
     * MEMBER DASHBOARD
     * 
     * Returns stats:
     * - Monthly meals summary
     * - Today's meals summary
     * - Monthly cost
     * - Personal balance
     */
    if (!$memberId) throw new Exception("Member profile missing.");

    // Monthly meals for logged in member
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN meal_type='breakfast' AND status='yes' THEN quantity ELSE 0 END) AS b,
            SUM(CASE WHEN meal_type='lunch'     AND status='yes' THEN quantity ELSE 0 END) AS l,
            SUM(CASE WHEN meal_type='dinner'    AND status='yes' THEN quantity ELSE 0 END) AS d,
            SUM(CASE WHEN status='yes' THEN quantity ELSE 0 END) AS total
        FROM meal_attendance
        WHERE member_id=? AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())
    ");
    $stmt->execute([$memberId]);
    $month = $stmt->fetch();

    // Today meals summary
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN meal_type='breakfast' AND status='yes' THEN quantity ELSE 0 END) AS b,
            SUM(CASE WHEN meal_type='lunch'     AND status='yes' THEN quantity ELSE 0 END) AS l,
            SUM(CASE WHEN meal_type='dinner'    AND status='yes' THEN quantity ELSE 0 END) AS d,
            SUM(CASE WHEN status='yes' THEN quantity ELSE 0 END) AS total
        FROM meal_attendance
        WHERE member_id=? AND date=CURDATE()
    ");
    $stmt->execute([$memberId]);
    $today = $stmt->fetch();

    //cost calculation
    $cost = (float)getSetting("cost_per_meal", 60);

    // personal Balance from view
    $stmt = $pdo->prepare("SELECT * FROM member_balance_view WHERE member_id=?");
    $stmt->execute([$memberId]);
    $balance = $stmt->fetch();
        
    
    // Return member dashboard JSON
    echo json_encode([
        "success" => true,
        "data" => [
            "monthly_breakfast" => (int)$month['b'],
            "monthly_lunch"     => (int)$month['l'],
            "monthly_dinner"    => (int)$month['d'],
            "monthly_meals"     => (int)$month['total'],
            "monthly_cost"      => (int)$month['total'] * $cost,

            "today_breakfast"   => (int)$today['b'],
            "today_lunch"       => (int)$today['l'],
            "today_dinner"      => (int)$today['d'],
            "today_meals"       => (int)$today['total'],

            "total_deposited"   => (float)($balance['total_deposited'] ?? 0),
            "total_spent"       => (float)($balance['total_meal_cost'] ?? 0),
            "balance"           => (float)($balance['balance'] ?? 0),
            "cost_per_meal"     => $cost
        ]
    ]);
    exit;
}


// CHECK CUTOFF
if ($action === "check_cutoff") {
    $meal = $_GET['meal_type'] ?? '';
    $date = $_GET['date'] ?? date("Y-m-d");

    if (!$meal) throw new Exception("Meal type required!");

    echo json_encode([
        "success" => true,
        "data" => [
            "can_edit" => canEditMeal($meal, $date), // whether editing is allowed
            "cutoff"   => getSetting($meal . "_cutoff", "00:00") //return cutoff times
        ]
    ]);
    exit;
}

// MARK ATTENDANCE
if ($action === "mark_attendance") {
    if (!$memberId) throw new Exception("Member not found!");

    $meal   = $_POST['meal_type'] ?? '';
    $status = $_POST['status'] ?? '';
    $qty    = max(1, (int)($_POST['quantity'] ?? 1)); //minimum 1
    $date   = date("Y-m-d");

    if (!$meal || !$status) throw new Exception("Invalid request!");

    if (!canEditMeal($meal, $date))
        throw new Exception("Cutoff passed!");

    // Insert or update attendance (ON DUPLICATE KEY)
    $stmt = $pdo->prepare("
        INSERT INTO meal_attendance (member_id,date,meal_type,status,quantity)
        VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE status=VALUES(status), quantity=VALUES(quantity)
    ");
    $stmt->execute([$memberId, $date, $meal, $status, $qty]);

    echo json_encode(["success" => true, "message" => "Saved"]);
    exit;
}

// GET MY ATTENDANCE
if ($action === "get_my_attendance") {

    $date = date("Y-m-d");

    $stmt = $pdo->prepare("SELECT * FROM meal_attendance WHERE member_id=? AND date=?");
    $stmt->execute([$memberId, $date]);
    $rows = $stmt->fetchAll();

    //create map for breakfast/lunch/dinner;
    $map = [];
    foreach ($rows as $r) {
        $map[$r['meal_type']] = $r;
    }

    echo json_encode([
        "success" => true,
        "data" => $map,
        "cutoff_status" => [
            "breakfast" => canEditMeal("breakfast", $date),
            "lunch"     => canEditMeal("lunch",     $date),
            "dinner"    => canEditMeal("dinner",    $date)
        ]
    ]);
    exit;
}


// MEALS HISTORY
if ($action === "get_my_meals_history") {

    $stmt = $pdo->prepare("
        SELECT 
            date,
            SUM(CASE WHEN meal_type='breakfast' AND status='yes' THEN quantity ELSE 0 END) AS breakfast,
            SUM(CASE WHEN meal_type='lunch'     AND status='yes' THEN quantity ELSE 0 END) AS lunch,
            SUM(CASE WHEN meal_type='dinner'    AND status='yes' THEN quantity ELSE 0 END) AS dinner,
            SUM(CASE WHEN status='yes' THEN quantity ELSE 0 END) AS total
        FROM meal_attendance
        WHERE member_id=?
        GROUP BY date
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute([$memberId]);

    echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    exit;
}

// PAYMENT - ADD
if ($action === "add_payment") {
    adminrequire(); // ensure admin only

    $stmt = $pdo->prepare("
        INSERT INTO payments (member_id, amount, payment_date, payment_method, reference_number, notes, created_by)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $_POST['member_id'],
        $_POST['amount'],
        $_POST['payment_date'],
        $_POST['payment_method'] ?? "cash",
        $_POST['reference_number'] ?? '',
        $_POST['notes'] ?? '',
        $userId
    ]);

    echo json_encode(["success" => true, "message" => "Payment added"]);
    exit;
}

// GET PAYMENTS
if ($action === "get_payments") {
    adminrequire();

    $stmt = $pdo->query("
        SELECT 
            p.*,
            u.name AS member_name,
            m.room
        FROM payments p
        JOIN members m ON p.member_id=m.id
        JOIN users u ON m.user_id=u.id
        ORDER BY p.payment_date DESC
    ");

    echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    exit;
}

// DELETE PAYMENT
if ($action === "delete_payment") {
    adminrequire();

    $stmt = $pdo->prepare("DELETE FROM payments WHERE id=?");
    $stmt->execute([$_POST['id']]);

    echo json_encode(["success" => true, "message" => "Deleted"]);
    exit;
}


// GET ALL BALANCES (ADMIN)
if ($action === "get_all_balances") {
    adminrequire(); 
    /**
     * This  SQL calculates:
     *  - Total meals of each type
     *  - Total cost per member
     *  - Total deposited money
     *  - Remaining balance
     */

    $sql = "
        SELECT 
            u.name,
            m.room,
            COALESCE(SUM(CASE WHEN ma.meal_type='breakfast' AND ma.status='yes' THEN ma.quantity ELSE 0 END),0) AS breakfast_count,
            COALESCE(SUM(CASE WHEN ma.meal_type='lunch'     AND ma.status='yes' THEN ma.quantity ELSE 0 END),0) AS lunch_count,
            COALESCE(SUM(CASE WHEN ma.meal_type='dinner'    AND ma.status='yes' THEN ma.quantity ELSE 0 END),0) AS dinner_count,
            COALESCE(SUM(CASE WHEN ma.status='yes' THEN ma.quantity ELSE 0 END),0) AS total_meals_count,
            COALESCE(p.total_deposited,0) AS total_deposited,

            (
                COALESCE(SUM(CASE WHEN ma.status='yes' THEN ma.quantity ELSE 0 END),0)
                * (SELECT value FROM settings WHERE key_name='cost_per_meal' LIMIT 1)
            ) AS total_meal_cost,

            (
                COALESCE(p.total_deposited,0)
                - (
                    COALESCE(SUM(CASE WHEN ma.status='yes' THEN ma.quantity ELSE 0 END),0)
                    * (SELECT value FROM settings WHERE key_name='cost_per_meal' LIMIT 1)
                )
            ) AS balance

        FROM users u
        JOIN members m ON u.id = m.user_id
        LEFT JOIN meal_attendance ma ON m.id = ma.member_id
        LEFT JOIN (
            SELECT member_id, SUM(amount) AS total_deposited
            FROM payments
            GROUP BY member_id
        ) p ON p.member_id = m.id

        WHERE u.role='member'
        GROUP BY u.id, m.room, p.total_deposited
        ORDER BY m.room ASC
    ";

    echo json_encode([
        "success" => true,
        "data"    => $pdo->query($sql)->fetchAll()
    ]);
    exit;
}

// MENU SAVE (admin only)
if ($action === "save_menu") {
    adminrequire();

    $stmt = $pdo->prepare("
        INSERT INTO meal_menu (date, breakfast, lunch, dinner)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE 
            breakfast=VALUES(breakfast),
            lunch=VALUES(lunch),
            dinner=VALUES(dinner)
    ");
    $stmt->execute([
        $_POST['date'],
        $_POST['breakfast'] ?? '',
        $_POST['lunch'] ?? '',
        $_POST['dinner'] ?? ''
    ]);

    echo json_encode(["success" => true, "message" => "Menu saved"]);
    exit;
}

// GET TODAY MENU
if ($action === "get_today_menu") {

    $stmt = $pdo->prepare("SELECT * FROM meal_menu WHERE date=?");
    $stmt->execute([date("Y-m-d")]);

    echo json_encode(["success" => true, "data" => $stmt->fetch()]);
    exit;
}

// GET ALL MENUS(only admin)
if ($action === "get_menus") {
    adminrequire();

    $menus = $pdo->query("
        SELECT * FROM meal_menu 
        ORDER BY date DESC 
        LIMIT 30
    ")->fetchAll();

    echo json_encode(["success" => true, "data" => $menus]);
    exit;
}

// DELETE MENU
if ($action === "delete_menu") {
    adminrequire();

    $stmt = $pdo->prepare("DELETE FROM meal_menu WHERE id=?");
    $stmt->execute([$_POST['id']]);

    echo json_encode(["success" => true, "message" => "Menu deleted"]);
    exit;
}

// ADD/UPDATE MEMBER
if ($action === "add_member") {
    adminrequire();

    $pdo->beginTransaction();

    try {
        $uid = $_POST['member_id'] ?? null;
        //update existing memeber
        if (!empty($uid)) {
            // if password provided: password also
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET name=?, email=?, password=?, phone=?, address=?, status=?
                    WHERE id=? AND role='member'
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['status'],
                    $uid
                ]);
            } 
            //otherwise: update without password
            else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET name=?, email=?, phone=?, address=?, status=?
                    WHERE id=? AND role='member'
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['status'],
                    $uid
                ]);
            }
           //update member room
            $stmt = $pdo->prepare("UPDATE members SET room=? WHERE user_id=?");
            $stmt->execute([$_POST['room'], $uid]);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Member updated"]);
            exit;
        }

        // INSERT NEW MEMBER
        $stmt = $pdo->prepare("
            INSERT INTO users (name,email,password,role,phone,address,status)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            "member",
            $_POST['phone'],
            $_POST['address'],
            $_POST['status']
        ]);

        $id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO members (user_id,room) VALUES (?,?)");
        $stmt->execute([$id, $_POST['room']]);

        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Member added"]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// GET MEMBERS
if ($action === "get_members") {
    adminrequire();

    $members = $pdo->query("
        SELECT 
            u.id, u.name, u.email, u.phone, u.address, u.status,
            m.room, m.id AS member_id
        FROM users u
        JOIN members m ON u.id=m.user_id
        WHERE u.role='member'
        ORDER BY m.room ASC
    ")->fetchAll();

    echo json_encode(["success" => true, "data" => $members]);
    exit;
}


// DELETE MEMBER
if ($action === "delete_member") {
    adminrequire();

    $pdo->prepare("DELETE FROM users WHERE id=? AND role='member'")
        ->execute([$_POST['id']]);

    echo json_encode(["success" => true, "message" => "Member removed"]);
    exit;
}

// SETTINGS - UPDATE
if ($action === "update_settings") {
    adminrequire();
    
    // Loop through all POST fields (except action)
    foreach ($_POST as $k => $v) 
    {
        if ($k === "action") continue;
        updateSetting($k, $v);
    }

    echo json_encode(["success" => true, "message" => "Saved"]);
    exit;
}

// SETTINGS - GET
if ($action === "get_settings") {
    adminrequire();

    $rows = $pdo->query("SELECT key_name,value FROM settings")->fetchAll();
    $map = [];

    foreach ($rows as $r) 
    {
        $map[$r['key_name']] = $r['value'];
    }

    echo json_encode(["success" => true, "data" => $map]);
    exit;
}

// INVALID ACTION
throw new Exception("Invalid action: " . $action);

  // main error handler
} 
catch (Exception $e) 
{
    // Return error in JSON format
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}

?>
