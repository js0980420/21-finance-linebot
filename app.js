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

const app = express();
const PORT = process.env.PORT || 3000;

// Line Bot 設定 - 使用 Zeabur 環境變數
const config = {
  channelAccessToken: process.env.LINE_CHANNEL_ACCESS_TOKEN,
  channelSecret: process.env.LINE_CHANNEL_SECRET,
};

const client = new line.Client(config);

// OpenAI 設定 - 使用 Zeabur 環境變數
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

// 資料庫設定
const dbConfig = {
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  port: process.env.DB_PORT || 3306,
  ssl: process.env.DB_SSL === 'true' ? {
    rejectUnauthorized: false
  } : false
};

// 安全性中間件
app.use(helmet());
app.use(cors());

// 請求限制
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15分鐘
  max: 100 // 限制每個IP最多100個請求
});
app.use(limiter);

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 資料庫連線池
let db;

async function initDatabase() {
  try {
    db = await mysql.createPool(dbConfig);
    console.log('資料庫連線成功');
    
    // 建立資料表
    await createTables();
  } catch (error) {
    console.error('資料庫連線失敗:', error);
  }
}

// 建立資料表
async function createTables() {
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
    
    // 業務表
    `CREATE TABLE IF NOT EXISTS sales_staff (
      id INT AUTO_INCREMENT PRIMARY KEY,
      line_user_id VARCHAR(255) UNIQUE NOT NULL,
      name VARCHAR(100) NOT NULL,
      department VARCHAR(100),
      email VARCHAR(255),
      phone VARCHAR(20),
      is_active BOOLEAN DEFAULT TRUE,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    
    // 表單提交記錄表
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

  for (const table of tables) {
    try {
      await db.execute(table);
      console.log('資料表建立成功');
    } catch (error) {
      console.error('建立資料表失敗:', error);
    }
  }
}

// Line Bot Webhook
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
      await replyMessage(userId, '請先完成註冊才能聯絡專屬業務。');
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

// 定期提醒排程
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

// 健康檢查端點
app.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    service: '中租經銷商 Line Bot'
  });
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