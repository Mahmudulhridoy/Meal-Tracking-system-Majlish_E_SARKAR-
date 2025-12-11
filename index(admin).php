<?php
/**
 *  ADMIN DASHBOARD — ACCESS CONTROL LAYER
 *  This page is ONLY for administrators.
 *  Before loading any dashboard content, we:
 * 1. Load global configuration + functions
 * 2. Ensure the user is logged in
 * 3. Ensure the user has admin privileges
 *  If these checks fail → redirect away immediately.
 */
require_once 'configuration.php';// Load session, database, and helper functions
login_Need(); // If user is not logged in - redirect to login



// If the logged-in user is NOT an admin, redirect them to member dashboard.

if (!isAdmin()) {
    header("Location: index(member).php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Majlish E Sarkar</title>
<style>
    
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #1E1E1E; /* Dark theme background */
            color: #F5F5F5;  /* Light text for contrast */
        }

        /* 
            NAVBAR — FIXED TOP HEADER (ADMIN TITLE + LOGOUT)
         */
        .navbar { 
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            color: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 20px rgba(255, 140, 66, 0.3); /* soft orange glow */
        }
        
        .navbar h1 { 
            font-size: 24px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2); /*light emboosed effect*/
        }
        
        .navbar .user-info { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        
        .btn-logout { 
            background: rgba(255,255,255,0.2); 
            color: white; 
            padding: 8px 20px; 
            border: 1px solid rgba(255,255,255,0.3); 
            border-radius: 8px; 
            cursor: pointer; 
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px); /*frosted glass effect */
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px); /*lift animation */
        }
        
        .container { 
            max-width: 1400px; 
            margin: 30px auto; 
            padding: 0 20px; 
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }

        /* Individual statistic card */
        .stat-card { 
            background: #2B2B2B;
            padding: 25px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border: 1px solid #3A3A3A; /*soft border */
            transition: all 0.3s ease;
        }
        /* Hover animation for stat cards */
        .stat-card:hover {
            transform: translateY(-5px);
            /*box-shadow: 0 8px 30px rgba(255, 140, 66, 0.2); */
            border-color: #FF8C42;
        }
        
        .stat-card h3 { 
            color: #B0B0B0;
            font-size: 13px; 
            font-weight: 500; 
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Big number display */
        .stat-card .value { 
            font-size: 32px; 
            font-weight: bold; 
            color: #FF8C42;
            text-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
        }
        
        .stat-card.meals-breakdown { 
            grid-column: span 2;  /* Wider layout for the meal breakdown section */
        }
        
        .meals-breakdown-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; 
            margin-top: 15px; 
        }
        /*individual meal block */
        .meal-item { 
            background: #1E1E1E;
            padding: 15px; 
            border-radius: 12px; 
            text-align: center;
            border: 1px solid #3A3A3A;
            transition: all 0.3s ease;
        }
        
        .meal-item:hover {
            border-color: #FF8C42;
            transform: scale(1.05);
        }
        
        .meal-item .label { 
            font-size: 12px; 
            color: #B0B0B0;
            margin-bottom: 8px;
        }
        
        .meal-item .count { 
            font-size: 28px; 
            font-weight: bold; 
            color: #FF8C42;
        }
        /*  
    TAB SYSTEM — Top Navigation for Sections
    (Members , Payments, Balances , Menus , Settings)
*/
        .tabs { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #3A3A3A;
            flex-wrap: wrap; /* Prevents breaking layout on small screens */
        }
        
        .tab-btn { 
            padding: 12px 24px; 
            background: transparent; 
            border: none; 
            border-bottom: 3px solid transparent; /*underlinde appear when active */
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 500; 
            color: #B0B0B0;
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover {
            color: #FF8C42; 
        }
        
        .tab-btn.active { 
            color: #FF8C42;
            border-bottom-color: #FF8C42; /* Active underline */
        }
        
        .tab-content { 
            display: none;  /* Hidden by default */
        }
        
        .tab-content.active { 
            display: block; /* Shown only for active tab */

        }
        /*card component universal box for all panels */
        .card { 
            background: #2B2B2B;
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            border: 1px solid #3A3A3A;
        }
        
        .card h2 { 
            margin-bottom: 20px; 
            color: #F5F5F5;
            font-size: 22px;
        }
        
        .card h3 {
            color: #FF8C42;
        }
        
        .form-group { 
            margin-bottom: 15px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            color: #B0B0B0;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #3A3A3A;
            border-radius: 8px; 
            font-size: 14px;
            background: #1E1E1E;
            color: #F5F5F5;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }
        
        .form-group textarea { 
            resize: vertical; 
            min-height: 80px; 
        }
        
        .form-group small {
            color: #808080;
        }
        
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
        }
        
        .btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500; 
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .btn-primary { 
            background: #FF8C42;
            color: white;
        }
        
        .btn-primary:hover {
            background: #FF7A2E;
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
        }
        
        .btn-success { 
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }
        
        .btn-danger { 
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }
        
        .btn-edit { 
            background: #4CAF50;
            color: white;
        }
        
        .btn-secondary { 
            background: #5A5A5A;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #6A6A6A;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        table th { 
            background: #1E1E1E;
            padding: 12px; 
            text-align: left; 
            font-weight: 600; 
            color: #FF8C42;
            border-bottom: 2px solid #3A3A3A;
        }
        
        table td { 
            padding: 12px; 
            border-bottom: 1px solid #3A3A3A;
            color: #F5F5F5;
        }
        
        table tr:hover {
            background: #1E1E1E;
        }
        
        .status-active { 
            color: #4CAF50;
            font-weight: 500;
            background: rgba(76, 175, 80, 0.1);
            padding: 4px 12px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .status-inactive { 
            color: #f44336;
            font-weight: 500;
            background: rgba(244, 67, 54, 0.1);
            padding: 4px 12px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .loading { 
            text-align: center; 
            padding: 20px; 
            color: #808080;
        }
        
        .balance-positive { 
            color: #4CAF50;
            font-weight: bold;
        }
        
        .balance-negative { 
            color: #f44336;
            font-weight: bold;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid #f44336;
        }

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
            background: #FF7A2E;
        }
    

</style>
</head>

<body>
    <div class="navbar">
        <h1>🍽️ Majlish E Sarkar - Admin</h1>
        <div class="user-info">
            <!-- Showing admin's name pulled from session -->
            <span>Welcome, <?php echo e(getUserName()); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!--  
        DASHBOARD STATISTICS SECTION  
         Total Members
         Total Meals Today
         Revenue
         Net Balance
         Today's Breakfast/Lunch/Dinner Breakdown
    -->
        <div class="stats-grid">

            <div class="stat-card">
                <h3>Total Members</h3>
                <div class="value" id="totalMembers">-</div>
            </div>

            <div class="stat-card">
                <h3>Today's Total Meals</h3>
                <div class="value" id="todayMeals">-</div>
            </div>

            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="value" id="totalRevenue">-</div>
            </div>

            <div class="stat-card">
                <h3>Net Balance</h3>
            <div class="value" id="netBalance">-</div>
        </div>

            <div class="stat-card meals-breakdown">

                <h3>Today's Meals Breakdown</h3>
                <div class="meals-breakdown-grid">
                    <div class="meal-item">
                        <div class="label">🌅 Breakfast</div>
                    <div class="count" id="todayBreakfast">-</div>
                </div>

                    <div class="meal-item">
                        <div class="label">🍛 Lunch</div>
                        <div class="count" id="todayLunch">-</div>
                    </div>

                    <div class="meal-item">
                        <div class="label">🌙 Dinner</div>
                        <div class="count" id="todayDinner">-</div>
                    </div>

                </div>
            </div>
        </div>



        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('members')">👥 Members</button>
            <button class="tab-btn" onclick="switchTab('payments')">💰 Payments</button>
            <button class="tab-btn" onclick="switchTab('balances')">📊 Balances</button>
            <button class="tab-btn" onclick="switchTab('menus')">🍽️ Menus</button>
            <button class="tab-btn" onclick="switchTab('settings')">⚙️ Settings</button>
        </div>




<!-- MEMBERS TAB -->
<div id="tab-members" class="tab-content active">

    <!-- Form to Add/Edit Members -->
    <div class="card">
        <h2>Member Management</h2>
        <div id="memberAlert"></div>

        <form id="memberForm" onsubmit="saveMember(event)">
        
        
        <!-- Hidden field used for EDITING -->
            <input type="hidden" id="member_id" name="member_id">
            
            <div class="form-grid">
                
                <div class="form-group"><label>Name *</label><input type="text" name="name" id="name" required>
            </div>

                <div class="form-group"><label>Email *</label><input type="email" name="email" id="email" required>
            </div>

                <div class="form-group"><label>Password *</label><input type="password" name="password" id="password"><small>Leave blank when editing</small>
            </div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" id="phone">
            </div>
                <div class="form-group"><label>Room *</label><input type="text" name="room" id="room" required>
            </div>
                
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="memberStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select></div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" id="address">
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Member</button>
            <button type="button" class="btn btn-secondary" onclick="clearMemberForm()">Clear Form</button>
        </form>
    </div>

    <div class="card">
        <h2>All Members</h2>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Room</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="membersTableBody"><tr><td colspan="6" class="loading">Loading...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- PAYMENTS TAB -->
<div id="tab-payments" class="tab-content">
    <div class="card">
        <h2>Add Payment/Deposit</h2>
        <div id="paymentAlert"></div>
        <form id="paymentForm" onsubmit="savePayment(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Member *</label>
                    <select name="member_id" id="payment_member_id" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (৳) *</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Payment Date *</label>
                    <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group"><label>Payment Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank Transfer</option><option value="other">Other</option></select></div>
            </div>
            <div class="form-group">
                <label>Reference Number</label>
                <input type="text" name="reference_number">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes"></textarea>
            </div>
            <button type="submit" class="btn btn-success">💰 Add Payment</button>
        </form>
    </div>

    <div class="card">
        <h2>Recent Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Room</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="paymentsTableBody">
                <tr>
                    <td colspan="7" class="loading">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- BALANCES -->
<div id="tab-balances" class="tab-content">
    <div class="card">
        <h2>Member Balances & Meal Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Room</th>
                    <th>Breakfast</th>
                    <th>Lunch</th>
                    <th>Dinner</th>
                    <th>Total</th>
                    <th>Deposited</th>
                    <th>Cost</th>
                    <th>Balance</th>
                </tr></thead>
            <tbody id="balancesTableBody">
                <tr>
                    <td colspan="9" class="loading">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- MENUS -->
<div id="tab-menus" class="tab-content">
    <div class="card">
        <h2>Menu Management</h2>

        <div id="menuAlert"></div>

        <form id="menuForm" onsubmit="saveMenu(event)">
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Breakfast</label>
                <textarea name="breakfast"></textarea>
            </div>

            <div class="form-group">
                <label>Lunch</label>
                <textarea name="lunch"></textarea>
            </div>

            <div class="form-group">
                <label>Dinner</label>
                <textarea name="dinner"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">🍽️ Save Menu</button>
        </form>
    </div>

    <div class="card">
        <h2>Recent Menus</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Breakfast</th>
                    <th>Lunch</th>
                    <th>Dinner</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody id="menusTableBody">
                <tr>
                    <td colspan="5" class="loading">Loading...</td>
                </tr>
            </tbody>

        </table>
    </div>
</div>

<!-- SETTINGS -->
<div id="tab-settings" class="tab-content">
    <div class="card">
        <h2>System Settings</h2>
        <div id="settingsAlert"></div>
        <form id="settingsForm" onsubmit="saveSettings(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Cost Per Meal (৳)</label>
                    <input type="number" name="cost_per_meal" step="0.01">
                </div>
                <div class="form-group">
                    <label>Hostel Name</label>
                    <input type="text" name="hostel_name">
                </div>

            </div>
            <h3 style="margin: 20px 0 10px 0;">Meal Cutoff Times</h3>
            <div class="form-grid">

                <div class="form-group">
                    <label>Breakfast</label>
                    <input type="time" name="breakfast_cutoff">
                </div>

                <div class="form-group">
                    <label>Lunch</label>
                    <input type="time" name="lunch_cutoff">
                </div>

                <div class="form-group">
                    <label>Dinner</label>
                    <input type="time" name="dinner_cutoff">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Save Settings</button>
        </form>
    </div>
</div>





<script>

/*
    SWITCH BETWEEN TABS
    - Hides all tab contents
    - Removes active class from all buttons
    - Activates the selected tab + loads required data
*/
function switchTab(tabName) {
        // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        // Hide all tab content sections
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
     // Activate selected button + tab section
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');

    if (tabName === 'payments') loadPayments();
    if (tabName === 'balances') loadBalances();
    if (tabName === 'menus') loadMenus();
}





function showAlert(id, message, type) {
    const el = document.getElementById(id);
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => el.innerHTML = "", 4000);
}






async function loadDashboard() {
    const res = await fetch("api.php?action=dashboard_stats");
    const d = await res.json();
    if (!d.success) return;

    totalMembers.textContent = d.data.total_members;
    todayMeals.textContent = d.data.today_meals;
    todayBreakfast.textContent = d.data.today_breakfast;
    todayLunch.textContent = d.data.today_lunch;
    todayDinner.textContent = d.data.today_dinner;
    totalRevenue.textContent = "৳" + d.data.total_revenue;
    netBalance.textContent = "৳" + d.data.net_balance;
}






async function loadMembers() {
    const res = await fetch("api.php?action=get_members");
    const d = await res.json();
    if (!d.success) return;

    const tbody = document.getElementById("membersTableBody");
    tbody.innerHTML = "";
    const paymentSelect = document.getElementById("payment_member_id");
    paymentSelect.innerHTML = `<option value="">Select Member</option>`;

    d.data.forEach(m => {
        tbody.innerHTML += `
        <tr>
            <td>${m.name}</td>
            <td>${m.email}</td>
            <td>${m.phone || '-'}</td>
            <td>${m.room}</td>
            <td><span class="status-${m.status}">${m.status.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-edit" onclick='editMember(${JSON.stringify(m)})'>Edit</button>
                <button class="btn btn-danger" onclick="deleteMember(${m.id}, '${m.name}')">Delete</button>
            </td>
        </tr>`;

        paymentSelect.innerHTML += `<option value="${m.member_id}">${m.name} (${m.room})</option>`;
    });
}







async function saveMember(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append("action", "add_member");

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("memberAlert", d.message, "success");
        clearMemberForm();
        loadMembers();
        loadDashboard();
    } else showAlert("memberAlert", d.message, "error");
}






function editMember(m) {
    member_id.value = m.id;
    name.value = m.name;
    email.value = m.email;
    phone.value = m.phone || "";
    room.value = m.room;
    address.value = m.address || "";
    memberStatus.value = m.status;
    password.value = "";
    password.removeAttribute("required");
}





function clearMemberForm() {
    memberForm.reset();
    member_id.value = "";
    password.setAttribute("required", "required");
}






async function deleteMember(id, name) {
    if (!confirm(`Delete member "${name}"?`)) return;

    const fd = new FormData();
    fd.append("action", "delete_member");
    fd.append("id", id);

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("memberAlert", d.message, "success");
        loadMembers();
        loadDashboard();
    } else showAlert("memberAlert", d.message, "error");
}






async function loadPayments() {
    const res = await fetch("api.php?action=get_payments");
    const d = await res.json();
    const tbody = paymentsTableBody;
    tbody.innerHTML = "";

    if (!d.success || d.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="loading">No payments</td></tr>`;
        return;
    }

    d.data.forEach(p => {
        tbody.innerHTML += `
        <tr>
            <td>${p.payment_date}</td>
            <td>${p.member_name}</td>
            <td>${p.room}</td>
            <td>৳${p.amount}</td>
            <td>${p.payment_method}</td>
            <td>${p.reference_number || "-"}</td>
            <td><button class="btn btn-danger" onclick="deletePayment(${p.id})">Delete</button></td>
        </tr>`;
    });
}







async function savePayment(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append("action", "add_payment");

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("paymentAlert", d.message, "success");
        e.target.reset();
        loadPayments();
        loadDashboard();
    } else showAlert("paymentAlert", d.message, "error");
}







async function deletePayment(id) {
    if (!confirm("Delete this payment?")) return;

    const fd = new FormData();
    fd.append("action", "delete_payment");
    fd.append("id", id);

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("paymentAlert", d.message, "success");
        loadPayments();
        loadDashboard();
    } else showAlert("paymentAlert", d.message, "error");
}







async function loadBalances() {
    const res = await fetch("api.php?action=get_all_balances");
    const d = await res.json();
    const tbody = balancesTableBody;
    tbody.innerHTML = "";

    if (!d.success) {
        tbody.innerHTML = `<tr><td colspan="9" class="loading">No data</td></tr>`;
        return;
    }

    d.data.forEach(b => {
        const cls = parseFloat(b.balance) >= 0 ? "balance-positive" : "balance-negative";
        tbody.innerHTML += `
        <tr>
            <td>${b.name}</td>
            <td>${b.room}</td>
            <td>${b.breakfast_count}</td>
            <td>${b.lunch_count}</td>
            <td>${b.dinner_count}</td>
            <td>${b.total_meals_count}</td>
            <td>৳${b.total_deposited}</td>
            <td>৳${b.total_meal_cost}</td>
            <td class="${cls}">৳${b.balance}</td>
        </tr>`;
    });
}






async function loadMenus() {
    const res = await fetch("api.php?action=get_menus");
    const d = await res.json();
    const tbody = menusTableBody;
    tbody.innerHTML = "";

    if (!d.success || d.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="loading">No menus</td></tr>`;
        return;
    }

    d.data.forEach(m => {
        tbody.innerHTML += `
        <tr>
            <td>${m.date}</td>
            <td>${m.breakfast || "-"}</td>
            <td>${m.lunch || "-"}</td>
            <td>${m.dinner || "-"}</td>
            <td><button class="btn btn-danger" onclick="deleteMenu(${m.id})">Delete</button></td>
        </tr>`;
    });
}







async function saveMenu(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append("action", "save_menu");

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("menuAlert", d.message, "success");
        loadMenus();
    } else showAlert("menuAlert", d.message, "error");
}







async function deleteMenu(id) {
    if (!confirm("Delete this menu?")) return;

    const fd = new FormData();
    fd.append("action", "delete_menu");
    fd.append("id", id);

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) {
        showAlert("menuAlert", d.message, "success");
        loadMenus();
    } else showAlert("menuAlert", d.message, "error");
}




async function loadSettings() {
    const res = await fetch("api.php?action=get_settings");
    const d = await res.json();
    if (!d.success) return;

    document.querySelector("[name=cost_per_meal]").value = d.data.cost_per_meal || "";
    document.querySelector("[name=hostel_name]").value = d.data.hostel_name || "";
    document.querySelector("[name=breakfast_cutoff]").value = d.data.breakfast_cutoff || "";
    document.querySelector("[name=lunch_cutoff]").value = d.data.lunch_cutoff || "";
    document.querySelector("[name=dinner_cutoff]").value = d.data.dinner_cutoff || "";
}

async function saveSettings(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append("action", "update_settings");

    const res = await fetch("api.php", { method: "POST", body: fd });
    const d = await res.json();

    if (d.success) showAlert("settingsAlert", d.message, "success");
    else showAlert("settingsAlert", d.message, "error");
}

document.addEventListener("DOMContentLoaded", () => {
    loadDashboard();
    loadMembers();
    loadSettings();
});
</script>

</body>
</html>
