<?php
/**
 *  MEMBER DASHBOARD - MAJLISH E SARKAR
 *  This page is shown only to MEMBERS (not admins).
 *  Responsibilities:
 * validate user session
 * prevent admin users from entering (redirect them)
 * render UI for meals, menus, attendance, history, statistics
 * load dynamic data using JavaScript + API
 */

require_once "configuration.php"; // Load core system config, session, DB, helpers



// REQUIRE USER TO BE LOGGED IN
// iff user is NOT logged in - redirect to login page.
login_Need();

//block admins from this page
//admins have their own dashboard: index(admin.php)
//if an admin somehow enters this url manually, redirect them
if (isAdmin()) {
    header("Location: index(admin).php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!--ensures correct text encoding-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- the layout responsive on mobile also-->
    <title>Member Dashboard - Majlish E Sarkar</title>
    <style>


  /* this removes default browser margin/padding for consistent layout
  box sizing: border box ensures padding and border stay inside set width/height
  */
        * { 
            margin: 0;
             padding: 0;
              box-sizing: border-box; 
            }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1E1E1E;
            color: #F5F5F5;
            min-height: 100vh; /* light text color for contrast*/
        }
        

        /* 
            NAVBAR (Top Bar with Logo + Logout Button)
       
            Sticky top appearance
            Gradient background
            Orange border accent
            Flex layout for left-right alignment
        */
        .navbar {
            background: linear-gradient(135deg, #2B2B2B 0%, #1E1E1E 100%);
            color: #F5F5F5;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            border-bottom: 2px solid #FF8C42;
        }
        
        .navbar h1 { 
            font-size: 28px;
            background: linear-gradient(135deg, #FF8C42 0%, #FFB366 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar .user-info { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        
        .navbar .user-info span {
            color: #F5F5F5;
            font-weight: 500;
        }

        /* LOGOUT BUTTON */
        .btn-logout {
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 66, 0.5);
        }
        /*
         wrapper / main container
         */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        /* 
           STATS GRID (Top Statistics Section)
         */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        /* stat cards: box
        gradient dark bg
        glow border on hover
        orange top accent stripe
        */
        .stat-card {
            background: linear-gradient(135deg, #2B2B2B 0%, #242424 100%);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            border: 1px solid rgba(255, 140, 66, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        /* Orange accent line at top of card */
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #FF8C42 0%, #FFB366 100%);
        }

        /* Hover effect */
        .stat-card:hover {
            transform: translateY(-5px);
        /*    box-shadow: 0 12px 32px rgba(255, 140, 66, 0.2); */
            border-color: rgba(255, 140, 66, 0.3);
        }
        
        .stat-card h3 {
            color: #B0B0B0;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Number/value display */
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, #FF8C42 0%, #FFB366 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card.balance-positive .value { 
            background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card.balance-negative .value { 
            background: linear-gradient(135deg, #F44336 0%, #E57373 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /* generic card container (menu, attendence , history) */
        .card {
            background: linear-gradient(135deg, #2B2B2B 0%, #242424 100%);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 140, 66, 0.1);
        }
        /*card title*/
        .card h2 {
            margin-bottom: 20px;
            color: #F5F5F5;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            border-bottom: 2px solid #FF8C42;
            padding-bottom: 12px;
        }

        /* date display(used in today's menu) */
        .date-display {
            background: #1E1E1E;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            color: #FF8C42;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        /*today's menu section */
        .menu-display {
            background: #1E1E1E;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 140, 66, 0.1);
        }
        /*individual menu rows */
        .menu-item {
            padding: 15px;
            margin-bottom: 12px;
            background: #2B2B2B;
            border-left: 4px solid #FF8C42;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateX(5px);
            border-left-width: 6px;
        }
        
        .menu-item strong {
            color: #FF8C42;
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .menu-item:last-child {
            margin-bottom: 0;
        }

        /*meal attendence checkbox*/
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            padding: 18px;
            background: #1E1E1E;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
            position: relative;
            border: 2px solid rgba(255, 140, 66, 0.2);
        }
        
        .checkbox-wrapper:hover { 
            background: #242424;
            border-color: #FF8C42;
            transform: translateX(5px);
        }

        /* when cutoff passed */
        .checkbox-wrapper.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: #3A3A3A;
        }
        
        .checkbox-wrapper.disabled:hover {
            transform: none;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-right: 15px;
            cursor: pointer;
            accent-color: #FF8C42;
        }
        
        .checkbox-wrapper.disabled input[type="checkbox"] {
            cursor: not-allowed;
        }
        
        .checkbox-wrapper label {
            flex: 1;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            color: #F5F5F5;
        }
        
        .checkbox-wrapper.disabled label {
            cursor: not-allowed;
            color: #B0B0B0;
        }

        /* Quantity input beside checkbox */
        .qty-input {
            width: 70px;
            padding: 8px;
            border: 2px solid #3A3A3A;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            background: #2B2B2B;
            color: #FF8C42;
            transition: all 0.3s;
        }
        
        .qty-input:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }
        /* Cutoff badge inside attendance blocks */
        .cutoff-warning {
            position: absolute;
            right: 100px;
            background: linear-gradient(135deg, #F44336 0%, #E57373 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }
        /* primary button(save attendence) */
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 66, 0.5);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #B0B0B0;
            font-size: 16px;
        }
        
        .meals-breakdown {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .meals-breakdown .stat-card {
            text-align: center;
        }
        
        .history-table {
            max-height: 400px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1E1E1E;
            border-radius: 12px;
            overflow: hidden;
        }
        
        table th {
            background: linear-gradient(135deg, #2B2B2B 0%, #242424 100%);
            padding: 14px;
            text-align: left;
            font-weight: 600;
            color: #FF8C42;
            position: sticky;
            top: 0;
            border-bottom: 2px solid #FF8C42;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        table td {
            padding: 14px;
            border-bottom: 1px solid #2B2B2B;
            color: #F5F5F5;
        }
        
        table tr:hover {
            background: rgba(255, 140, 66, 0.05);
        }

        /* Scrollbar styling (orange theme) */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #1E1E1E;
        }

        ::-webkit-scrollbar-thumb {
            background: #FF8C42;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #FFB366;
        }
        /* 
           POPUP NOTIFICATION (Bottom Alert for cutoff timers)
        */
        .popup-notice 
        {
    position: fixed;
    bottom: -80px;
    left: 50%;
    transform: translateX(-50%);
    background: #ff3b3b;
    color: white;
    padding: 15px 25px;
    font-size: 14px;
    border-radius: 10px;
    transition: 0.4s;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    z-index: 9999;
}
.popup-notice.show {
    bottom: 20px;
}
/* Cutoff special colors */
.cutoff-warning {
    background: #ff9800 !important;
    color: black !important;
}
.cutoff-passed {
    background: #444 !important;
    color: #bbb !important;
}

    </style>
</head>
<body>
    <!-- 
     NAVBAR (Top bar with logo + user name + logout button)
    -->
    <div class="navbar">
        <h1>🍽️ Majlish E Sarkar</h1>
        <div class="user-info"><!-- Display logged-in user name -->

            <span>Welcome, <?php echo e(getUserName()); ?></span>

            <!-- Logout button -->
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- 
     MAIN PAGE CONTAINER
     This wrapper limits page width to 1400px and centers all content.
      -->
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">

            <!-- Monthly Meals -->
            <div class="stat-card">
                <h3>This Month</h3>
                <div class="value" id="monthlyMeals">-</div>
            </div>

             <!-- Monthly costs -->
            <div class="stat-card">
                <h3>Monthly Cost</h3>
                <div class="value" id="monthlyCost">-</div>
            </div>
        <!-- total deposited -->

            <div class="stat-card">
                <h3>Deposited</h3>
                <div class="value" id="totalDeposited">-</div>
            </div>
            <!-- Balance (Positive = Green, Negative = Red) -->
            <div class="stat-card" id="balanceCard">
                <h3>Balance</h3>
                <div class="value" id="currentBalance">-</div>
            </div>
        </div>

        <!-- Today's Meals Breakdown -->
        <div class="card">
            <h2>📊 Today's Meals</h2>
            <div class="meals-breakdown">
                <div class="stat-card">
                    <h3>🌅 Breakfast</h3>
                    <div class="value" id="todayBreakfast">-</div>
                </div>
                <div class="stat-card">
                    <h3>🍛 Lunch</h3>
                    <div class="value" id="todayLunch">-</div>
                </div>
                <div class="stat-card">
                    <h3>🌙 Dinner</h3>
                    <div class="value" id="todayDinner">-</div>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <div class="card">
            <h2>📈 This Month's Breakdown</h2>
            <div class="meals-breakdown">
                <div class="stat-card">
                    <h3>🌅 Breakfast</h3>
                    <div class="value" id="monthlyBreakfast">-</div>
                </div>
                <div class="stat-card">
                    <h3>🍛 Lunch</h3>
                    <div class="value" id="monthlyLunch">-</div>
                </div>
                <div class="stat-card">
                    <h3>🌙 Dinner</h3>
                    <div class="value" id="monthlyDinner">-</div>
                </div>
            </div>
        </div>



        <!-- Today's Menu -->
        <div class="card">
            <h2>📋 Today's Menu</h2>
            <div class="date-display" id="currentDate"></div>
            <div id="todayMenu" class="loading">Loading menu...</div>
        </div>

        <!-- Meal Attendance -->
        <div class="card">
            <h2>✅ Mark Your Meals</h2>
            <form id="attendanceForm" onsubmit="saveAttendance(event)">

            <!--breakfast block-->
                <div class="checkbox-wrapper" id="breakfast-wrapper">
                    <input type="checkbox" id="breakfast" name="breakfast">
                    <label for="breakfast">🌅 I'll eat breakfast</label>
                    <span class="cutoff-warning" id="breakfast-cutoff" style="display:none;">Cutoff passed</span>
                    <input type="number" id="breakfast_qty" name="breakfast_qty" min="1" max="5" value="1" class="qty-input">
                </div>
                <!--lunch block-->

                <div class="checkbox-wrapper" id="lunch-wrapper">
                    <input type="checkbox" id="lunch" name="lunch">
                    <label for="lunch">🍛 I'll eat lunch</label>
                    <span class="cutoff-warning" id="lunch-cutoff" style="display:none;">Cutoff passed</span>
                    <input type="number" id="lunch_qty" name="lunch_qty" min="1" max="5" value="1" class="qty-input">
                </div>
                
                <div class="checkbox-wrapper" id="dinner-wrapper">
                    <input type="checkbox" id="dinner" name="dinner">
                    <label for="dinner">🌙 I'll eat dinner</label>
                    <span class="cutoff-warning" id="dinner-cutoff" style="display:none;">Cutoff passed</span>
                    <input type="number" id="dinner_qty" name="dinner_qty" min="1" max="5" value="1" class="qty-input">
                </div>
                
                <button type="submit" class="btn btn-primary" id="saveBtn">💾 Save Attendance</button>
            </form>
        </div>

        <!-- Meals History -->
        <div class="card">
            <h2>📜 Meal History (Last 30 Days)</h2>
            <div class="history-table">
                <div id="mealsHistory" class="loading">Loading history...</div>
            </div>
        </div>
    </div>

    <script>
        

/* 
   FUNCTION: bdNow()
   Returns current time in Bangladesh timezone.
   The server may run different timezone, so we convert to Asia/Dhaka.
*/  
function bdNow() 
{
    return new Date(new Date().toLocaleString("en-US", { timeZone: "Asia/Dhaka" }));
}

/* 
   FUNCTION: showPopup(message)
   Shows a sliding popup alert (used for cutoff countdown notifications).
*/
function showPopup(msg) 
{
    const p = document.getElementById("popupNotice");
    p.textContent = msg;
    p.classList.add("show");

    // Auto-hide after 4 seconds
    setTimeout(() => p.classList.remove("show"), 4000);
}
/* 
AUTO-FILL BREAKFAST BEFORE 6AM
If user opens dashboard early morning, automatically check breakfast.
  */
function autoFillBreakfast() 
{
    const now = bdNow();
    const hour = now.getHours();
    const chk = document.getElementById("breakfast");

    if (hour < 6 && chk && !chk.checked) {
        chk.checked = true;
    }
}
/* 
   LOAD DASHBOARD STATISTICS
   API: action=dashboard_stats
   Loads:
    Monthly breakfast/lunch/dinner
    Monthly total meals
    Monthly cost
    Today’s meals
    Balance + Deposited amount
*/
async function loadStats() 
{
    try {
        const res = await fetch("api.php?action=dashboard_stats");
        const d = await res.json();
        if (!d.success) return;
        
        // Update monthly stats
        document.getElementById("monthlyMeals").textContent = d.data.monthly_meals;
        document.getElementById("monthlyCost").textContent = "৳" + d.data.monthly_cost;
        document.getElementById("totalDeposited").textContent = "৳" + d.data.total_deposited;

        document.getElementById("monthlyBreakfast").textContent = d.data.monthly_breakfast;
        document.getElementById("monthlyLunch").textContent = d.data.monthly_lunch;
        document.getElementById("monthlyDinner").textContent = d.data.monthly_dinner;
        
        // Today's breakdown
        document.getElementById("todayBreakfast").textContent = d.data.today_breakfast;
        document.getElementById("todayLunch").textContent = d.data.today_lunch;
        document.getElementById("todayDinner").textContent = d.data.today_dinner;
        
        // Balance styling (positive=green, negative=red)
        const balance = d.data.balance;
        const card = document.getElementById("balanceCard");
        card.className = balance >= 0 ? "stat-card balance-positive" : "stat-card balance-negative";
        document.getElementById("currentBalance").textContent = "৳" + balance;

    } catch (e) 
    {
        console.error("Stats error:", e);
    }
}
/* 
   LOAD TODAY'S MENU
   API: action=get_today_menu
   Loads menu for:
      Breakfast
      Lunch
      Dinner
 */
async function loadTodayMenu() 
{
    try {
        const r = await fetch("api.php?action=get_today_menu");
        const d = await r.json();
        const menuDiv = document.getElementById("todayMenu");

        if (!d.success || !d.data) {
            menuDiv.innerHTML = '<p style="color:#B0B0B0;text-align:center;">No menu set</p>';
            return;
        }

        const m = d.data;
        menuDiv.innerHTML = `
            <div class="menu-item"><strong>🌅 Breakfast</strong>${m.breakfast || "Not set"}</div>
            <div class="menu-item"><strong>🍛 Lunch</strong>${m.lunch || "Not set"}</div>
            <div class="menu-item"><strong>🌙 Dinner</strong>${m.dinner || "Not set"}</div>
        `;
        // Display current date on menu card
        document.getElementById("currentDate").textContent =
            bdNow().toISOString().split("T")[0];

    } catch (e) {
        console.error("Menu error:", e);
    }
}

let cutoffStatus = { breakfast: true, lunch: true, dinner: true };

async function loadMyAttendance() {
    try {
        const r = await fetch("api.php?action=get_my_attendance");
        const d = await r.json();
        if (!d.success) return;

        const att = d.data;
        cutoffStatus = d.cutoff_status;

        ["breakfast", "lunch", "dinner"].forEach(meal => {
            const chk = document.getElementById(meal);
            const qty = document.getElementById(meal + "_qty");
            const wrap = document.getElementById(meal + "-wrapper");
            const warn = document.getElementById(meal + "-cutoff");

            if (att[meal]) {
                chk.checked = att[meal].status === "yes";
                qty.value = att[meal].quantity;
            }

            if (!cutoffStatus[meal]) {
                wrap.classList.add("disabled");
                chk.disabled = true;
                qty.disabled = true;
                warn.style.display = "block";
            }
        });

        document.getElementById("saveBtn").disabled =
            !cutoffStatus.breakfast && !cutoffStatus.lunch && !cutoffStatus.dinner;

    } catch (e) {
        console.error("Attendance error:", e);
    }
}

async function saveAttendance(e) {
    e.preventDefault();

    const meals = ["breakfast", "lunch", "dinner"];
    let saved = 0;
    let errors = [];

    for (const meal of meals) {
        if (!cutoffStatus[meal]) continue;

        const chk = document.getElementById(meal);
        const qty = document.getElementById(meal + "_qty");

        const fd = new FormData();
        fd.append("action", "mark_attendance");
        fd.append("meal_type", meal);
        fd.append("status", chk.checked ? "yes" : "no");
        fd.append("quantity", qty.value);

        try {
            const res = await fetch("api.php", { method: "POST", body: fd });
            const d = await res.json();
            if (d.success) saved++;
            else errors.push(meal + ": " + d.message);
        } catch {
            errors.push(meal + ": Network error");
        }
    }

    if (errors.length) {
        alert("⚠️ Some failed:\n" + errors.join("\n"));
    } else if (saved > 0) {
        alert("✅ Attendance saved!");
    } else {
        alert("⚠️ Past cutoff — nothing saved");
    }

    loadStats();
    loadMyAttendance();
}

async function loadMealsHistory() {
    try {
        const r = await fetch("api.php?action=get_my_meals_history");
        const d = await r.json();
        const div = document.getElementById("mealsHistory");

        if (!d.success || d.data.length === 0) {
            div.innerHTML = '<p style="color:#B0B0B0;text-align:center;">No history found</p>';
            return;
        }

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>🌅</th><th>🍛</th><th>🌙</th><th>Total</th>
                    </tr>
                </thead>
                <tbody>
        `;

        d.data.forEach(r => {
            html += `
                <tr>
                    <td>${r.date}</td>
                    <td>${r.breakfast}</td>
                    <td>${r.lunch}</td>
                    <td>${r.dinner}</td>
                    <td><strong>${r.total}</strong></td>
                </tr>
            `;
        });

        html += "</tbody></table>";
        div.innerHTML = html;

    } catch (e) {
        console.error("History error:", e);
    }
}

function finalCutoffSystem(meal, cutoff) {
    const chk = document.getElementById(meal);
    const box = document.getElementById(meal + "_cutoff_badge");
    const timer = document.getElementById(meal + "_cutoff_timer");

    function loop() {
        const now = bdNow();
        const today = now.toISOString().split("T")[0];
        const cutoffDate = new Date(`${today}T${cutoff}:00`);
        const diff = cutoffDate - now;

        if (diff <= 0) {
            chk.disabled = true;
            if (box) box.textContent = "Closed";
            if (box) box.className = "cutoff-passed";
            if (timer) timer.textContent = "";
            return;
        }

        const m = Math.floor(diff / 60000);
        const s = Math.floor((diff % 60000) / 1000);

        if (timer) timer.textContent = `${m}m ${s}s`;

        if (m <= 10 && m > 0) {
            showPopup(`⚠️ ${meal.toUpperCase()} cutoff in ${m} minutes`);
        }

        setTimeout(loop, 1000);
    }

    loop();
}

async function loadSettings() {
    const r = await fetch("api.php?action=get_settings");
    const d = await r.json();
    if (!d.success) return;

    const s = d.data;

    if (s.breakfast_cutoff) finalCutoffSystem("breakfast", s.breakfast_cutoff);
    if (s.lunch_cutoff) finalCutoffSystem("lunch", s.lunch_cutoff);
    if (s.dinner_cutoff) finalCutoffSystem("dinner", s.dinner_cutoff);
}

document.addEventListener("DOMContentLoaded", () => {
    loadStats();
    loadTodayMenu();
    loadMyAttendance();
    loadMealsHistory();
    loadSettings();
    autoFillBreakfast();

    setInterval(() => loadMyAttendance(), 300000);
});
</script>
    <div id="popupNotice" class="popup-notice"></div>

</body>
</html>