// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    loadBudgets();
    updateSummary();
    
    // Set default date to today
    document.getElementById('transactionDate').valueAsDate = new Date();
});

// CRUD Operations for Transactions

// CREATE/UPDATE Transaction
document.getElementById('transactionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const transactionId = document.getElementById('transactionId').value;
    const action = transactionId ? 'update' : 'create';
    
    const data = {
        type: document.getElementById('transactionType').value,
        category: document.getElementById('transactionCategory').value,
        amount: parseFloat(document.getElementById('transactionAmount').value),
        note: document.getElementById('transactionNote').value,
        date: document.getElementById('transactionDate').value
    };
    
    if (transactionId) {
        data.transaction_id = transactionId;
    }
    
    try {
        const response = await fetch(`api_transactions.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadTransactions();
            loadBudgets();
            updateSummary();
        } else {
            alert(result.error || 'Failed to save transaction');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});

// READ Transactions
async function loadTransactions() {
    try {
        const response = await fetch('api_transactions.php?action=get_all');
        const result = await response.json();
        
        const list = document.getElementById('transactionList');
        
        // Check if we got data
        const transactions = result.data || [];
        
        if (transactions.length === 0) {
            list.innerHTML = '<p class="empty-state">No transactions yet</p>';
            return;
        }
        
        list.innerHTML = transactions.map(transaction => {
            const isIncome = transaction.type === 'income';
            const amountClass = isIncome ? 'income' : 'expense';
            const amountPrefix = isIncome ? '+' : '-';
            
            return `
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-icon">${getCategoryIcon(transaction.category)}</div>
                        <div class="transaction-details">
                            <h4>${transaction.category}</h4>
                            <p>${transaction.note || 'No note'} • ${new Date(transaction.transaction_date).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="transaction-right">
                        <div class="transaction-amount ${amountClass}">
                            ${amountPrefix}₱${parseFloat(transaction.amount).toFixed(2)}
                        </div>
                        <div class="transaction-actions">
                            <button class="btn-icon edit" onclick="editTransaction(${transaction.transaction_id})" title="Edit">✏️</button>
                            <button class="btn-icon delete" onclick="deleteTransaction(${transaction.transaction_id})" title="Delete">🗑️</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading transactions:', error);
    }
}

// UPDATE - Edit Transaction
async function editTransaction(id) {
    try {
        const response = await fetch('api_transactions.php?action=get_all');
        const result = await response.json();
        const transactions = result.data || [];
        const transaction = transactions.find(t => t.transaction_id == id);
        
        if (transaction) {
            document.getElementById('modalTitle').textContent = 'Edit Transaction';
            document.getElementById('transactionId').value = transaction.transaction_id;
            document.getElementById('transactionType').value = transaction.type;
            document.getElementById('transactionCategory').value = transaction.category;
            document.getElementById('transactionAmount').value = transaction.amount;
            document.getElementById('transactionNote').value = transaction.note || '';
            document.getElementById('transactionDate').value = transaction.transaction_date;
            
            document.getElementById('transactionModal').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// DELETE Transaction
async function deleteTransaction(id) {
    if (!confirm('Are you sure you want to delete this transaction?')) {
        return;
    }
    
    try {
        const response = await fetch(`api_transactions.php?action=delete&id=${id}`, {
            method: 'GET'
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadTransactions();
            loadBudgets();
            updateSummary();
        } else {
            alert(result.error || 'Failed to delete transaction');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

// CRUD Operations for Budgets

// CREATE/UPDATE Budget
document.getElementById('budgetForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        category: document.getElementById('budgetCategory').value,
        amount: parseFloat(document.getElementById('budgetAmount').value),
        month: new Date().toISOString().slice(0, 7)
    };
    
    try {
        const response = await fetch('api_budgets.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeBudgetModal();
            loadBudgets();
        } else {
            alert(result.error || 'Failed to save budget');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});

// READ Budgets
async function loadBudgets() {
    try {
        const month = new Date().toISOString().slice(0, 7);
        const [budgetsResponse, spendingResponse] = await Promise.all([
            fetch(`api_budgets.php?action=get_all&month=${month}`),
            fetch(`api_budgets.php?action=get_spending&month=${month}`)
        ]);
        
        const budgetsResult = await budgetsResponse.json();
        const spendingResult = await spendingResponse.json();
        
        const budgets = budgetsResult.data || [];
        const spending = spendingResult.data || {};
        
        const list = document.getElementById('budgetList');
        
        if (budgets.length === 0) {
            list.innerHTML = '<p class="empty-state">No budgets set</p>';
            return;
        }
        
        list.innerHTML = budgets.map(budget => {
            const spent = spending[budget.category] || 0;
            const percentage = (spent / budget.amount) * 100;
            const progressClass = percentage > 90 ? 'danger' : percentage > 70 ? 'warning' : '';
            
            return `
                <div class="budget-item">
                    <div class="budget-header">
                        <span class="budget-name">${budget.category}</span>
                        <div style="display: flex; align-items: center;">
                            <span class="budget-amount">₱${spent.toFixed(2)} / ₱${parseFloat(budget.amount).toFixed(2)}</span>
                            <div class="budget-actions">
                                <button class="btn-icon delete" onclick="deleteBudget(${budget.budget_id})" title="Delete">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill ${progressClass}" style="width: ${Math.min(percentage, 100)}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading budgets:', error);
    }
}

// DELETE Budget
async function deleteBudget(id) {
    if (!confirm('Are you sure you want to delete this budget?')) {
        return;
    }
    
    try {
        const response = await fetch(`api_budgets.php?action=delete&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            loadBudgets();
        } else {
            alert(result.error || 'Failed to delete budget');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

// Update Summary
async function updateSummary() {
    try {
        const month = new Date().toISOString().slice(0, 7);
        const response = await fetch(`api_transactions.php?action=get_summary&month=${month}`);
        const result = await response.json();
        
        const summary = result.data || {income: 0, expense: 0, balance: 0};
        
        document.getElementById('totalBalance').textContent = `₱${summary.balance.toFixed(2)}`;
        document.getElementById('totalIncome').textContent = `₱${summary.income.toFixed(2)}`;
        document.getElementById('totalExpense').textContent = `₱${summary.expense.toFixed(2)}`;
    } catch (error) {
        console.error('Error updating summary:', error);
    }
}

// Modal Functions
function openModal(type) {
    const modal = document.getElementById('transactionModal');
    const typeSelect = document.getElementById('transactionType');
    const title = document.getElementById('modalTitle');
    
    document.getElementById('transactionForm').reset();
    document.getElementById('transactionId').value = '';
    document.getElementById('transactionDate').valueAsDate = new Date();
    
    if (type === 'income') {
        typeSelect.value = 'income';
        title.textContent = 'Add Income';
    } else {
        typeSelect.value = 'expense';
        title.textContent = 'Add Expense';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('transactionModal').classList.remove('active');
    document.getElementById('transactionForm').reset();
}

function openBudgetModal() {
    document.getElementById('budgetForm').reset();
    document.getElementById('budgetModal').classList.add('active');
}

function closeBudgetModal() {
    document.getElementById('budgetModal').classList.remove('active');
    document.getElementById('budgetForm').reset();
}

function getCategoryIcon(category) {
    const icons = {
        'Salary': '💼',
        'Food': '🍔',
        'Transport': '🚗',
        'Shopping': '🛍️',
        'Bills': '📱',
        'Entertainment': '🎮',
        'Other': '📦'
    };
    return icons[category] || '💰';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const transactionModal = document.getElementById('transactionModal');
    const budgetModal = document.getElementById('budgetModal');
    
    if (event.target === transactionModal) {
        closeModal();
    }
    if (event.target === budgetModal) {
        closeBudgetModal();
    }
}
