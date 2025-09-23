<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">有效客</h1>
        <p class="text-gray-600 mt-2">已確認為有效客戶的案件</p>
      </div>
      <div class="flex space-x-3">
        <button
          v-if="authStore?.hasPermission && authStore.hasPermission('customer_management')"
          @click="openCreateCaseModal"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2"
        >
          <PlusIcon class="w-5 h-5" />
          <span>新增案件</span>
        </button>
      </div>
    </div>

    <!-- Cases DataTable -->
    <DataTable
      title="有效客案件列表"
      :columns="tableColumns"
      :data="cases"
      :loading="loading"
      :error="loadError"
      :search-query="search"
      search-placeholder="搜尋客戶姓名/手機/Email..."
      :current-page="pagination.currentPage"
      :items-per-page="pagination.perPage"
      loading-text="載入中..."
      empty-text="沒有有效客案件"
      @search="handleSearch"
      @refresh="loadCases"
      @retry="loadCases"
      @page-change="handlePageChange"
      @page-size-change="handlePageSizeChange"
    >
      <!-- Filter Controls -->
      <template #filters>
        <select v-model="selectedChannel" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">全部來源</option>
          <option value="wp">網站表單</option>
          <option value="lineoa">官方賴</option>
          <option value="email">Email</option>
          <option value="phone">電話</option>
        </select>
        <select v-model="selectedAssignee" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">全部業務</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
        </select>
      </template>

      <!-- Custom Table Cells -->
      <template #cell-customer_name="{ item }">
        <div class="font-medium text-gray-900">{{ item.customer_name || '-' }}</div>
      </template>

      <template #cell-assigned_user="{ item }">
        <div v-if="item.assignedUser" class="flex items-center space-x-2">
          <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs">
            {{ item.assignedUser.name.charAt(0) }}
          </div>
          <span>{{ item.assignedUser.name }}</span>
        </div>
        <span v-else class="text-gray-400">未指派</span>
      </template>

      <template #cell-channel="{ item }">
        <span class="px-2 py-1 text-xs rounded-full" :class="getChannelClass(item.channel)">
          {{ getChannelLabel(item.channel) }}
        </span>
      </template>

      <template #cell-demand_amount="{ item }">
        {{ formatCurrency(item.demand_amount) }}
      </template>

      <template #cell-status_updated_at="{ item }">
        <div v-if="item.status_updated_at">
          <div>{{ formatDate(item.status_updated_at) }}</div>
          <div class="text-sm text-gray-500">{{ formatTime(item.status_updated_at) }}</div>
        </div>
        <span v-else>-</span>
      </template>

      <!-- Actions Cell -->
      <template #cell-actions="{ item }">
        <div class="flex items-center space-x-2 justify-end">
          <button
            @click="openEditCaseModal(item)"
            class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-all duration-200"
            title="編輯案件"
          >
            <PencilIcon class="w-4 h-4" />
          </button>
          <button
            @click="changeStatus(item)"
            class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-all duration-200"
            title="變更狀態"
          >
            <ArrowPathIcon class="w-4 h-4" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- 案件編輯彈窗 -->
    <CaseEditModal
      :is-open="showCaseEditModal"
      :case="editingCase"
      @close="closeCaseEditModal"
      @save="handleCaseSave"
    />
  </div>
</template>

<script setup>
import {
  PencilIcon,
  PlusIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import DataTable from '~/components/DataTable.vue'
import CaseEditModal from '~/components/cases/CaseEditModal.vue'

definePageMeta({ middleware: 'auth' })
const authStore = useAuthStore()

// Composables
const { pagination, updatePagination } = usePagination(10)
const { success, error: showError, confirm } = useNotification()
const { getUsers } = useUsers()

// State
const cases = ref([])
const users = ref([])
const loading = ref(false)
const search = ref('')
const selectedChannel = ref('')
const selectedAssignee = ref('')
const loadError = ref(null)

// Case edit modal
const showCaseEditModal = ref(false)
const editingCase = ref(null)

// Table columns
const tableColumns = computed(() => [
  {
    key: 'customer_name',
    title: '客戶姓名',
    sortable: true,
    width: '120px'
  },
  {
    key: 'customer_phone',
    title: '客戶手機',
    sortable: false,
    width: '120px',
    formatter: (value) => value || '-'
  },
  {
    key: 'assigned_user',
    title: '指派業務',
    sortable: false,
    width: '120px'
  },
  {
    key: 'channel',
    title: '來源管道',
    sortable: false,
    width: '100px'
  },
  {
    key: 'consultation_item',
    title: '諮詢項目',
    sortable: false,
    width: '120px',
    formatter: (value) => value || '-'
  },
  {
    key: 'demand_amount',
    title: '需求金額',
    sortable: true,
    width: '120px'
  },
  {
    key: 'status_updated_at',
    title: '狀態更新時間',
    sortable: true,
    width: '140px'
  },
  {
    key: 'actions',
    title: '操作',
    sortable: false,
    width: '100px'
  }
])

// Methods
const loadCases = async () => {
  loading.value = true
  loadError.value = null

  try {
    // TODO: 實際API調用
    // const { items, meta, success: ok, error } = await listCases({
    //   page: pagination.currentPage,
    //   per_page: pagination.perPage,
    //   search: search.value.trim(),
    //   status: 'valid_customer',
    //   channel: selectedChannel.value,
    //   assigned_to: selectedAssignee.value
    // })

    // 暫時使用模擬數據
    const mockData = {
      items: [],
      meta: {
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1
      }
    }

    cases.value = mockData.items
    updatePagination(mockData.meta)
  } catch (err) {
    loadError.value = '載入案件數據時發生錯誤'
    console.error('Load cases error:', err)
  } finally {
    loading.value = false
  }
}

const loadUsers = async () => {
  try {
    const { success: ok, users: list } = await getUsers({ per_page: 250 })
    if (ok && Array.isArray(list)) users.value = list
  } catch (e) {
    console.warn('Load users failed:', e)
  }
}

const handleSearch = (query) => {
  search.value = query
}

const handlePageChange = (page) => {
  pagination.currentPage = page
}

const handlePageSizeChange = (size) => {
  pagination.perPage = size
  pagination.currentPage = 1
}

// Case modal methods
const openCreateCaseModal = () => {
  editingCase.value = { status: 'valid_customer' }
  showCaseEditModal.value = true
}

const openEditCaseModal = (case_) => {
  editingCase.value = case_
  showCaseEditModal.value = true
}

const closeCaseEditModal = () => {
  showCaseEditModal.value = false
  editingCase.value = null
}

const handleCaseSave = async (caseData) => {
  try {
    // TODO: 實際API調用
    success('案件保存成功')
    closeCaseEditModal()
    await loadCases()
  } catch (err) {
    showError('案件保存失敗，請稍後再試')
    console.error('Save case error:', err)
  }
}

const changeStatus = async (case_) => {
  // TODO: 實現狀態變更功能
  showError('狀態變更功能開發中')
}

// Helper methods
const getChannelClass = (channel) => {
  const classes = {
    wp: 'bg-blue-100 text-blue-800',
    lineoa: 'bg-green-100 text-green-800',
    email: 'bg-purple-100 text-purple-800',
    phone: 'bg-orange-100 text-orange-800'
  }
  return classes[channel] || 'bg-gray-100 text-gray-800'
}

const getChannelLabel = (channel) => {
  const labels = {
    wp: '網站表單',
    lineoa: '官方賴',
    email: 'Email',
    phone: '電話'
  }
  return labels[channel] || channel || '-'
}

const formatCurrency = (amount) => {
  if (!amount) return '-'
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}/${month}/${day}`
}

const formatTime = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

// Watchers
watch([() => pagination.currentPage, () => pagination.perPage], loadCases)
watch([search, selectedChannel, selectedAssignee], () => {
  pagination.currentPage = 1
  loadCases()
})

// Lifecycle
onMounted(async () => {
  await Promise.all([loadCases(), loadUsers()])
})
</script>