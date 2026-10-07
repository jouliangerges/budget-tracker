-- Amounts are in cents (e.g. 320000 = 3,200.00 EUR).
INSERT INTO transactions (category_id, type, amount, description, date) VALUES
    (1, 'income',  320000, 'Salary August',          '2026-08-01'),
    (2, 'expense',  95000, 'Rent August',            '2026-08-03'),
    (3, 'expense',   6743, 'Weekly groceries',       '2026-08-06'),
    (4, 'expense',   5800, 'Monthly transit ticket', '2026-08-07'),
    (5, 'expense',   2499, 'Cinema',                 '2026-08-15'),
    (6, 'expense',   1250, NULL,                     '2026-08-21'),
    (1, 'income',  320000, 'Salary September',       '2026-09-01'),
    (2, 'expense',  95000, 'Rent September',         '2026-09-03'),
    (3, 'expense',   8215, 'Weekly groceries',       '2026-09-05'),
    (6, 'income',    1250, 'Pharmacy refund',        '2026-09-12'),
    (5, 'expense',   4990, 'Concert tickets',        '2026-09-19');
