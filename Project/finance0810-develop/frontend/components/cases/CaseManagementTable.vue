<template>
  <DataTable
    :title="title"
    :columns="tableColumns"
    :data="cases"
    :loading="loading"
    :error="loadError"
    :search-query="search"
    search-placeholder="搜尋客戶姓名/手機/Email..."
    :current-page="pagination.currentPage"
    :items-per-page="pagination.perPage"
    loading-text="載入中..."
    :empty-text="emptyText"
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
      <select v-if="showAssigneeFilter" v-model="selectedAssignee" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">全部業務</option>
        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
      </select>
    </template>

    <!-- Custom Table Cells -->
    <template #cell-customer_name="{ item }">
      <div class="font-medium text-gray-900">{{ item.customer_name || '-' }}</div>
    </template>

    <template #cell-customer_phone="{ item }">
      {{ item.customer_phone || '-' }}
    </template>

    <template #cell-customer_email="{ item }">
      {{ item.customer_email || '-' }}
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

    <template #cell-status="{ item }">
      <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
        {{ getStatusLabel(item.status) }}
      </span>
    </template>

    <template #cell-demand_amount="{ item }">
      {{ formatCurrency(item.demand_amount) }}
    </template>

    <template #cell-loan_amount="{ item }">
      {{ formatCurrency(item.loan_amount) }}
    </template>

    <template #cell-created_at="{ item }">
      <div v-if="item.created_at">
        <div>{{ formatDate(item.created_at) }}</div>
        <div class="text-sm text-gray-500">{{ formatTime(item.created_at) }}</div>
      </div>
      <span v-else>-</span>
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
          @click="editCase(item)"
          class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-all duration-200"
          title="編輯案件"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button
          v-if="status === 'unassigned'"
          @click="assignCase(item)"
          class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-all duration-200"
          title="指派案件"
        >
          <UserIcon class="w-4 h-4" />
        </button>
        <button
          v-else
          @click="changeStatus(item)"
          class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-all duration-200"
          title="變更狀態"
        >
          <ArrowPathIcon class="w-4 h-4" />
        </button>
        <button
          @click="deleteCase(item)"
          class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all duration-200"
          title="刪除案件"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import {
  PencilIcon,
  TrashIcon,
  UserIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import DataTable from '~/components/DataTable.vue'
import { formatDate, formatTime, formatCurrency } from '~/utils/dateFormatter'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  status: {
    type: String,
    required: true
  },
  emptyText: {
    type: String,
    default: '沒有案件資料'
  },
  showAssigneeFilter: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['edit-case', 'assign-case', 'change-status', 'delete-case'])

// Composables
const { pagination, updatePagination } = usePagination(10)
const { success, error: showError, confirm } = useNotification()
const { getUsers } = useUsers()
const { list: listCases } = useCases()

// State
const cases = ref([])
const users = ref([])
const loading = ref(false)
const search = ref('')
const selectedChannel = ref('')
const selectedAssignee = ref('')
const loadError = ref(null)

// Table columns based on status
const tableColumns = computed(() => {
  const baseColumns = [
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
      width: '120px'
    }
  ]

  // Add columns based on status group
  if (['valid_customer', 'invalid_customer', 'customer_service', 'blacklist', 'tracking_management'].includes(props.status)) {
    baseColumns.push({
      key: 'assigned_user',
      title: '指派業務',
      sortable: false,
      width: '120px'
    })
  }

  baseColumns.push(
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
    }
  )

  // Add amount columns based on status
  if (['approved_disbursed', 'approved_undisbursed', 'conditional_approval'].includes(props.status)) {
    baseColumns.push({
      key: 'loan_amount',
      title: '核准金額',
      sortable: true,
      width: '120px'
    })
  } else {
    baseColumns.push({
      key: 'demand_amount',
      title: '需求金額',
      sortable: true,
      width: '120px'
    })
  }

  // Add time columns
  if (props.status === 'unassigned') {
    baseColumns.push({
      key: 'created_at',
      title: '建立時間',
      sortable: true,
      width: '140px'
    })
  } else {
    baseColumns.push({
      key: 'status_updated_at',
      title: '狀態更新時間',
      sortable: true,
      width: '140px'
    })
  }

  baseColumns.push({
    key: 'actions',
    title: '操作',
    sortable: false,
    width: '120px'
  })

  return baseColumns
})

// Methods
const loadCases = async () => {
  loading.value = true
  loadError.value = null

  try {
    const apiParams = {
      page: pagination.currentPage,
      per_page: pagination.perPage,
      status: props.status
    }

    // 加入搜尋條件
    if (search.value.trim()) {
      apiParams.search = search.value.trim()
    }

    // 加入來源管道篩選
    if (selectedChannel.value) {
      apiParams.channel = selectedChannel.value
    }

    // 加入指派業務篩選
    if (selectedAssignee.value) {
      apiParams.assigned_to = selectedAssignee.value
    }

    const { success: ok, items, meta, error } = await listCases(apiParams)

    if (ok) {
      cases.value = items || []
      updatePagination({
        current_page: meta.currentPage,
        per_page: meta.perPage,
        total: meta.total,
        last_page: meta.lastPage
      })
    } else {
      loadError.value = error || '載入案件數據時發生錯誤'
      cases.value = []
    }
  } catch (err) {
    console.error('載入案件時發生錯誤:', err)
    loadError.value = err.message || '載入案件數據時發生錯誤'
    cases.value = []
  } finally {
    loading.value = false
  }
}

const loadUsers = async () => {
  if (!props.showAssigneeFilter) return

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

// Action methods
const editCase = (case_) => {
  emit('edit-case', case_)
}

const assignCase = async (case_) => {
  emit('assign-case', case_)
}

const changeStatus = async (case_) => {
  emit('change-status', case_)
}

const deleteCase = async (case_) => {
  const confirmed = await confirm(`確定刪除案件：${case_.customer_name || case_.id} 嗎？`)
  if (!confirmed) return

  emit('delete-case', case_)
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

const getStatusClass = (status) => {
  const classes = {
    unassigned: 'bg-gray-100 text-gray-800',
    valid_customer: 'bg-green-100 text-green-800',
    invalid_customer: 'bg-red-100 text-red-800',
    customer_service: 'bg-blue-100 text-blue-800',
    blacklist: 'bg-black text-white',
    approved_disbursed: 'bg-emerald-100 text-emerald-800',
    approved_undisbursed: 'bg-yellow-100 text-yellow-800',
    conditional_approval: 'bg-orange-100 text-orange-800',
    rejected: 'bg-red-100 text-red-800',
    tracking_management: 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    unassigned: '未指派',
    valid_customer: '有效客',
    invalid_customer: '無效客',
    customer_service: '客服',
    blacklist: '黑名單',
    approved_disbursed: '核准撥款',
    approved_undisbursed: '核准未撥',
    conditional_approval: '附條件',
    rejected: '婉拒',
    tracking_management: '追蹤管理'
  }
  return labels[status] || status || '-'
}


// Watchers
watch([() => pagination.currentPage, () => pagination.perPage], loadCases)
watch([search, selectedChannel, selectedAssignee], () => {
  pagination.currentPage = 1
  loadCases()
})

// Expose methods to parent
defineExpose({
  loadCases,
  refreshData: loadCases
})

// Lifecycle
onMounted(async () => {
  await Promise.all([loadCases(), loadUsers()])
})
</script>