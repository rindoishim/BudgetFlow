<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetFlow - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">BudgetFlow</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <div class="user-avatar"><?php echo $user_initial; ?></div>
                <form action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Balance Card -->
        <div class="balance-card">
            <div class="balance-label">Total Balance</div>
            <div class="balance-amount" id="totalBalance">₱0.00</div>
            <div class="balance-stats">
                <div class="stat-item">
                    <div class="stat-icon income">💰</div>
                    <div class="stat-info">
                        <h4>Income</h4>
                        <p id="totalIncome">₱0.00</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon expense">💸</div>
                    <div class="stat-info">
                        <h4>Expenses</h4>
                        <p id="totalExpense">₱0.00</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="action-btn income" onclick="openModal('income')">
                <span class="action-icon">+</span>
                <span>Add Income</span>
            </button>
            <button class="action-btn expense" onclick="openModal('expense')">
                <span class="action-icon">−</span>
                <span>Add Expense</span>
            </button>
        </div>

        <!-- Main Grid -->
        <div class="grid">
            <!-- Transactions Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Transactions</h2>
                </div>
                <div class="transaction-list" id="transactionList">
                    <p class="empty-state">No transactions yet</p>
                </div>
            </div>

            <!-- Budgets Section -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Budget Overview</h2>
                    <button class="btn-small" onclick="openBudgetModal()">Set Budget</button>
                </div>
                <div class="budget-list" id="budgetList">
                    <p class="empty-state">No budgets set</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Transaction</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="transactionForm">
                <input type="hidden" id="transactionId">
                
                <div class="form-group">
                    <label for="transactionType">Type</label>
                    <select id="transactionType" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="transactionCategory">Category</label>
                    <select id="transactionCategory" required>
                        <option value="Salary">Salary</option>
                        <option value="Food">Food</option>
                        <option value="Transport">Transport</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Bills">Bills</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="transactionAmount">Amount (₱)</label>
                    <input type="number" id="transactionAmount" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="transactionNote">Note (Optional)</label>
                    <input type="text" id="transactionNote" placeholder="Add a note...">
                </div>

                <div class="form-group">
                    <label for="transactionDate">Date</label>
                    <input type="date" id="transactionDate" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Budget Modal -->
    <div id="budgetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Set Budget</h2>
                <span class="close" onclick="closeBudgetModal()">&times;</span>
            </div>
            <form id="budgetForm">
                <div class="form-group">
                    <label for="budgetCategory">Category</label>
                    <select id="budgetCategory" required>
                        <option value="Food">Food</option>
                        <option value="Transport">Transport</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Bills">Bills</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="budgetAmount">Monthly Budget (₱)</label>
                    <input type="number" id="budgetAmount" step="0.01" min="0.01" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeBudgetModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Budget</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
