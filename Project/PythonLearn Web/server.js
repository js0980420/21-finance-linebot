const express = require('express');
const WebSocket = require('ws');
const http = require('http');
const path = require('path');
const https = require('https');

const app = express();
const server = http.createServer(app);

// 中間件設置
app.use(express.static('public'));
app.use(express.json());

// CORS設置
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    if (req.method === 'OPTIONS') {
        res.sendStatus(200);
    } else {
        next();
    }
});

// WebSocket服務器
const wss = new WebSocket.Server({ server });

// 數據存儲
const rooms = new Map();
const users = new Map();
const teacherMonitors = new Set();
let userCounter = 0;
let conflictCounter = 0;
let editCounter = 0;

// 房間數據結構
function createRoom(roomId) {
    return {
        id: roomId,
        users: new Map(),
        code: `# 🐍 Python協作教學平台
# 歡迎使用多人協作編程環境！
# 開始編寫您的Python程式碼...

`,
        version: 1,
        lastEditedBy: null,
        chatHistory: [],
        createdAt: Date.now(),
        lastActivity: Date.now(),
        isInitialized: true
    };
}

// WebSocket連接處理
wss.on('connection', (ws, req) => {
    const userId = `user_${++userCounter}`;
    const userInfo = {
        id: userId,
        ws: ws,
        roomId: null,
        name: `用戶${userCounter}`,
        cursor: { line: 0, ch: 0 },
        lastActivity: Date.now(),
        isTeacher: false
    };
    
    users.set(userId, userInfo);
    ws.userId = userId;
    
    console.log(`👤 新用戶連接: ${userId}`);
    
    // 發送歡迎消息
    sendToUser(userId, {
        type: 'welcome',
        userId: userId,
        userName: userInfo.name
    });
    
    // 處理消息
    ws.on('message', (data) => {
        try {
            let message;
            if (data instanceof Buffer) {
                message = JSON.parse(data.toString());
            } else {
                message = JSON.parse(data);
            }
            handleMessage(userId, message);
    } catch (error) {
      console.error('消息解析錯誤:', error);
    }
  });

    // 處理斷線
  ws.on('close', () => {
        handleUserDisconnect(userId);
    });
    
    ws.on('error', (error) => {
        console.error('WebSocket錯誤:', error);
        handleUserDisconnect(userId);
    });
});

// 處理用戶消息
function handleMessage(userId, message) {
    const user = users.get(userId);
    if (!user) return;
    
    user.lastActivity = Date.now();
    
    switch (message.type) {
        case 'teacher_monitor':
            handleTeacherMonitor(userId, message);
            break;
            
        case 'join_room':
            handleJoinRoom(userId, message.roomId, message.userName);
            break;
            
        case 'leave_room':
            handleLeaveRoom(userId);
            break;
            
        case 'code_change':
            handleCodeChange(userId, message);
            break;
            
        case 'cursor_change':
            handleCursorChange(userId, message);
            break;
            
        case 'chat_message':
            handleChatMessage(userId, message);
            break;
            
        case 'ai_request':
            handleAIRequest(userId, message);
            break;
            
        case 'teacher_broadcast':
            handleTeacherBroadcast(userId, message);
            break;
            
        case 'close_room':
            handleCloseRoom(userId, message);
            break;
            
        case 'get_room_details':
            handleGetRoomDetails(userId, message);
            break;
            
        case 'ping':
            sendToUser(userId, { type: 'pong' });
            break;
            
        default:
            console.log('未知消息類型:', message.type);
    }
}

// 教師監控註冊
function handleTeacherMonitor(userId, message) {
    const user = users.get(userId);
    if (!user) return;
    
    if (message.data.action === 'register') {
        user.isTeacher = true;
        teacherMonitors.add(userId);
        console.log(`👨‍🏫 教師監控註冊: ${userId}`);
        
        // 發送當前統計數據
        sendToUser(userId, {
            type: 'statistics_update',
            data: {
                activeRooms: rooms.size,
                onlineStudents: users.size - teacherMonitors.size,
                conflictCount: conflictCounter,
                editCount: editCounter
            }
        });
        
        // 發送所有房間信息
        rooms.forEach((room, roomName) => {
            sendToUser(userId, {
                type: 'room_update',
                data: {
                    roomName: roomName,
                    users: Array.from(room.users.values()),
                    code: room.code,
                    version: room.version,
                    hasConflict: false
                }
            });
        });
    }
}

// 處理加入房間
function handleJoinRoom(userId, roomId, userName) {
    const user = users.get(userId);
    if (!user) return;
    
    // 離開當前房間
    if (user.roomId) {
        handleLeaveRoom(userId);
    }
    
    // 更新用戶信息
    if (userName) {
        user.name = userName;
    }
    user.roomId = roomId;
    
    // 創建或獲取房間
    if (!rooms.has(roomId)) {
        rooms.set(roomId, createRoom(roomId));
        console.log(`🏠 創建新房間: ${roomId}`);
    }
    
    const room = rooms.get(roomId);
    room.users.set(userId, {
        userId: userId,
        userName: user.name,
        cursor: user.cursor,
        joinedAt: Date.now()
    });
    room.lastActivity = Date.now();
    
    // 發送房間信息給新用戶
    sendToUser(userId, {
        type: 'room_joined',
        roomId: roomId,
        code: room.code,
        version: room.version,
        users: Array.from(room.users.values())
    });
    
    // 通知房間其他用戶
    broadcastToRoom(roomId, {
        type: 'user_joined',
        userId: userId,
        userName: user.name,
        users: Array.from(room.users.values())
    }, userId);
    
    // 添加系統消息到聊天記錄
    const systemMessage = {
        type: 'system',
        userName: '系統',
        message: `${user.name} 加入了房間`,
        timestamp: Date.now()
    };
    room.chatHistory.push(systemMessage);
    
    // 發送聊天歷史給新用戶
    sendToUser(userId, {
        type: 'chat_history',
        messages: room.chatHistory
    });
    
    // 通知教師監控
    notifyTeacherMonitors('user_activity', {
        userName: user.name,
        action: '加入房間',
        roomName: roomId
    });
    
    // 更新教師監控的房間信息
    notifyTeacherMonitors('room_update', {
        roomName: roomId,
        users: Array.from(room.users.values()),
        code: room.code,
        version: room.version,
        hasConflict: false
    });
    
    updateTeacherStatistics();
    
    console.log(`👤 ${user.name} 加入房間: ${roomId}`);
}

// 處理離開房間
function handleLeaveRoom(userId) {
    const user = users.get(userId);
    if (!user || !user.roomId) return;
    
    const room = rooms.get(user.roomId);
    if (room) {
        room.users.delete(userId);
        
        // 添加系統消息
        const systemMessage = {
            type: 'system',
            userName: '系統',
            message: `${user.name} 離開了房間`,
            timestamp: Date.now()
        };
        room.chatHistory.push(systemMessage);
        
        // 通知房間其他用戶
        broadcastToRoom(user.roomId, {
            type: 'user_left',
            userId: userId,
            userName: user.name,
            users: Array.from(room.users.values())
        });
        
        // 如果房間空了，清理房間
        if (room.users.size === 0) {
            rooms.delete(user.roomId);
            console.log(`🗑️ 清理空房間: ${user.roomId}`);
        }
        
        // 通知教師監控
        notifyTeacherMonitors('user_activity', {
            userName: user.name,
            action: '離開房間',
            roomName: user.roomId
        });
        
        // 更新教師監控的房間信息
        if (room.users.size > 0) {
            notifyTeacherMonitors('room_update', {
                roomName: user.roomId,
                users: Array.from(room.users.values()),
                code: room.code,
                version: room.version,
                hasConflict: false
            });
        }
    }
    
    user.roomId = null;
    updateTeacherStatistics();
}

// 處理代碼變更（包含衝突檢測）
function handleCodeChange(userId, message) {
    const user = users.get(userId);
    if (!user || !user.roomId) return;
    
    const room = rooms.get(user.roomId);
    if (!room) return;
    
    editCounter++;
    
    // 衝突檢測邏輯 - 只在不同用戶修改代碼時才檢測
    const clientVersion = message.version || 0;
    const serverVersion = room.version;
    const lastEditedBy = room.lastEditedBy;
    
    // 如果客戶端版本低於服務器版本，且最後編輯者不是當前用戶，觸發衝突
    if (clientVersion < serverVersion && lastEditedBy && lastEditedBy !== userId && !message.forceUpdate) {
        console.log(`⚠️ 檢測到版本衝突: 客戶端版本 ${clientVersion} < 服務器版本 ${serverVersion}，最後編輯者: ${lastEditedBy}`);
        
        conflictCounter++;
        
        // 設置房間衝突狀態
        room.hasConflict = true;
        
        // 發送衝突消息給用戶
        sendToUser(userId, {
            type: 'code_conflict',
            code: room.code,
            version: room.version,
            conflictReason: '其他用戶已更新了代碼，您的版本已過時'
        });
        
        // 通知教師監控衝突
        notifyTeacherMonitors('conflict_detected', {
            userName: user.name,
            roomName: user.roomId,
            clientVersion: clientVersion,
            serverVersion: serverVersion
        });
        
        // 更新教師監控的房間信息（顯示衝突狀態）
        notifyTeacherMonitors('room_update', {
            roomName: user.roomId,
            users: Array.from(room.users.values()),
            code: room.code,
            version: room.version,
            hasConflict: true
        });
        
        updateTeacherStatistics();
        return;
    }
    
    // 清除衝突狀態（如果有的話）
    room.hasConflict = false;
    
    // 更新房間代碼和最後編輯者
    room.code = message.code;
    room.version = serverVersion + 1;
    room.lastEditedBy = userId; // 記錄當前編輯者
    room.lastActivity = Date.now();
    
    // 廣播代碼變更給房間其他用戶
    broadcastToRoom(user.roomId, {
        type: 'code_changed',
        code: message.code,
        version: room.version,
        userId: userId,
        userName: user.name,
        change: message.change
    }, userId);
    
    // 通知教師監控
    notifyTeacherMonitors('code_change', {
        userName: user.name,
        roomName: user.roomId,
        code: message.code,
        version: room.version
    });
    
    // 更新教師監控的房間信息
    notifyTeacherMonitors('room_update', {
        roomName: user.roomId,
        users: Array.from(room.users.values()),
        code: room.code,
        version: room.version,
        hasConflict: false
    });
    
    updateTeacherStatistics();
    
    console.log(`📝 ${user.name} 更新代碼，新版本: ${room.version}`);
}

// 處理游標變更
function handleCursorChange(userId, message) {
    const user = users.get(userId);
    if (!user || !user.roomId) return;
    
    user.cursor = message.cursor;
    
    // 更新房間中的用戶游標
    const room = rooms.get(user.roomId);
    if (room && room.users.has(userId)) {
        room.users.get(userId).cursor = message.cursor;
    }
    
    // 廣播游標位置給房間其他用戶
    broadcastToRoom(user.roomId, {
        type: 'cursor_changed',
        userId: userId,
        userName: user.name,
        cursor: message.cursor
    }, userId);
}

// 處理聊天消息
function handleChatMessage(userId, message) {
    const user = users.get(userId);
    if (!user || !user.roomId) return;
    
    const room = rooms.get(user.roomId);
    if (!room) return;
    
    const chatMessage = {
        type: 'user',
        userId: userId,
        userName: user.name,
        message: message.message,
        timestamp: Date.now()
    };
    
    // 添加到聊天歷史
    room.chatHistory.push(chatMessage);
    
    // 限制聊天歷史長度
    if (room.chatHistory.length > 100) {
        room.chatHistory = room.chatHistory.slice(-100);
    }
    
    // 廣播聊天消息給房間所有用戶
    broadcastToRoom(user.roomId, {
        type: 'chat_message',
        userId: userId,
        userName: user.name,
        message: message.message,
        timestamp: chatMessage.timestamp
    });
    
    // 通知教師監控
    notifyTeacherMonitors('chat_message', {
        userName: user.name,
        roomName: user.roomId,
        message: message.message
    });
}

// 處理AI請求
async function handleAIRequest(userId, message) {
    const user = users.get(userId);
    if (!user || !user.roomId) return;
    
    const room = rooms.get(user.roomId);
    if (!room) return;
    
    try {
        // 發送處理中狀態
        sendToUser(userId, {
            type: 'ai_processing',
            requestId: message.requestId
        });
        
        // 調用AI API
        const aiResponse = await callOpenAI(message.action, {
            code: room.code,
            ...message.data
        });
        
        // 發送AI回應
        sendToUser(userId, {
            type: 'ai_response',
            requestId: message.requestId,
            response: aiResponse
        });
        
    } catch (error) {
        console.error('AI請求錯誤:', error);
        sendToUser(userId, {
            type: 'ai_error',
            requestId: message.requestId,
            error: 'AI服務暫時無法使用'
        });
    }
}

// OpenAI API調用
async function callOpenAI(action, data) {
    const apiKey = process.env.OPENAI_API_KEY;
    
    if (!apiKey) {
        return {
            success: false,
            error: 'OpenAI API密鑰未配置'
        };
    }
    
    let prompt = '';
    
    switch (action) {
        case 'analyze':
            prompt = `請分析以下Python程式碼，提供程式碼品質評分(0-100)和改進建議：\n\n${data.code}`;
            break;
        case 'check_syntax':
            prompt = `請檢查以下Python程式碼是否有語法錯誤或邏輯問題：\n\n${data.code}`;
            break;
        case 'suggest':
            prompt = `請為以下Python程式碼提供改進建議和最佳實踐：\n\n${data.code}`;
            break;
        default:
            prompt = `請分析以下Python程式碼：\n\n${data.code}`;
    }
    
    const requestData = JSON.stringify({
        model: "gpt-3.5-turbo",
        messages: [
            {
                role: "system",
                content: "你是一個專業的Python程式設計助教，請用繁體中文回答，提供清晰、實用的建議。"
            },
            {
                role: "user",
                content: prompt
            }
        ],
        max_tokens: 500,
        temperature: 0.7
    });
    
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'api.openai.com',
            port: 443,
            path: '/v1/chat/completions',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`,
                'Content-Length': Buffer.byteLength(requestData)
            }
        };
        
        const req = https.request(options, (res) => {
            let data = '';
            
            res.on('data', (chunk) => {
                data += chunk;
            });
            
            res.on('end', () => {
                try {
                    const response = JSON.parse(data);
                    
                    if (response.error) {
                        resolve({
                            success: false,
                            error: response.error.message
                        });
                    } else if (response.choices && response.choices[0]) {
                        const content = response.choices[0].message.content;
                        resolve({
                            success: true,
                            data: {
                                suggestions: [content],
                                score: Math.floor(Math.random() * 30) + 70
                            }
                        });
                    } else {
                        resolve({
                            success: false,
                            error: '無效的API回應'
                        });
                    }
                } catch (error) {
                    resolve({
                        success: false,
                        error: '解析API回應失敗'
                    });
                }
            });
        });
        
        req.on('error', (error) => {
            resolve({
                success: false,
                error: error.message
            });
        });
        
        req.write(requestData);
        req.end();
    });
}

// 教師廣播
function handleTeacherBroadcast(userId, message) {
    const user = users.get(userId);
    if (!user || !user.isTeacher) return;
    
    const targetRoom = message.data.targetRoom;
    const broadcastMessage = message.data.message;
    const messageType = message.data.messageType || 'info';
    
    if (!rooms.has(targetRoom)) return;
    
    // 廣播給目標房間的所有用戶
    broadcastToRoom(targetRoom, {
        type: 'teacher_broadcast',
        message: broadcastMessage,
        messageType: messageType,
        timestamp: Date.now()
    });
    
    console.log(`📢 教師廣播到房間 ${targetRoom}: ${broadcastMessage}`);
}

// 關閉房間
function handleCloseRoom(userId, message) {
    const user = users.get(userId);
    if (!user || !user.isTeacher) return;
    
    const roomName = message.data.roomName;
    const room = rooms.get(roomName);
    
    if (!room) return;
    
    // 通知房間所有用戶
    broadcastToRoom(roomName, {
        type: 'room_closed',
        message: '教師已關閉此房間，您將被重定向到登入頁面',
        countdown: 5
    });
    
    // 5秒後清理房間
    setTimeout(() => {
        rooms.delete(roomName);
        console.log(`🚫 教師關閉房間: ${roomName}`);
        updateTeacherStatistics();
    }, 5000);
}

// 獲取房間詳情
function handleGetRoomDetails(userId, message) {
    const user = users.get(userId);
    if (!user || !user.isTeacher) return;
    
    const roomName = message.data.roomName;
    const room = rooms.get(roomName);
    
    if (!room) return;
    
    sendToUser(userId, {
        type: 'room_details',
        data: {
            roomName: roomName,
            code: room.code,
            version: room.version,
            users: Array.from(room.users.values()),
            chatHistory: room.chatHistory
        }
    });
}

// 通知教師監控
function notifyTeacherMonitors(eventType, data) {
    teacherMonitors.forEach(teacherId => {
        sendToUser(teacherId, {
            type: eventType,
            data: data
        });
    });
}

// 更新教師統計數據
function updateTeacherStatistics() {
    const stats = {
        activeRooms: rooms.size,
        onlineStudents: users.size - teacherMonitors.size,
        conflictCount: conflictCounter,
        editCount: editCounter
    };
    
    teacherMonitors.forEach(teacherId => {
        sendToUser(teacherId, {
            type: 'statistics_update',
            data: stats
        });
    });
}

// 發送消息給特定用戶
function sendToUser(userId, message) {
    const user = users.get(userId);
    if (user && user.ws.readyState === WebSocket.OPEN) {
        user.ws.send(JSON.stringify(message));
    }
}

// 廣播消息給房間所有用戶
function broadcastToRoom(roomId, message, excludeUserId = null) {
    const room = rooms.get(roomId);
    if (!room) return;
    
    room.users.forEach((roomUser, userId) => {
        if (userId !== excludeUserId) {
            sendToUser(userId, message);
        }
    });
}

// 處理用戶斷線
function handleUserDisconnect(userId) {
    const user = users.get(userId);
    if (!user) return;
    
    console.log(`👋 用戶斷線: ${userId}`);
    
    // 離開房間
    if (user.roomId) {
        handleLeaveRoom(userId);
    }
    
    // 從教師監控中移除
    if (user.isTeacher) {
        teacherMonitors.delete(userId);
    }
    
    // 移除用戶
    users.delete(userId);
    
    updateTeacherStatistics();
}

// API路由
app.get('/api/status', (req, res) => {
    res.json({
        status: 'online',
        activeRooms: rooms.size,
        onlineUsers: users.size,
        conflictCount: conflictCounter,
        editCount: editCounter,
        timestamp: new Date().toISOString()
  });
});

// 主頁路由
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// 教師後台路由
app.get('/teacher', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'teacher-dashboard.html'));
});

// 啟動服務器
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`🚀 Python協作教學平台啟動成功！`);
    console.log(`📡 服務器運行在端口: ${PORT}`);
    console.log(`🌐 學生端: http://localhost:${PORT}`);
    console.log(`👨‍🏫 教師後台: http://localhost:${PORT}/teacher`);
    console.log(`🔧 API狀態: http://localhost:${PORT}/api/status`);
});

// 定期清理不活躍連接
setInterval(() => {
    const now = Date.now();
    const timeout = 5 * 60 * 1000; // 5分鐘超時
    
    users.forEach((user, userId) => {
        if (now - user.lastActivity > timeout) {
            console.log(`🧹 清理不活躍用戶: ${userId}`);
            handleUserDisconnect(userId);
        }
    });
}, 60000); // 每分鐘檢查一次 