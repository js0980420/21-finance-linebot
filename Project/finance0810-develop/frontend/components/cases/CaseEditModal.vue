<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- 背景遮罩 -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal"></div>

    <!-- 全螢幕彈窗 -->
    <div class="relative w-full h-full max-w-none max-h-none bg-white overflow-auto">
      <!-- 標題列 -->
      <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? '編輯案件' : '新增案件' }}
        </h2>
        <div class="flex space-x-3">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            取消
          </button>
          <button
            type="button"
            @click="saveCase"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            儲存
          </button>
        </div>
      </div>

      <!-- 表單內容 -->
      <form @submit.prevent="saveCase" class="p-6">
        <!-- 基本資訊區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            基本資訊
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- 案件狀態 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">案件狀態</label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="(label, value) in caseStatusOptions" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>

            <!-- 建立時間 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">建立時間</label>
              <input
                :value="formatDateTime(form.created_at)"
                type="text"
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
            </div>

            <!-- 指派業務 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">指派業務</label>
              <select
                v-model="form.assigned_to"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">未指派</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>

            <!-- 來源管道 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">來源管道</label>
              <select
                v-model="form.channel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">未指定</option>
                <option value="wp_form">網站表單</option>
                <option value="line">官方LINE</option>
                <option value="email">Email</option>
                <option value="phone_call">電話</option>
              </select>
            </div>

            <!-- 客戶姓名 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">客戶姓名 *</label>
              <input
                v-model="form.customer_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- LINE 顯示名稱 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">LINE 顯示名稱</label>
              <input
                v-model="form.line_display_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- LINE 加好友 ID -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">LINE 加好友 ID</label>
              <input
                v-model="form.line_add_friend_id"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 諮詢項目 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">諮詢項目</label>
              <input
                v-model="form.consultation_item"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 網站來源 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">網站來源</label>
              <input
                v-model="form.website_source"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 客戶Email -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                v-model="form.customer_email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 客戶手機 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">手機號碼 *</label>
              <input
                v-model="form.customer_phone"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 案件編號 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">案件編號</label>
              <input
                v-model="form.case_number"
                type="text"
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
            </div>
          </div>
        </div>

        <!-- 個人資料區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            個人資料
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 出生年月日 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">出生年月日</label>
              <input
                v-model="form.birth_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 身份證字號 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">身份證字號</label>
              <input
                v-model="form.id_number"
                type="text"
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 最高學歷 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">最高學歷</label>
              <select
                v-model="form.education"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">請選擇</option>
                <option value="國小">國小</option>
                <option value="國中">國中</option>
                <option value="高中職">高中職</option>
                <option value="專科">專科</option>
                <option value="大學">大學</option>
                <option value="碩士">碩士</option>
                <option value="博士">博士</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 聯絡資訊區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            聯絡資訊
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 地區 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">地區</label>
              <input
                v-model="form.customer_region"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 戶籍地址 -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">戶籍地址</label>
              <input
                v-model="form.home_address"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 室內電話 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">室內電話</label>
              <input
                v-model="form.landline_phone"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 通訊地址是否同戶籍地 -->
            <div class="flex items-center">
              <input
                v-model="form.comm_address_same_as_home"
                @change="handleCommAddressChange"
                type="checkbox"
                id="comm_address_same_as_home"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="comm_address_same_as_home" class="ml-2 block text-sm text-gray-700">
                通訊地址同戶籍地
              </label>
            </div>

            <!-- 通訊地址 -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                通訊地址
                <span v-if="form.comm_address_same_as_home" class="text-sm text-gray-500">(自動同步戶籍地址)</span>
              </label>
              <input
                v-model="form.comm_address"
                type="text"
                :readonly="form.comm_address_same_as_home"
                :class="[
                  'w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                  form.comm_address_same_as_home ? 'bg-gray-50' : 'bg-white'
                ]"
              />
            </div>

            <!-- 通訊電話 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">通訊電話</label>
              <input
                v-model="form.comm_phone"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 現居地住多久 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">現居地住多久</label>
              <input
                v-model="form.residence_duration"
                type="text"
                placeholder="例：2年3個月"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 居住地持有人 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">居住地持有人</label>
              <select
                v-model="form.residence_owner"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">請選擇</option>
                <option value="本人">本人</option>
                <option value="父母">父母</option>
                <option value="配偶">配偶</option>
                <option value="租屋">租屋</option>
                <option value="其他">其他</option>
              </select>
            </div>

            <!-- 電信業者 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">電信業者</label>
              <select
                v-model="form.telecom_operator"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">請選擇</option>
                <option value="中華電信">中華電信</option>
                <option value="台灣大哥大">台灣大哥大</option>
                <option value="遠傳電信">遠傳電信</option>
                <option value="亞太電信">亞太電信</option>
                <option value="台灣之星">台灣之星</option>
                <option value="其他">其他</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 公司資料區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            公司資料
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 公司名稱 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">公司名稱</label>
              <input
                v-model="form.company_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 公司電話 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">公司電話</label>
              <input
                v-model="form.company_phone"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 職稱 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">職稱</label>
              <input
                v-model="form.job_title"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 公司地址 -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">公司地址</label>
              <input
                v-model="form.company_address"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 月收入 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">月收入</label>
              <input
                v-model="form.monthly_income"
                type="number"
                step="0.01"
                placeholder="0.00"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 有無新轉勞保 -->
            <div class="flex items-center">
              <input
                v-model="form.has_labor_insurance"
                type="checkbox"
                id="has_labor_insurance"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="has_labor_insurance" class="ml-2 block text-sm text-gray-700">
                有新轉勞保
              </label>
            </div>

            <!-- 目前公司在職多久 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">目前公司在職多久</label>
              <input
                v-model="form.company_tenure"
                type="text"
                placeholder="例：1年6個月"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- 其他資訊區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            其他資訊
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 需求金額 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">需求金額</label>
              <input
                v-model="form.demand_amount"
                type="number"
                step="0.01"
                placeholder="0.00"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 貸款金額 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">貸款金額</label>
              <input
                v-model="form.loan_amount"
                type="number"
                step="0.01"
                placeholder="0.00"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 貸款類型 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">貸款類型</label>
              <input
                v-model="form.loan_type"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 貸款期數 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">貸款期數（月）</label>
              <input
                v-model="form.loan_term"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 利率 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">利率（%）</label>
              <input
                v-model="form.interest_rate"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 備註 -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">備註</label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- 自定義欄位 -->
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">自定義欄位</label>
              <textarea
                v-model="form.custom_field"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- 緊急聯絡人區塊 -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
            緊急聯絡人
          </h3>

          <!-- 聯絡人① -->
          <div class="mb-6">
            <h4 class="text-md font-medium text-gray-800 mb-3">聯絡人①</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
                <input
                  v-model="form.emergency_contact_1_name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">關係</label>
                <select
                  v-model="form.emergency_contact_1_relationship"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">請選擇</option>
                  <option value="父親">父親</option>
                  <option value="母親">母親</option>
                  <option value="配偶">配偶</option>
                  <option value="子女">子女</option>
                  <option value="兄弟姊妹">兄弟姊妹</option>
                  <option value="朋友">朋友</option>
                  <option value="同事">同事</option>
                  <option value="其他">其他</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">電話</label>
                <input
                  v-model="form.emergency_contact_1_phone"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">方便聯絡時間</label>
                <input
                  v-model="form.contact_time_1"
                  type="text"
                  placeholder="例：平日上午"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
            <div class="mt-2 flex items-center">
              <input
                v-model="form.confidential_1"
                type="checkbox"
                id="confidential_1"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="confidential_1" class="ml-2 block text-sm text-gray-700">
                保密聯絡
              </label>
            </div>
          </div>

          <!-- 聯絡人② -->
          <div class="mb-6">
            <h4 class="text-md font-medium text-gray-800 mb-3">聯絡人②</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
                <input
                  v-model="form.emergency_contact_2_name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">關係</label>
                <select
                  v-model="form.emergency_contact_2_relationship"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">請選擇</option>
                  <option value="父親">父親</option>
                  <option value="母親">母親</option>
                  <option value="配偶">配偶</option>
                  <option value="子女">子女</option>
                  <option value="兄弟姊妹">兄弟姊妹</option>
                  <option value="朋友">朋友</option>
                  <option value="同事">同事</option>
                  <option value="其他">其他</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">電話</label>
                <input
                  v-model="form.emergency_contact_2_phone"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">方便聯絡時間</label>
                <input
                  v-model="form.contact_time_2"
                  type="text"
                  placeholder="例：假日下午"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
            <div class="mt-2 flex items-center">
              <input
                v-model="form.confidential_2"
                type="checkbox"
                id="confidential_2"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="confidential_2" class="ml-2 block text-sm text-gray-700">
                保密聯絡
              </label>
            </div>
          </div>

          <!-- 介紹人 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">介紹人</label>
            <input
              v-model="form.referrer"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isOpen: { type: Boolean, default: false },
  case: { type: Object, default: null }
})

const emit = defineEmits(['close', 'save'])

// 案件狀態選項 - 10種狀態
const caseStatusOptions = {
  'unassigned': '未指派',
  'valid_customer': '有效客',
  'invalid_customer': '無效客',
  'customer_service': '客服',
  'blacklist': '黑名單',
  'approved_disbursed': '核准撥款',
  'approved_undisbursed': '核准未撥',
  'conditional_approval': '附條件',
  'rejected': '婉拒',
  'tracking_management': '追蹤管理'
}

// 可用用戶列表
const availableUsers = ref([])

// 表單資料
const form = reactive({
  id: null,
  status: 'unassigned',
  created_at: null,
  assigned_to: '',
  channel: '',
  customer_name: '',
  line_display_name: '',
  line_add_friend_id: '',
  consultation_item: '',
  website_source: '',
  customer_email: '',
  customer_phone: '',
  case_number: '',
  birth_date: '',
  id_number: '',
  education: '',
  customer_region: '',
  home_address: '',
  landline_phone: '',
  comm_address_same_as_home: false,
  comm_address: '',
  comm_phone: '',
  residence_duration: '',
  residence_owner: '',
  telecom_operator: '',
  company_name: '',
  company_phone: '',
  company_address: '',
  job_title: '',
  monthly_income: '',
  has_labor_insurance: false,
  company_tenure: '',
  demand_amount: '',
  loan_amount: '',
  loan_type: '',
  loan_term: '',
  interest_rate: '',
  custom_field: '',
  notes: '',
  emergency_contact_1_name: '',
  emergency_contact_1_relationship: '',
  emergency_contact_1_phone: '',
  contact_time_1: '',
  confidential_1: false,
  emergency_contact_2_name: '',
  emergency_contact_2_relationship: '',
  emergency_contact_2_phone: '',
  contact_time_2: '',
  confidential_2: false,
  referrer: ''
})

// 重置表單
const resetForm = () => {
  Object.assign(form, {
    id: null,
    status: 'unassigned',
    created_at: new Date().toISOString(),
    assigned_to: '',
    channel: '',
    customer_name: '',
    line_display_name: '',
    line_add_friend_id: '',
    consultation_item: '',
    birth_date: '',
    id_number: '',
    education: '',
    home_address: '',
    landline_phone: '',
    comm_address_same_as_home: false,
    comm_address: '',
    comm_phone: '',
    residence_duration: '',
    residence_owner: '',
    telecom_operator: '',
    company_name: '',
    company_phone: '',
    company_address: '',
    job_title: '',
    monthly_income: null,
    has_labor_insurance: false,
    company_tenure: '',
    demand_amount: null,
    custom_field: '',
    emergency_contact_1_name: '',
    emergency_contact_1_relationship: '',
    emergency_contact_1_phone: '',
    contact_time_1: '',
    confidential_1: false,
    emergency_contact_2_name: '',
    emergency_contact_2_relationship: '',
    emergency_contact_2_phone: '',
    contact_time_2: '',
    confidential_2: false,
    referrer: ''
  })
}

// 是否為編輯模式
const isEdit = computed(() => !!props.case?.id)

// 監聽案件資料變更
watch(() => props.case, (newCase) => {
  if (newCase) {
    Object.assign(form, {
      id: newCase.id || null,
      status: newCase.status || 'unassigned',
      created_at: newCase.created_at || null,
      assigned_to: newCase.assigned_to || '',
      channel: newCase.channel || '',
      customer_name: newCase.customer_name || '',
      line_display_name: newCase.line_display_name || '',
      line_add_friend_id: newCase.line_add_friend_id || '',
      consultation_item: newCase.consultation_item || '',
      website_source: newCase.website_source || '',
      customer_email: newCase.customer_email || '',
      customer_phone: newCase.customer_phone || '',
      case_number: newCase.case_number || '',
      birth_date: newCase.birth_date || '',
      id_number: newCase.id_number || '',
      education: newCase.education || '',
      customer_region: newCase.customer_region || '',
      home_address: newCase.home_address || '',
      landline_phone: newCase.landline_phone || '',
      comm_address_same_as_home: newCase.comm_address_same_as_home || false,
      comm_address: newCase.comm_address || '',
      comm_phone: newCase.comm_phone || '',
      residence_duration: newCase.residence_duration || '',
      residence_owner: newCase.residence_owner || '',
      telecom_operator: newCase.telecom_operator || '',
      company_name: newCase.company_name || '',
      company_phone: newCase.company_phone || '',
      company_address: newCase.company_address || '',
      job_title: newCase.job_title || '',
      monthly_income: newCase.monthly_income || '',
      has_labor_insurance: newCase.has_labor_insurance || false,
      company_tenure: newCase.company_tenure || '',
      demand_amount: newCase.demand_amount || '',
      loan_amount: newCase.loan_amount || '',
      loan_type: newCase.loan_type || '',
      loan_term: newCase.loan_term || '',
      interest_rate: newCase.interest_rate || '',
      custom_field: newCase.custom_field || '',
      notes: newCase.notes || '',
      emergency_contact_1_name: newCase.emergency_contact_1_name || '',
      emergency_contact_1_relationship: newCase.emergency_contact_1_relationship || '',
      emergency_contact_1_phone: newCase.emergency_contact_1_phone || '',
      contact_time_1: newCase.contact_time_1 || '',
      confidential_1: newCase.confidential_1 || false,
      emergency_contact_2_name: newCase.emergency_contact_2_name || '',
      emergency_contact_2_relationship: newCase.emergency_contact_2_relationship || '',
      emergency_contact_2_phone: newCase.emergency_contact_2_phone || '',
      contact_time_2: newCase.contact_time_2 || '',
      confidential_2: newCase.confidential_2 || false,
      referrer: newCase.referrer || ''
    })
  } else {
    // 重置表單
    resetForm()
  }
}, { immediate: true })

// 關閉彈窗
const closeModal = () => {
  emit('close')
}

// 處理通訊地址同步
const handleCommAddressChange = () => {
  if (form.comm_address_same_as_home) {
    form.comm_address = form.home_address
  }
}

// 監聽戶籍地址變更，自動同步通訊地址
watch(() => form.home_address, (newAddress) => {
  if (form.comm_address_same_as_home) {
    form.comm_address = newAddress
  }
})

// 儲存案件
const saveCase = () => {
  // 基本驗證
  if (!form.customer_name.trim()) {
    alert('請輸入客戶姓名')
    return
  }
  if (!form.customer_phone.trim()) {
    alert('請輸入手機號碼')
    return
  }

  // 如果通訊地址同戶籍地，則確保複製戶籍地址
  if (form.comm_address_same_as_home) {
    form.comm_address = form.home_address
  }

  emit('save', { ...form })
}

// 格式化日期時間
const formatDateTime = (dateTime) => {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 載入可用用戶列表
const { getUsers } = useUsers()
const loadUsers = async () => {
  try {
    const { success, users, error } = await getUsers({ per_page: 250 })
    if (success && Array.isArray(users)) {
      availableUsers.value = users
    }
  } catch (error) {
    console.error('載入用戶列表失敗:', error)
  }
}

// 監聽按鍵事件，ESC 關閉彈窗
const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.isOpen) {
    closeModal()
  }
}

onMounted(() => {
  loadUsers()
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* 確保全螢幕樣式 */
.fixed.inset-0 {
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

/* 滾動條美化 */
.overflow-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>