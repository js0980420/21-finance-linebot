/**
 * Line Bot 業務功能測試腳本
 * 模擬業務人員與 Line Bot 的互動
 */

const axios = require('axios');

const BASE_URL = 'http://localhost:3000';

// 模擬不同角色的業務人員 Line ID
const DEMO_USERS = {
  sales1: 'U001',    // 王小明 (業務)
  sales2: 'U002',    // 李美華 (業務)
  manager: 'U006',   // 黃主管 (主管)
  admin: 'U009'      // 總經理 (管理者)
};

// 模擬 Line Bot Webhook 請求
async function simulateLineMessage(userId, messageText) {
  const payload = {
    destination: 'test-destination',
    events: [{
      type: 'message',
      mode: 'active',
      timestamp: Date.now(),
      source: {
        type: 'user',
        userId: userId
      },
      message: {
        id: Date.now().toString(),
        type: 'text',
        text: messageText
      },
      replyToken: 'test-reply-token'
    }]
  };

  try {
    console.log(`\n🔄 模擬用戶 ${userId} 發送訊息: "${messageText}"`);
    console.log('📤 發送 Webhook 請求...');
    
    // 注意：演示模式下，實際上不會發送 Line 訊息，但會執行業務邏輯
    const response = await axios.post(`${BASE_URL}/webhook`, payload, {
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    console.log('✅ Webhook 處理完成');
    return response.data;
  } catch (error) {
    console.error('❌ Webhook 錯誤:', error.response?.data || error.message);
    return null;
  }
}

// 測試案例
async function runTests() {
  console.log('🚀 開始測試 Line Bot 業務功能\n');
  console.log('============================================');
  
  // 測試 1: 業務人員王小明查看客戶列表
  console.log('\n📋 測試 1: 業務人員查看客戶列表');
  await simulateLineMessage(DEMO_USERS.sales1, '客戶列表');
  
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  // 測試 2: 業務人員查看案件管理
  console.log('\n📊 測試 2: 業務人員查看案件管理');
  await simulateLineMessage(DEMO_USERS.sales1, '案件管理');
  
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  // 測試 3: 主管查看統計報表
  console.log('\n📈 測試 3: 主管查看統計報表');
  await simulateLineMessage(DEMO_USERS.manager, '統計報表');
  
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  // 測試 4: 搜尋特定客戶
  console.log('\n🔍 測試 4: 搜尋特定客戶');
  await simulateLineMessage(DEMO_USERS.sales1, '客戶:張小花');
  
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  // 測試 5: 管理者查看全部客戶
  console.log('\n👨‍💼 測試 5: 管理者查看全部客戶');
  await simulateLineMessage(DEMO_USERS.admin, '客戶列表');
  
  await new Promise(resolve => setTimeout(resolve, 1000));
  
  // 測試 6: 非業務人員訊息（應顯示一般選單）
  console.log('\n👤 測試 6: 一般客戶訊息');
  await simulateLineMessage('U999', '你好');
  
  console.log('\n============================================');
  console.log('✅ 所有測試完成！');
  console.log('\n📝 功能說明:');
  console.log('1. 業務人員可透過 Line 查看負責客戶');
  console.log('2. 點擊客戶 Line 名稱可直接跳轉對話');
  console.log('3. 不同權限角色看到不同範圍的資料');
  console.log('4. 支援客戶搜尋功能');
  console.log('5. 一般客戶會看到標準服務選單');
  
  console.log('\n🌐 網頁版管理系統: http://localhost:3000');
  console.log('👥 演示帳號:');
  console.log('  - 總經理: admin123');
  console.log('  - 黃主管: manager123');
  console.log('  - 王小明: sales123');
}

// 檢查服務狀態
async function checkServiceHealth() {
  try {
    const response = await axios.get(`${BASE_URL}/health`);
    console.log('✅ 服務狀態正常:', response.data.service);
    return true;
  } catch (error) {
    console.error('❌ 服務未啟動，請先執行 npm start');
    return false;
  }
}

// 主程式
async function main() {
  console.log('🔍 檢查服務狀態...');
  
  if (await checkServiceHealth()) {
    await runTests();
  } else {
    console.log('\n💡 請先啟動服務:');
    console.log('   npm start');
    process.exit(1);
  }
}

// 執行測試
if (require.main === module) {
  main().catch(console.error);
}

module.exports = {
  simulateLineMessage,
  DEMO_USERS
}; 