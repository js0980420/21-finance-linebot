<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">網路進線</h1>
        <p class="text-gray-600 mt-2">未指派狀態的案件</p>
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

    <!-- Cases Management Table -->
    <CaseManagementTable
      ref="caseTableRef"
      title="未指派案件列表"
      status="unassigned"
      empty-text="沒有未指派的案件"
      :show-assignee-filter="false"
      @edit-case="handleEditCase"
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

    <!-- 案件指派彈窗 -->
    <CaseAssignModal
      :is-open="showAssignModal"
      :case="assigningCase"
      @close="closeAssignModal"
      @save="handleAssignSave"
    />
  </div>
</template>

<script setup>
import {
  PlusIcon
} from '@heroicons/vue/24/outline'
import CaseEditModal from '~/components/cases/CaseEditModal.vue'
import CaseAssignModal from '~/components/cases/CaseAssignModal.vue'
import CaseManagementTable from '~/components/cases/CaseManagementTable.vue'

definePageMeta({ middleware: 'auth' })
const authStore = useAuthStore()

// Composables
const { success, error: showError, confirm } = useNotification()

// Case edit modal
const showCaseEditModal = ref(false)
const editingCase = ref(null)

// Case assign modal
const showAssignModal = ref(false)
const assigningCase = ref(null)

// Table ref
const caseTableRef = ref(null)

// Event handlers
const handleEditCase = (case_) => {
  editingCase.value = case_
  showCaseEditModal.value = true
}

const handleAssignCase = (case_) => {
  assigningCase.value = case_
  showAssignModal.value = true
}

const handleChangeStatus = (case_) => {
  // TODO: 實現狀態變更功能
  showError('狀態變更功能開發中')
}

const handleDeleteCase = async (case_) => {
  try {
    success('案件刪除成功')
    caseTableRef.value?.loadCases()
  } catch (err) {
    showError('案件刪除失敗，請稍後再試')
    console.error('Delete case error:', err)
  }
}

// Modal methods
const openCreateCaseModal = () => {
  editingCase.value = null
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
    caseTableRef.value?.loadCases()
  } catch (err) {
    showError('案件保存失敗，請稍後再試')
    console.error('Save case error:', err)
  }
}

const closeAssignModal = () => {
  showAssignModal.value = false
  assigningCase.value = null
}

const handleAssignSave = async (assignData) => {
  try {
    success('案件指派成功')
    closeAssignModal()
    caseTableRef.value?.loadCases()
  } catch (err) {
    showError('案件指派失敗，請稍後再試')
    console.error('Assign case error:', err)
  }
}
</script>