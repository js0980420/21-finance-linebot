// 僅在本地開發環境載入 .env 檔案
if (process.env.NODE_ENV !== 'production') {
  require('dotenv').config();
}

const express = require('express');
const line = require('@line/bot-sdk');
const mysql = require('mysql2/promise');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const cron = require('node-cron');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
const moment = require('moment');
const axios = require('axios');
const OpenAI = require('openai');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Line Bot 設定 - 使用 Zeabur 環境變數
const config = {
  channelAccessToken: process.env.LINE_CHANNEL_ACCESS_TOKEN || 'demo_token',
  channelSecret: process.env.LINE_CHANNEL_SECRET || 'demo_secret',
};

// 只在有正確配置時初始化 Line Client
let client = null;
if (process.env.LINE_CHANNEL_ACCESS_TOKEN && process.env.LINE_CHANNEL_SECRET) {
  client = new line.Client(config);
}

// OpenAI 設定 - 使用 Zeabur 環境變數
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

// 資料庫設定 - 演示模式使用內存資料庫
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'demo_user',
  password: process.env.DB_PASSWORD || 'demo_password',
  database: process.env.DB_NAME || 'demo_database',
  port: process.env.DB_PORT || 3306,
  ssl: process.env.DB_SSL === 'true' ? {
    rejectUnauthorized: false
  } : false
};

// 安全性中間件 - 開發演示模式放寬CSP限制
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
      scriptSrcAttr: ["'unsafe-inline'"],
      styleSrc: ["'self'", "'unsafe-inline'", "https://cdnjs.cloudflare.com", "https://fonts.googleapis.com"],
      fontSrc: ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com"],
      imgSrc: ["'self'", "data:", "https:"],
      connectSrc: ["'self'"]
    }
  }
}));
app.use(cors());

// 請求限制
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15分鐘
  max: 100 // 限制每個IP最多100個請求
});
app.use(limiter);

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 提供靜態檔案服務
app.use(express.static('public'));

// 資料庫連線池
let db;
let isDemo = true; // 演示模式標記

// 內存資料庫（演示模式）
let memoryDatabase = {
  applications: [],
  salesStaff: []
};

async function initDatabase() {
  // 檢查是否有必要的資料庫環境變數
  const hasDbConfig = process.env.DB_HOST && process.env.DB_USER && process.env.DB_PASSWORD;
  
  if (!hasDbConfig) {
    console.log('🔧 未檢測到資料庫配置，啟動演示模式');
    isDemo = true;
    await loadDemoData();
    return;
  }

  try {
    console.log('🔗 嘗試連接資料庫...');
    console.log(`📍 資料庫主機: ${process.env.DB_HOST}:${process.env.DB_PORT || 3306}`);
    
    // 創建連線池
    db = mysql.createPool(dbConfig);
    
    // 測試連線
    const connection = await db.getConnection();
    await connection.ping();
    connection.release();
    
    console.log('✅ 資料庫連線成功');
    isDemo = false;
    
    console.log('🏗️ 開始建立資料表...');
    await createTables();
    console.log('✅ 資料表建立完成');
      
    console.log('📝 插入測試資料...');
    await insertSampleData();
    console.log('✅ 測試資料插入完成');
    
  } catch (error) {
    console.error('❌ 資料庫初始化失敗:', error.message);
    console.error('📋 錯誤詳情:', {
      code: error.code,
      errno: error.errno,
      host: process.env.DB_HOST,
      port: process.env.DB_PORT || 3306,
      user: process.env.DB_USER
    });
    
    console.log('🔄 切換到演示模式...');
    isDemo = true;
    db = null; // 清除失敗的連線池
    await loadDemoData();
  }
}

// 載入演示資料
async function loadDemoData() {
  // 演示業務人員資料 - 包含權限角色
  memoryDatabase.salesStaff = [
    {id: 1, name: '王小明', department: '汽車部', email: 'wang@example.com', phone: '0987654321', role: 'sales', manager_id: 6},
    {id: 2, name: '李美華', department: '機車部', email: 'li@example.com', phone: '0912345678', role: 'sales', manager_id: 7},
    {id: 3, name: '張志強', department: '手機部', email: 'zhang@example.com', phone: '0923456789', role: 'sales', manager_id: 8},
    {id: 4, name: '陳雅婷', department: '汽車部', email: 'chen@example.com', phone: '0934567890', role: 'sales', manager_id: 6},
    {id: 5, name: '林大雄', department: '機車部', email: 'lin@example.com', phone: '0945678901', role: 'sales', manager_id: 7},
    {id: 6, name: '黃主管', department: '汽車部', email: 'huang@example.com', phone: '0956789012', role: 'manager', manager_id: 9},
    {id: 7, name: '劉主管', department: '機車部', email: 'liu@example.com', phone: '0967890123', role: 'manager', manager_id: 9},
    {id: 8, name: '吳主管', department: '手機部', email: 'wu@example.com', phone: '0978901234', role: 'manager', manager_id: 9},
    {id: 9, name: '總經理', department: '管理部', email: 'ceo@example.com', phone: '0989012345', role: 'admin', manager_id: null}
  ];

  // 演示案件資料 - 簡化來源網站只保留3個
  memoryDatabase.applications = [
    {id: 1, case_number: 'CASE1735130000001', applicant_line_name: '張小花', applicant_name: '張小花', phone: '0912345678', email: 'zhang@test.com', source_website: '熊好貸', region: '台北市', assigned_sales_staff_id: 1, sales_staff_name: '王小明', loan_type: 'car', collateral_item: 'Toyota Camry 2020年', can_submit: true, has_negotiated: false, monthly_payment: 15000, installment_periods: 36, disbursement_date: '2024-12-25', application_date: '2024-12-25', status: 'pending'},
    {id: 2, case_number: 'CASE1735130000002', applicant_line_name: 'LINE_USER_002', applicant_name: '李大明', phone: '0987654321', email: 'li@test.com', source_website: 'A網站', region: '新北市', assigned_sales_staff_id: 2, sales_staff_name: '李美華', loan_type: 'motorcycle', collateral_item: 'Yamaha SMAX 2022年', can_submit: false, has_negotiated: true, monthly_payment: 8000, installment_periods: 24, disbursement_date: null, application_date: '2024-12-25', status: 'pending'},
    {id: 3, case_number: 'CASE1735130000003', applicant_line_name: '王美美', applicant_name: '王美美', phone: '0923456789', email: 'wang@test.com', source_website: 'B網站', region: '桃園市', assigned_sales_staff_id: 3, sales_staff_name: '張志強', loan_type: 'phone', collateral_item: 'iPhone 15 Pro', can_submit: true, has_negotiated: true, monthly_payment: 3000, installment_periods: 12, disbursement_date: '2024-12-20', application_date: '2024-12-25', status: 'approved'},
    {id: 4, case_number: 'CASE1735130000004', applicant_line_name: 'Johnson Lin', applicant_name: '林強森', phone: '0934567890', email: 'johnson@test.com', source_website: '熊好貸', region: '台中市', assigned_sales_staff_id: 1, sales_staff_name: '王小明', loan_type: 'car', collateral_item: 'BMW X3 2021年', can_submit: false, has_negotiated: false, monthly_payment: 25000, installment_periods: 48, disbursement_date: null, application_date: '2024-12-24', status: 'pending'},
    {id: 5, case_number: 'CASE1735130000005', applicant_line_name: '陳小美', applicant_name: '陳小美', phone: '0945678901', email: 'chen@test.com', source_website: 'A網站', region: '高雄市', assigned_sales_staff_id: 2, sales_staff_name: '李美華', loan_type: 'motorcycle', collateral_item: 'Honda PCX 2023年', can_submit: true, has_negotiated: true, monthly_payment: 6000, installment_periods: 18, disbursement_date: '2024-12-22', application_date: '2024-12-24', status: 'approved'},
    {id: 6, case_number: 'CASE1735130000006', applicant_line_name: '劉志明', applicant_name: '劉志明', phone: '0956789012', email: 'liu@test.com', source_website: 'B網站', region: '台南市', assigned_sales_staff_id: 3, sales_staff_name: '張志強', loan_type: 'phone', collateral_item: 'Samsung Galaxy S24', can_submit: true, has_negotiated: false, monthly_payment: 2500, installment_periods: 24, disbursement_date: null, application_date: '2024-12-24', status: 'pending'},
    {id: 7, case_number: 'CASE1735130000007', applicant_line_name: '黃小芳', applicant_name: '黃小芳', phone: '0967890123', email: 'huang@test.com', source_website: '熊好貸', region: '桃園市', assigned_sales_staff_id: 4, sales_staff_name: '陳雅婷', loan_type: 'car', collateral_item: 'Honda Civic 2023年', can_submit: false, has_negotiated: true, monthly_payment: 18000, installment_periods: 60, disbursement_date: '2024-12-23', application_date: '2024-12-23', status: 'approved'},
    {id: 8, case_number: 'CASE1735130000008', applicant_line_name: 'Mary Chen', applicant_name: '陳美莉', phone: '0978901234', email: 'mary@test.com', source_website: 'A網站', region: '台北市', assigned_sales_staff_id: 5, sales_staff_name: '林大雄', loan_type: 'motorcycle', collateral_item: 'Suzuki GSX-R150', can_submit: true, has_negotiated: true, monthly_payment: 7500, installment_periods: 36, disbursement_date: null, application_date: '2024-12-23', status: 'pending'},
    {id: 9, case_number: 'CASE1735130000009', applicant_line_name: '林大偉', applicant_name: '林大偉', phone: '0989012345', email: 'david@test.com', source_website: 'B網站', region: '新北市', assigned_sales_staff_id: 1, sales_staff_name: '王小明', loan_type: 'phone', collateral_item: 'iPhone 14 Pro Max', can_submit: true, has_negotiated: false, monthly_payment: 3500, installment_periods: 18, disbursement_date: '2024-12-21', application_date: '2024-12-23', status: 'approved'},
    {id: 10, case_number: 'CASE1735130000010', applicant_line_name: '王大華', applicant_name: '王大華', phone: '0921334567', email: 'wangdh@test.com', source_website: '熊好貸', region: '台中市', assigned_sales_staff_id: 2, sales_staff_name: '李美華', loan_type: 'car', collateral_item: 'Mazda CX-5 2023年', can_submit: false, has_negotiated: false, monthly_payment: 20000, installment_periods: 42, disbursement_date: null, application_date: '2024-12-25', status: 'pending'},
    {id: 11, case_number: 'CASE1735130000011', applicant_line_name: '李小玉', applicant_name: '李小玉', phone: '0933445566', email: 'lixy@test.com', source_website: 'A網站', region: '台南市', assigned_sales_staff_id: 3, sales_staff_name: '張志強', loan_type: 'motorcycle', collateral_item: 'Gogoro 3 Plus', can_submit: true, has_negotiated: true, monthly_payment: 4500, installment_periods: 30, disbursement_date: '2024-12-26', application_date: '2024-12-24', status: 'approved'},
    {id: 12, case_number: 'CASE1735130000012', applicant_line_name: '劉小明', applicant_name: '劉小明', phone: '0944556677', email: 'liuming@test.com', source_website: 'B網站', region: '高雄市', assigned_sales_staff_id: 4, sales_staff_name: '陳雅婷', loan_type: 'phone', collateral_item: 'Google Pixel 8 Pro', can_submit: true, has_negotiated: false, monthly_payment: 2800, installment_periods: 20, disbursement_date: null, application_date: '2024-12-25', status: 'pending'}
  ];

  console.log('演示資料載入完成');
}

// 建立資料表
async function createTables() {
  if (!db) {
    throw new Error('資料庫連線池未初始化');
  }

  const tables = [
    // 客戶表
    `CREATE TABLE IF NOT EXISTS customers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      line_user_id VARCHAR(255) UNIQUE NOT NULL,
      line_display_name VARCHAR(255),
      phone VARCHAR(20),
      email VARCHAR(255),
      region VARCHAR(100),
      source_website VARCHAR(255),
      utm_source VARCHAR(255),
      utm_medium VARCHAR(255),
      utm_campaign VARCHAR(255),
      registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
      status ENUM('active', 'inactive') DEFAULT 'active',
      notes TEXT
    )`,
    
    // 業務表 - 加入權限管理
    `CREATE TABLE IF NOT EXISTS sales_staff (
      id INT AUTO_INCREMENT PRIMARY KEY,
      line_user_id VARCHAR(255) UNIQUE NOT NULL,
      name VARCHAR(100) NOT NULL,
      department VARCHAR(100),
      email VARCHAR(255),
      phone VARCHAR(20),
      role ENUM('admin', 'manager', 'sales') DEFAULT 'sales',
      manager_id INT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (manager_id) REFERENCES sales_staff(id)
    )`,
    
    // 客戶業務關聯表
    `CREATE TABLE IF NOT EXISTS customer_sales_assignments (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT,
      sales_staff_id INT,
      assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      is_active BOOLEAN DEFAULT TRUE,
      FOREIGN KEY (customer_id) REFERENCES customers(id),
      FOREIGN KEY (sales_staff_id) REFERENCES sales_staff(id)
    )`,
    
    // 追蹤記錄表
    `CREATE TABLE IF NOT EXISTS follow_up_records (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT,
      sales_staff_id INT,
      follow_up_type ENUM('call', 'message', 'meeting', 'email') NOT NULL,
      content TEXT,
      next_follow_up_date DATETIME,
      status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (customer_id) REFERENCES customers(id),
      FOREIGN KEY (sales_staff_id) REFERENCES sales_staff(id)
    )`,
    
    // 案件申請表
    `CREATE TABLE IF NOT EXISTS loan_applications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      case_number VARCHAR(50) UNIQUE NOT NULL,
      applicant_line_name VARCHAR(255),
      applicant_name VARCHAR(100),
      phone VARCHAR(20),
      email VARCHAR(255),
      source_website VARCHAR(255),
      region VARCHAR(100),
      assigned_sales_staff_id INT,
      loan_type ENUM('car', 'motorcycle', 'phone') NOT NULL,
      collateral_item VARCHAR(255),
      can_submit BOOLEAN DEFAULT FALSE,
      has_negotiated BOOLEAN DEFAULT FALSE,
      monthly_payment DECIMAL(12,2),
      installment_periods INT,
      disbursement_date DATE,
      application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      line_user_id VARCHAR(255),
      status ENUM('pending', 'approved', 'rejected', 'processing') DEFAULT 'pending',
      notes TEXT,
      FOREIGN KEY (assigned_sales_staff_id) REFERENCES sales_staff(id)
    )`,
    
    // 表單提交記錄表（保留原有功能）
    `CREATE TABLE IF NOT EXISTS form_submissions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100),
      phone VARCHAR(20),
      email VARCHAR(255),
      loan_type ENUM('car', 'motorcycle', 'phone') NOT NULL,
      amount DECIMAL(12,2),
      source_website VARCHAR(255),
      utm_params JSON,
      submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      line_user_id VARCHAR(255),
      is_processed BOOLEAN DEFAULT FALSE
    )`,
    
    // 一次性驗證Token表
    `CREATE TABLE IF NOT EXISTS verification_tokens (
      id INT AUTO_INCREMENT PRIMARY KEY,
      token VARCHAR(255) UNIQUE NOT NULL,
      phone VARCHAR(20),
      email VARCHAR(255),
      used BOOLEAN DEFAULT FALSE,
      expires_at DATETIME,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`
  ];

  for (let i = 0; i < tables.length; i++) {
    const tableName = ['customers', 'sales_staff', 'customer_sales_assignments', 'follow_up_records', 'loan_applications', 'form_submissions', 'verification_tokens'][i];
    try {
      await db.execute(tables[i]);
      console.log(`✅ 資料表 ${tableName} 建立成功`);
    } catch (error) {
      console.error(`❌ 建立資料表 ${tableName} 失敗:`, error.message);
      throw error; // 重新拋出錯誤，讓上層函數處理
    }
  }
}

// 插入測試資料
async function insertSampleData() {
  try {
    // 檢查是否已有業務資料
    const [existingSales] = await db.execute('SELECT COUNT(*) as count FROM sales_staff');
    
    if (existingSales[0].count === 0) {
      // 插入測試業務人員
      const salesData = [
        ['U1001', '王小明', '汽車部', 'wang@example.com', '0987654321'],
        ['U1002', '李美華', '機車部', 'li@example.com', '0912345678'],
        ['U1003', '張志強', '手機部', 'zhang@example.com', '0923456789'],
        ['U1004', '陳雅婷', '汽車部', 'chen@example.com', '0934567890'],
        ['U1005', '林大雄', '機車部', 'lin@example.com', '0945678901']
      ];
      
      for (const [lineId, name, dept, email, phone] of salesData) {
        await db.execute(
          'INSERT INTO sales_staff (line_user_id, name, department, email, phone) VALUES (?, ?, ?, ?, ?)',
          [lineId, name, dept, email, phone]
        );
      }
      console.log('已插入測試業務人員資料');
    }
    
    // 檢查是否已有案件資料
    const [existingApps] = await db.execute('SELECT COUNT(*) as count FROM loan_applications');
    
    if (existingApps[0].count === 0) {
      // 插入測試案件資料
      const applicationsData = [
        ['CASE1735130000001', '張小花', '張小花', '0912345678', 'zhang@test.com', '熊好貸', '台北市', 1, 'car', 'Toyota Camry 2020年', true, false, 15000, 36, '2024-12-25', 'pending'],
        ['CASE1735130000002', 'LINE_USER_002', '李大明', '0987654321', 'li@test.com', '和潤', '新北市', 2, 'motorcycle', 'Yamaha SMAX 2022年', false, true, 8000, 24, null, 'processing'],
        ['CASE1735130000003', '王美美', '王美美', '0923456789', 'wang@test.com', 'Google廣告', '桃園市', 3, 'phone', 'iPhone 15 Pro', true, true, 3000, 12, '2024-12-20', 'approved'],
        ['CASE1735130000004', 'Johnson Lin', '林強森', '0934567890', 'johnson@test.com', '熊好貸', '台中市', 1, 'car', 'BMW X3 2021年', false, false, 25000, 48, null, 'pending'],
        ['CASE1735130000005', '陳小美', '陳小美', '0945678901', 'chen@test.com', '和潤', '高雄市', 2, 'motorcycle', 'Honda PCX 2023年', true, true, 6000, 18, '2024-12-22', 'approved'],
        ['CASE1735130000006', '劉志明', '劉志明', '0956789012', 'liu@test.com', '官方網站', '台南市', 3, 'phone', 'Samsung Galaxy S24', true, false, 2500, 24, null, 'processing'],
        ['CASE1735130000007', '黃小芳', '黃小芳', '0967890123', 'huang@test.com', 'Facebook', '桃園市', 4, 'car', 'Honda Civic 2023年', false, true, 18000, 60, '2024-12-23', 'approved'],
        ['CASE1735130000008', 'Mary Chen', '陳美莉', '0978901234', 'mary@test.com', '熊好貸', '台北市', 5, 'motorcycle', 'Suzuki GSX-R150', true, true, 7500, 36, null, 'processing'],
        ['CASE1735130000009', '林大偉', '林大偉', '0989012345', 'david@test.com', '和潤', '新北市', 1, 'phone', 'iPhone 14 Pro Max', true, false, 3500, 18, '2024-12-21', 'approved']
      ];
      
      for (const appData of applicationsData) {
        await db.execute(`
          INSERT INTO loan_applications (
            case_number, applicant_line_name, applicant_name, phone, email, 
            source_website, region, assigned_sales_staff_id, loan_type, 
            collateral_item, can_submit, has_negotiated, monthly_payment, 
            installment_periods, disbursement_date, status
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, appData);
      }
      console.log('已插入測試案件資料');
    }
    
  } catch (error) {
    console.error('插入測試資料錯誤:', error);
  }
}

// Line Bot Webhook - 只在生產模式啟用
if (!isDemo && client) {
app.post('/webhook', line.middleware(config), async (req, res) => {
  try {
    const events = req.body.events;
    
    for (const event of events) {
      await handleEvent(event);
    }
    
    res.status(200).end();
  } catch (error) {
    console.error('Webhook錯誤:', error);
    res.status(500).end();
  }
});
} else {
  // 演示模式下的假 webhook
  app.post('/webhook', (req, res) => {
    console.log('演示模式 - 收到 webhook 請求');
    res.json({ status: 'demo mode' });
  });
}

// 處理Line事件
async function handleEvent(event) {
  const { type, source, message, postback } = event;
  const userId = source.userId;

  try {
    switch (type) {
      case 'message':
        if (message.type === 'text') {
          await handleTextMessage(userId, message.text);
        }
        break;
        
      case 'follow':
        await handleFollowEvent(userId);
        break;
        
      case 'postback':
        await handlePostbackEvent(userId, postback.data);
        break;
    }
  } catch (error) {
    console.error('處理事件錯誤:', error);
  }
}

// 處理文字訊息
async function handleTextMessage(userId, text) {
  const lowerText = text.toLowerCase();
  
  // 檢查是否為業務人員
  const salesStaff = await getSalesStaffByLineId(userId);
  
  if (salesStaff) {
    // 業務人員功能
    if (lowerText.includes('客戶列表') || lowerText.includes('查看客戶')) {
      await showCustomerList(userId, salesStaff);
    } else if (lowerText.includes('案件管理') || lowerText.includes('我的案件')) {
      await showMyCases(userId, salesStaff);
    } else if (lowerText.includes('統計報表') || lowerText.includes('業績')) {
      await showPerformanceStats(userId, salesStaff);
    } else if (lowerText.startsWith('客戶:') || lowerText.startsWith('查詢:')) {
      // 搜尋特定客戶
      const searchTerm = text.substring(text.indexOf(':') + 1).trim();
      await searchCustomer(userId, salesStaff, searchTerm);
    } else {
      await showSalesMainMenu(userId, salesStaff);
    }
    return;
  }
  
  // 演示模式下一般客戶功能較簡化
  if (isDemo) {
    await showMainMenu(userId);
    return;
  }

  // 檢查用戶是否已註冊
  const [customers] = await db.execute(
    'SELECT * FROM customers WHERE line_user_id = ?',
    [userId]
  );
  
  const customer = customers[0];
  
  if (lowerText.includes('註冊') || lowerText.includes('加入')) {
    if (customer) {
      await replyMessage(userId, '您已經是我們的會員了！如需其他服務請選擇下方選單。');
    } else {
      await startRegistration(userId);
    }
  } else if (lowerText.includes('查詢') || lowerText.includes('狀態')) {
    if (customer) {
      await showCustomerInfo(userId, customer);
    } else {
      await replyMessage(userId, '請先完成註冊才能查詢相關資訊。');
    }
  } else if (lowerText.includes('貸款') || lowerText.includes('申請')) {
    await showLoanOptions(userId);
  } else if (lowerText.includes('聯絡') || lowerText.includes('業務')) {
    if (customer) {
      await contactSalesStaff(userId, customer.id);
    } else {
      await replyMessage(userId, '請先完成註冊才能查詢相關資訊。');
    }
  } else {
    // 使用 OpenAI 智能回應
    try {
      const aiResponse = await getAIResponse(text, customer);
      await replyMessage(userId, aiResponse);
    } catch (error) {
      console.error('AI回應錯誤:', error);
      // 預設回應
      await showMainMenu(userId);
    }
  }
}

// 處理追蹤事件（用戶加入）
async function handleFollowEvent(userId) {
  // 演示模式下不處理追蹤事件
  if (isDemo) {
    console.log('演示模式 - 跳過追蹤事件處理');
    return;
  }

  try {
    // 獲取用戶資料
    const profile = await client.getProfile(userId);
    
    // 檢查是否已存在
    const [existing] = await db.execute(
      'SELECT * FROM customers WHERE line_user_id = ?',
      [userId]
    );
    
    if (existing.length === 0) {
      // 新用戶，插入基本資料
      await db.execute(
        'INSERT INTO customers (line_user_id, line_display_name) VALUES (?, ?)',
        [userId, profile.displayName]
      );
    } else {
      // 更新顯示名稱
      await db.execute(
        'UPDATE customers SET line_display_name = ?, last_active = NOW() WHERE line_user_id = ?',
        [profile.displayName, userId]
      );
    }
    
    const welcomeMessage = {
      type: 'template',
      altText: '歡迎加入中租經銷商服務',
      template: {
        type: 'buttons',
        title: '歡迎加入中租經銷商',
        text: `您好 ${profile.displayName}！\n我們提供汽車、機車、手機貸款服務`,
        actions: [
          {
            type: 'message',
            label: '🚗 汽車貸款',
            text: '我想了解汽車貸款'
          },
          {
            type: 'message',
            label: '🏍️ 機車貸款',
            text: '我想了解機車貸款'
          },
          {
            type: 'message',
            label: '📱 手機貸款',
            text: '我想了解手機貸款'
          },
          {
            type: 'postback',
            label: '📋 完整註冊',
            data: 'action=register'
          }
        ]
      }
    };
    
    await client.replyMessage(event.replyToken, welcomeMessage);
  } catch (error) {
    console.error('處理追蹤事件錯誤:', error);
  }
}

// 處理Postback事件
async function handlePostbackEvent(userId, data) {
  const params = new URLSearchParams(data);
  const action = params.get('action');
  
  switch (action) {
    case 'register':
      await startRegistration(userId);
      break;
    case 'loan_car':
      await showLoanForm(userId, 'car');
      break;
    case 'loan_motorcycle':
      await showLoanForm(userId, 'motorcycle');
      break;
    case 'loan_phone':
      await showLoanForm(userId, 'phone');
      break;
  }
}

// 開始註冊流程
async function startRegistration(userId) {
  const message = {
    type: 'template',
    altText: '請完成註冊',
    template: {
      type: 'buttons',
      title: '完成會員註冊',
      text: '請點擊下方連結完成詳細註冊',
      actions: [
        {
          type: 'uri',
          label: '📝 填寫註冊表單',
          uri: `${process.env.FRONTEND_URL}/register?token=${await generateVerificationToken(userId)}`
        }
      ]
    }
  };
  
  await replyMessage(userId, message);
}

// 顯示貸款選項
async function showLoanOptions(userId) {
  const message = {
    type: 'template',
    altText: '貸款服務選項',
    template: {
      type: 'carousel',
      columns: [
        {
          title: '汽車貸款',
          text: '新車、中古車貸款服務',
          actions: [
            {
              type: 'postback',
              label: '了解詳情',
              data: 'action=loan_car'
            }
          ]
        },
        {
          title: '機車貸款',
          text: '機車購車貸款服務',
          actions: [
            {
              type: 'postback',
              label: '了解詳情',
              data: 'action=loan_motorcycle'
            }
          ]
        },
        {
          title: '手機貸款',
          text: '手機分期貸款服務',
          actions: [
            {
              type: 'postback',
              label: '了解詳情',
              data: 'action=loan_phone'
            }
          ]
        }
      ]
    }
  };
  
  await replyMessage(userId, message);
}

// 根據 Line ID 獲取業務人員資訊
async function getSalesStaffByLineId(lineUserId) {
  if (isDemo) {
    // 演示模式：模擬業務人員 Line ID
    const demoLineIds = {
      'U001': 1, // 王小明
      'U002': 2, // 李美華
      'U003': 3, // 張志強
      'U004': 4, // 陳雅婷
      'U005': 5, // 林大雄
      'U006': 6, // 黃主管
      'U007': 7, // 劉主管
      'U008': 8, // 吳主管
      'U009': 9, // 總經理
    };
    
    const staffId = demoLineIds[lineUserId];
    if (staffId) {
      return memoryDatabase.salesStaff.find(staff => staff.id === staffId);
    }
    return null;
  }
  
  try {
    const [staff] = await db.execute(
      'SELECT * FROM sales_staff WHERE line_user_id = ? AND is_active = TRUE',
      [lineUserId]
    );
    return staff[0] || null;
  } catch (error) {
    console.error('獲取業務人員資訊錯誤:', error);
    return null;
  }
}

// 顯示業務人員主選單
async function showSalesMainMenu(userId, salesStaff) {
  const message = {
    type: 'template',
    altText: '業務管理系統',
    template: {
      type: 'buttons',
      title: `${salesStaff.name} - 業務系統`,
      text: `${salesStaff.department} | ${salesStaff.role === 'admin' ? '管理者' : salesStaff.role === 'manager' ? '主管' : '業務'}`,
      actions: [
        {
          type: 'message',
          label: '👥 查看客戶',
          text: '客戶列表'
        },
        {
          type: 'message',
          label: '📋 我的案件',
          text: '案件管理'
        },
        {
          type: 'message',
          label: '📊 統計報表',
          text: '統計報表'
        },
        {
          type: 'uri',
          label: '💻 網頁版',
          uri: `http://localhost:3000/login`
        }
      ]
    }
  };
  
  await replyMessage(userId, message);
}

// 顯示客戶列表 - 支援點擊客戶名稱自動跳轉對話
async function showCustomerList(userId, salesStaff) {
  let customers = [];
  
  if (isDemo) {
    // 根據權限過濾客戶
    if (salesStaff.role === 'admin') {
      customers = memoryDatabase.applications;
    } else if (salesStaff.role === 'manager') {
      // 主管可看部門內所有客戶
      const teamMembers = memoryDatabase.salesStaff.filter(staff => 
        staff.manager_id === salesStaff.id || staff.id === salesStaff.id
      );
      const teamIds = teamMembers.map(member => member.id);
      customers = memoryDatabase.applications.filter(app => 
        teamIds.includes(app.assigned_sales_staff_id)
      );
    } else {
      // 業務只能看自己的客戶
      customers = memoryDatabase.applications.filter(app => 
        app.assigned_sales_staff_id === salesStaff.id
      );
    }
  } else {
    // 真實資料庫查詢邏輯
    // TODO: 實現資料庫查詢
  }
  
  if (customers.length === 0) {
    await replyMessage(userId, '目前沒有分配給您的客戶案件。');
    return;
  }
  
  // 建立客戶列表 Flex Message
  const bubbles = customers.slice(0, 10).map(customer => ({
    type: 'bubble',
    header: {
      type: 'box',
      layout: 'vertical',
      contents: [
        {
          type: 'text',
          text: customer.applicant_line_name,
          weight: 'bold',
          size: 'lg',
          color: '#1DB446',
                     action: {
             type: 'uri',
             uri: `https://line.me/R/ti/p/~${customer.applicant_line_name}`
           }
        }
      ]
    },
    body: {
      type: 'box',
      layout: 'vertical',
      contents: [
        {
          type: 'text',
          text: `真實姓名: ${customer.applicant_name}`,
          size: 'sm',
          color: '#666666'
        },
        {
          type: 'text',
          text: `案件編號: ${customer.case_number}`,
          size: 'sm',
          color: '#666666'
        },
        {
          type: 'text',
          text: `地區: ${customer.region}`,
          size: 'sm',
          color: '#666666'
        },
        {
          type: 'text',
          text: `來源: ${customer.source_website}`,
          size: 'sm',
          color: '#666666'
        },
        {
          type: 'text',
          text: `狀態: ${customer.status === 'pending' ? '待處理' : '可送件'}`,
          size: 'sm',
          color: customer.status === 'pending' ? '#FF6B6B' : '#4ECDC4',
          weight: 'bold'
        }
      ]
    },
    footer: {
      type: 'box',
      layout: 'vertical',
      contents: [
        {
          type: 'button',
                     action: {
             type: 'uri',
             label: '💬 開始對話',
             uri: `https://line.me/R/ti/p/~${customer.applicant_line_name}`
           },
          style: 'primary',
          color: '#1DB446'
        }
      ]
    }
  }));
  
  const message = {
    type: 'flex',
    altText: '客戶列表',
    contents: {
      type: 'carousel',
      contents: bubbles
    }
  };
  
  await replyMessage(userId, message);
  
  // 如果客戶超過10個，顯示提示
  if (customers.length > 10) {
    await replyMessage(userId, `顯示前10位客戶，共${customers.length}位。\n輸入「客戶:姓名」搜尋特定客戶。`);
  }
}

// 搜尋客戶
async function searchCustomer(userId, salesStaff, searchTerm) {
  let customers = [];
  
  if (isDemo) {
    // 根據權限過濾並搜尋
    let allCustomers = [];
    if (salesStaff.role === 'admin') {
      allCustomers = memoryDatabase.applications;
    } else if (salesStaff.role === 'manager') {
      const teamMembers = memoryDatabase.salesStaff.filter(staff => 
        staff.manager_id === salesStaff.id || staff.id === salesStaff.id
      );
      const teamIds = teamMembers.map(member => member.id);
      allCustomers = memoryDatabase.applications.filter(app => 
        teamIds.includes(app.assigned_sales_staff_id)
      );
    } else {
      allCustomers = memoryDatabase.applications.filter(app => 
        app.assigned_sales_staff_id === salesStaff.id
      );
    }
    
    customers = allCustomers.filter(customer => 
      customer.applicant_name.includes(searchTerm) || 
      customer.applicant_line_name.includes(searchTerm) ||
      customer.case_number.includes(searchTerm)
    );
  }
  
  if (customers.length === 0) {
    await replyMessage(userId, `找不到包含「${searchTerm}」的客戶資料。`);
    return;
  }
  
  // 顯示搜尋結果
  await showCustomerList(userId, salesStaff);
}

// 顯示我的案件
async function showMyCases(userId, salesStaff) {
  let cases = [];
  
  if (isDemo) {
    if (salesStaff.role === 'admin') {
      cases = memoryDatabase.applications;
    } else if (salesStaff.role === 'manager') {
      const teamMembers = memoryDatabase.salesStaff.filter(staff => 
        staff.manager_id === salesStaff.id || staff.id === salesStaff.id
      );
      const teamIds = teamMembers.map(member => member.id);
      cases = memoryDatabase.applications.filter(app => 
        teamIds.includes(app.assigned_sales_staff_id)
      );
    } else {
      cases = memoryDatabase.applications.filter(app => 
        app.assigned_sales_staff_id === salesStaff.id
      );
    }
  }
  
  const pendingCases = cases.filter(c => c.status === 'pending').length;
  const approvedCases = cases.filter(c => c.status === 'approved').length;
  
  const message = `📋 案件統計\n\n` +
    `待處理案件: ${pendingCases} 件\n` +
    `可送件案件: ${approvedCases} 件\n` +
    `總案件數: ${cases.length} 件\n\n` +
    `💻 詳細資訊請使用網頁版管理系統`;
  
  await replyMessage(userId, message);
}

// 顯示業績統計
async function showPerformanceStats(userId, salesStaff) {
  let cases = [];
  
  if (isDemo) {
    if (salesStaff.role === 'sales') {
      cases = memoryDatabase.applications.filter(app => 
        app.assigned_sales_staff_id === salesStaff.id
      );
    } else {
      // 主管或管理者看部門統計
      const teamMembers = memoryDatabase.salesStaff.filter(staff => 
        salesStaff.role === 'admin' ? true :
        (staff.manager_id === salesStaff.id || staff.id === salesStaff.id)
      );
      const teamIds = teamMembers.map(member => member.id);
      cases = memoryDatabase.applications.filter(app => 
        teamIds.includes(app.assigned_sales_staff_id)
      );
    }
  }
  
  const today = new Date().toISOString().split('T')[0];
  const todayCases = cases.filter(c => c.application_date === today).length;
  const negotiatedCases = cases.filter(c => c.has_negotiated).length;
  
  const message = `📊 業績統計\n\n` +
    `今日新案件: ${todayCases} 件\n` +
    `已洽談案件: ${negotiatedCases} 件\n` +
    `總負責案件: ${cases.length} 件\n` +
    `成交率: ${cases.length > 0 ? Math.round(negotiatedCases / cases.length * 100) : 0}%\n\n` +
    `💻 詳細統計請使用網頁版系統`;
  
  await replyMessage(userId, message);
}

// 顯示主選單
async function showMainMenu(userId) {
  const message = {
    type: 'template',
    altText: '主選單',
    template: {
      type: 'buttons',
      title: '中租經銷商服務',
      text: '請選擇您需要的服務',
      actions: [
        {
          type: 'message',
          label: '💰 貸款服務',
          text: '貸款'
        },
        {
          type: 'message',
          label: '📞 聯絡業務',
          text: '聯絡'
        },
        {
          type: 'message',
          label: '📊 查詢狀態',
          text: '查詢'
        },
        {
          type: 'postback',
          label: '📋 會員註冊',
          data: 'action=register'
        }
      ]
    }
  };
  
  await replyMessage(userId, message);
}

// 回覆訊息
async function replyMessage(userId, message) {
  try {
    await client.pushMessage(userId, message);
  } catch (error) {
    console.error('發送訊息錯誤:', error);
  }
}

// 生成驗證Token
async function generateVerificationToken(userId) {
  // 演示模式下返回假token
  if (isDemo) {
    return 'demo-token-' + Date.now();
  }

  const token = uuidv4();
  const expiresAt = moment().add(1, 'hour').format('YYYY-MM-DD HH:mm:ss');
  
  await db.execute(
    'INSERT INTO verification_tokens (token, expires_at) VALUES (?, ?)',
    [token, expiresAt]
  );
  
  return token;
}

// 表單提交API
app.post('/api/form-submit', async (req, res) => {
  try {
    const {
      name, phone, email, loanType, amount,
      sourceWebsite, utmSource, utmMedium, utmCampaign,
      token
    } = req.body;
    
    if (isDemo) {
      // 演示模式：假的表單提交
      console.log('演示模式 - 表單提交:', { name, phone, email, loanType, sourceWebsite });
      res.json({ success: true, message: '表單提交成功（演示模式）' });
      return;
    }
    
    // 驗證token
    const [tokenResults] = await db.execute(
      'SELECT * FROM verification_tokens WHERE token = ? AND used = FALSE AND expires_at > NOW()',
      [token]
    );
    
    if (tokenResults.length === 0) {
      return res.status(400).json({ error: '無效或已過期的驗證token' });
    }
    
    // 插入表單資料
    const [result] = await db.execute(
      `INSERT INTO form_submissions 
       (name, phone, email, loan_type, amount, source_website, utm_params, submission_date)
       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
      [
        name, phone, email, loanType, amount, sourceWebsite,
        JSON.stringify({ utmSource, utmMedium, utmCampaign })
      ]
    );
    
    // 標記token為已使用
    await db.execute(
      'UPDATE verification_tokens SET used = TRUE WHERE token = ?',
      [token]
    );
    
    // 自動分配業務（簡單的輪循分配）
    await autoAssignSalesStaff(result.insertId);
    
    res.json({ success: true, message: '表單提交成功' });
  } catch (error) {
    console.error('表單提交錯誤:', error);
    res.status(500).json({ error: '提交失敗' });
  }
});

// OpenAI 智能回應
async function getAIResponse(userMessage, customer) {
  try {
    const customerInfo = customer ? `客戶姓名: ${customer.line_display_name}` : '新客戶';
    
    const completion = await openai.chat.completions.create({
      model: "gpt-3.5-turbo",
      messages: [
        {
          role: "system",
          content: `你是中租經銷商的客服助理，專門協助客戶了解汽車、機車、手機貸款服務。
          
          請用繁體中文回應，保持專業友善的語調。
          
          當客戶詢問貸款相關問題時，請引導他們：
          1. 汽車貸款：新車、中古車皆可申請
          2. 機車貸款：各品牌機車購車貸款
          3. 手機貸款：手機分期付款服務
          
          如果需要具體申請，請引導客戶輸入「貸款」或「申請」來查看詳細選項。
          如果是一般諮詢，請提供有用的資訊並引導至相關服務。
          
          ${customerInfo}`
        },
        {
          role: "user",
          content: userMessage
        }
      ],
      max_tokens: 150,
      temperature: 0.7
    });

    return completion.choices[0].message.content;
  } catch (error) {
    console.error('OpenAI API錯誤:', error);
    return '很抱歉，我現在無法理解您的問題。請輸入「貸款」查看服務選項，或輸入「聯絡」與專人服務。';
  }
}

// 自動分配業務
async function autoAssignSalesStaff(submissionId) {
  // 演示模式下不進行實際分配
  if (isDemo) {
    console.log('演示模式 - 跳過業務分配');
    return;
  }

  try {
    // 取得可用的業務人員
    const [salesStaff] = await db.execute(
      'SELECT * FROM sales_staff WHERE is_active = TRUE ORDER BY id'
    );
    
    if (salesStaff.length > 0) {
      // 簡單的輪循分配邏輯
      const assignedStaff = salesStaff[submissionId % salesStaff.length];
      
      // 通知業務有新客戶
      const message = `🔔 新客戶通知\n\n有新的客戶提交表單，請盡快聯絡處理。\n\n表單編號: ${submissionId}`;
      
      await client.pushMessage(assignedStaff.line_user_id, message);
    }
  } catch (error) {
    console.error('自動分配業務錯誤:', error);
  }
}

// 定期提醒排程 - 只在生產模式啟用
if (!isDemo) {
cron.schedule('0 9 * * 1', async () => {
  console.log('執行每週業務提醒...');
  
  try {
    // 取得需要追蹤的客戶
    const [followUps] = await db.execute(`
      SELECT fr.*, c.line_display_name, c.phone, s.line_user_id as sales_line_id, s.name as sales_name
      FROM follow_up_records fr
      JOIN customers c ON fr.customer_id = c.id
      JOIN sales_staff s ON fr.sales_staff_id = s.id
      WHERE fr.status = 'pending' 
      AND fr.next_follow_up_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    `);
    
    // 分組發送提醒
    const salesReminders = {};
    
    followUps.forEach(followUp => {
      if (!salesReminders[followUp.sales_line_id]) {
        salesReminders[followUp.sales_line_id] = {
          salesName: followUp.sales_name,
          customers: []
        };
      }
      
      salesReminders[followUp.sales_line_id].customers.push({
        name: followUp.line_display_name,
        phone: followUp.phone,
        nextDate: moment(followUp.next_follow_up_date).format('MM/DD')
      });
    });
    
    // 發送提醒訊息
    for (const [salesLineId, reminder] of Object.entries(salesReminders)) {
      const customerList = reminder.customers
        .map(c => `• ${c.name} (${c.phone}) - ${c.nextDate}`)
        .join('\n');
        
      const message = `📅 本週追蹤提醒\n\n${reminder.salesName} 您好，\n\n以下客戶需要本週內追蹤：\n\n${customerList}\n\n請盡快聯絡客戶了解需求。`;
      
      await client.pushMessage(salesLineId, message);
    }
    
    console.log(`已發送 ${Object.keys(salesReminders).length} 個提醒訊息`);
  } catch (error) {
    console.error('定期提醒錯誤:', error);
    }
  });
}

// 路由設定
app.get('/', (req, res) => {
  res.redirect('/login');
});

app.get('/admin', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

app.get('/demo', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'demo.html'));
});

app.get('/demo2', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'demo2.html'));
});

// 登入頁面路由
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// 主管頁面路由
app.get('/manager', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'manager.html'));
});

// 業務頁面路由
app.get('/sales', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'sales.html'));
});

// API 路由 - 取得所有案件
app.get('/api/applications', async (req, res) => {
  try {
    if (isDemo) {
      // 演示模式：返回內存資料
      res.json(memoryDatabase.applications);
    } else {
      // 生產模式：查詢資料庫
      const [rows] = await db.execute(`
        SELECT la.*, ss.name as sales_staff_name 
        FROM loan_applications la
        LEFT JOIN sales_staff ss ON la.assigned_sales_staff_id = ss.id
        ORDER BY la.application_date DESC
      `);
      res.json(rows);
    }
  } catch (error) {
    console.error('獲取案件列表錯誤:', error);
    res.status(500).json({ error: '獲取案件列表失敗' });
  }
});

// API 路由 - 取得業務人員列表
app.get('/api/sales-staff', async (req, res) => {
  try {
    if (isDemo) {
      // 演示模式：返回內存資料
      res.json(memoryDatabase.salesStaff);
    } else {
      // 生產模式：查詢資料庫
      const [rows] = await db.execute('SELECT * FROM sales_staff WHERE is_active = TRUE ORDER BY name');
      res.json(rows);
    }
  } catch (error) {
    console.error('獲取業務人員列表錯誤:', error);
    res.status(500).json({ error: '獲取業務人員列表失敗' });
  }
});

// API 路由 - 新增案件
app.post('/api/applications', async (req, res) => {
  try {
    const applicationData = req.body;
    
    if (isDemo) {
      // 演示模式：添加到內存資料
      const newId = Math.max(...memoryDatabase.applications.map(a => a.id), 0) + 1;
      const caseNumber = `CASE${Date.now()}${String(newId).padStart(3, '0')}`;
      
      const newApplication = {
        id: newId,
        case_number: caseNumber,
        application_date: new Date().toISOString().split('T')[0],
        ...applicationData
      };
      
      // 如果有指定業務人員ID，找到對應的業務人員名稱
      if (applicationData.assigned_sales_staff_id) {
        const salesStaff = memoryDatabase.salesStaff.find(s => s.id === parseInt(applicationData.assigned_sales_staff_id));
        if (salesStaff) {
          newApplication.sales_staff_name = salesStaff.name;
        }
      }
      
      memoryDatabase.applications.push(newApplication);
      res.json(newApplication);
    } else {
      // 生產模式：插入資料庫
      const caseNumber = `CASE${Date.now()}${String(Math.floor(Math.random() * 1000)).padStart(3, '0')}`;
      
      const [result] = await db.execute(`
        INSERT INTO loan_applications 
        (case_number, applicant_line_name, applicant_name, phone, email, source_website, 
         region, assigned_sales_staff_id, loan_type, collateral_item, can_submit, 
         has_negotiated, monthly_payment, installment_periods, disbursement_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `, [
        caseNumber, applicationData.applicant_line_name, applicationData.applicant_name,
        applicationData.phone, applicationData.email, applicationData.source_website,
        applicationData.region, applicationData.assigned_sales_staff_id, applicationData.loan_type,
        applicationData.collateral_item, applicationData.can_submit, applicationData.has_negotiated,
        applicationData.monthly_payment, applicationData.installment_periods, 
        applicationData.disbursement_date, applicationData.status || 'pending'
      ]);
      
      const [newApplication] = await db.execute(`
        SELECT la.*, ss.name as sales_staff_name 
        FROM loan_applications la
        LEFT JOIN sales_staff ss ON la.assigned_sales_staff_id = ss.id
        WHERE la.id = ?
      `, [result.insertId]);
      
      res.json(newApplication[0]);
    }
  } catch (error) {
    console.error('新增案件錯誤:', error);
    res.status(500).json({ error: '新增案件失敗' });
  }
});

// API 路由 - 更新案件
app.put('/api/applications/:id', async (req, res) => {
  try {
    const applicationId = parseInt(req.params.id);
    const updateData = req.body;
    
    if (isDemo) {
      // 演示模式：更新內存資料
      const index = memoryDatabase.applications.findIndex(a => a.id === applicationId);
      if (index !== -1) {
        memoryDatabase.applications[index] = { 
          ...memoryDatabase.applications[index], 
          ...updateData 
        };
        
        // 如果有指定業務人員ID，找到對應的業務人員名稱
        if (updateData.assigned_sales_staff_id) {
          const salesStaff = memoryDatabase.salesStaff.find(s => s.id === parseInt(updateData.assigned_sales_staff_id));
          if (salesStaff) {
            memoryDatabase.applications[index].sales_staff_name = salesStaff.name;
          }
        }
        
        res.json(memoryDatabase.applications[index]);
      } else {
        res.status(404).json({ error: '案件不存在' });
      }
    } else {
      // 生產模式：更新資料庫
      await db.execute(`
        UPDATE loan_applications SET
        applicant_line_name = ?, applicant_name = ?, phone = ?, email = ?,
        source_website = ?, region = ?, assigned_sales_staff_id = ?, loan_type = ?,
        collateral_item = ?, can_submit = ?, has_negotiated = ?, monthly_payment = ?,
        installment_periods = ?, disbursement_date = ?, status = ?
        WHERE id = ?
      `, [
        updateData.applicant_line_name, updateData.applicant_name, updateData.phone,
        updateData.email, updateData.source_website, updateData.region,
        updateData.assigned_sales_staff_id, updateData.loan_type, updateData.collateral_item,
        updateData.can_submit, updateData.has_negotiated, updateData.monthly_payment,
        updateData.installment_periods, updateData.disbursement_date, updateData.status,
        applicationId
      ]);
      
      const [updatedApplication] = await db.execute(`
        SELECT la.*, ss.name as sales_staff_name 
        FROM loan_applications la
        LEFT JOIN sales_staff ss ON la.assigned_sales_staff_id = ss.id
        WHERE la.id = ?
      `, [applicationId]);
      
      res.json(updatedApplication[0]);
    }
  } catch (error) {
    console.error('更新案件錯誤:', error);
    res.status(500).json({ error: '更新案件失敗' });
  }
});

// API 路由 - 刪除案件
app.delete('/api/applications/:id', async (req, res) => {
  try {
    const applicationId = parseInt(req.params.id);
    
    if (isDemo) {
      // 演示模式：從內存資料中刪除
      const index = memoryDatabase.applications.findIndex(a => a.id === applicationId);
      if (index !== -1) {
        memoryDatabase.applications.splice(index, 1);
        res.json({ success: true });
      } else {
        res.status(404).json({ error: '案件不存在' });
      }
    } else {
      // 生產模式：從資料庫刪除
      await db.execute('DELETE FROM loan_applications WHERE id = ?', [applicationId]);
      res.json({ success: true });
    }
  } catch (error) {
    console.error('刪除案件錯誤:', error);
    res.status(500).json({ error: '刪除案件失敗' });
  }
});

// 健康檢查端點
app.get('/health', async (req, res) => {
  const healthInfo = {
    status: 'ok', 
    timestamp: new Date().toISOString(),
    service: '貸款案件管理系統',
    mode: isDemo ? 'demo' : 'production',
    database: isDemo ? 'demo' : 'connected',
    features: {
      lineBot: client ? 'enabled' : 'demo-mode',
      webCRM: 'enabled',
      aiService: process.env.OPENAI_API_KEY ? 'enabled' : 'disabled'
    }
  };

  // 如果是生產模式，檢查資料庫連線
  if (!isDemo && db) {
    try {
      const connection = await db.getConnection();
      await connection.ping();
      connection.release();
      healthInfo.database = 'connected';
    } catch (error) {
      healthInfo.database = 'disconnected';
      healthInfo.status = 'warning';
      healthInfo.databaseError = error.message;
    }
  }

  // 如果是演示模式，加入演示資料統計
  if (isDemo) {
    healthInfo.demoData = {
      applications: memoryDatabase.applications.length,
      salesStaff: memoryDatabase.salesStaff.length,
      note: '演示模式 - 使用內存資料庫'
    };
  }

  res.json(healthInfo);
});

// 啟動服務
async function startServer() {
  try {
    await initDatabase();
    
    app.listen(PORT, () => {
      console.log(`🚀 Line Bot服務啟動成功，Port: ${PORT}`);
      console.log(`📊 健康檢查: http://localhost:${PORT}/health`);
    });
  } catch (error) {
    console.error('啟動服務失敗:', error);
    process.exit(1);
  }
}

startServer();

// 優雅關閉
process.on('SIGTERM', async () => {
  console.log('收到SIGTERM信號，正在關閉服務...');
  if (db) {
    await db.end();
  }
  process.exit(0);
}); 