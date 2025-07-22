-- 創建 migrations 表
CREATE TABLE migrations (
    id INTEGER PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- 創建 mortgage_form_submissions 表
CREATE TABLE mortgage_form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    line_id VARCHAR(100),
    region VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    assigned_to VARCHAR(255),
    submitted_at DATETIME NOT NULL,
    source_url TEXT,
    utm_source VARCHAR(255),
    utm_medium VARCHAR(255),
    utm_campaign VARCHAR(255),
    utm_content VARCHAR(255),
    utm_term VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 創建 sales_line_accounts 表
CREATE TABLE sales_line_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sales_code VARCHAR(100) NOT NULL UNIQUE,
    sales_name VARCHAR(255) NOT NULL,
    line_user_id VARCHAR(100) NOT NULL UNIQUE,
    is_manager BOOLEAN DEFAULT 0,
    responsible_regions TEXT, -- JSON format
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 創建 line_query_logs 表  
CREATE TABLE line_query_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    line_user_id VARCHAR(100) NOT NULL,
    query_type VARCHAR(100) NOT NULL,
    query_params TEXT, -- JSON format
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 插入示例數據
INSERT INTO sales_line_accounts (sales_code, sales_name, line_user_id, is_manager, responsible_regions, is_active) VALUES
('SA001', '李美玲', 'U123456789abcdef01', 1, '["台北市", "新北市"]', 1),
('SA002', '陳志強', 'U123456789abcdef02', 0, '["桃園市", "新竹市"]', 1),
('SA003', '王小華', 'U123456789abcdef03', 0, '["台中市", "彰化縣"]', 1),
('SA004', '林雅婷', 'U123456789abcdef04', 0, '["台南市", "高雄市"]', 1);

INSERT INTO mortgage_form_submissions (customer_name, phone, line_id, region, status, assigned_to, submitted_at, source_url, utm_source, utm_medium, utm_campaign, ip_address) VALUES
('張大明', '0912345678', 'U987654321fedcba01', '台北市', 'pending', '李美玲', '2024-01-15 10:30:00', 'https://example.com/form', 'google', 'cpc', 'spring_promotion', '192.168.1.100'),
('劉小芳', '0923456789', 'U987654321fedcba02', '桃園市', 'contacted', '陳志強', '2024-01-15 14:20:00', 'https://example.com/form', 'facebook', 'social', 'new_year_campaign', '192.168.1.101'),
('陳建國', '0934567890', '', '台中市', 'qualified', '王小華', '2024-01-16 09:15:00', 'https://example.com/form', 'line', 'referral', 'friend_referral', '192.168.1.102'),
('李淑美', '0945678901', 'U987654321fedcba04', '台南市', 'pending', '林雅婷', '2024-01-16 16:45:00', 'https://example.com/form', 'google', 'organic', '', '192.168.1.103'),
('黃志偉', '0956789012', 'U987654321fedcba05', '高雄市', 'invalid', '林雅婷', '2024-01-17 11:30:00', 'https://example.com/form', 'yahoo', 'cpc', 'finance_ads', '192.168.1.104');

INSERT INTO line_query_logs (line_user_id, query_type, query_params) VALUES
('U123456789abcdef01', 'customer_list', '{"region": "台北市", "status": "pending"}'),
('U123456789abcdef02', 'today_customers', '{"date": "2024-01-15"}'),
('U123456789abcdef03', 'statistics', '{"period": "week"}'),
('U123456789abcdef01', 'search', '{"keyword": "張大明"}'),
('U123456789abcdef04', 'customer_list', '{"region": "台南市", "status": "all"}');

INSERT INTO migrations (migration, batch) VALUES
('2024_01_01_000001_create_mortgage_form_submissions_table', 1),
('2024_01_01_000002_create_sales_line_accounts_table', 1),
('2024_01_01_000003_create_line_query_logs_table', 1);