<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">無效客</h1>
        <p class="text-gray-600 mt-2">確認為無效客戶的案件</p>
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
    <CaseManagementTable
      ref="caseTable"
      title="無效客案件列表"
      :status="'invalid_customer'"
      empty-text="沒有無效客案件"
      @edit-case="openEditCaseModal"
      @assign-case="handleAssignCase"
      @change-status="handleChangeStatus"
      @delete-case="handleDeleteCase"
    />

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
import { PlusIcon } from '@heroicons/vue/24/outline'
import CaseManagementTable from '~/components/cases/CaseManagementTable.vue'
import CaseEditModal from '~/components/cases/CaseEditModal.vue'

definePageMeta({ middleware: 'auth' })
const authStore = useAuthStore()
const { success, error: showError } = useNotification()

// Case edit modal
const showCaseEditModal = ref(false)
const editingCase = ref(null)
const caseTable = ref(null)

// Case modal methods
const openCreateCaseModal = () => {
  editingCase.value = { status: 'invalid_customer' }
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
    success('案件保存成功')
    closeCaseEditModal()
    // 重新載入表格數據
    if (caseTable.value) {
      caseTable.value.refreshData()
    }
  } catch (err) {
    showError('案件保存失敗，請稍後再試')
    console.error('Save case error:', err)
  }
}

// Table event handlers
const handleAssignCase = (case_) => {
  showError('案件指派功能開發中')
}

const handleChangeStatus = (case_) => {
  showError('狀態變更功能開發中')
}

const handleDeleteCase = async (case_) => {
  try {
    success('案件刪除成功')
    // 重新載入表格數據
    if (caseTable.value) {
      caseTable.value.refreshData()
    }
  } catch (err) {
    showError('案件刪除失敗，請稍後再試')
    console.error('Delete case error:', err)
  }
}
</script>