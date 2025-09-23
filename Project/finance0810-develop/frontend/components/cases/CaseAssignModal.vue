<template>
  <UModal v-model="isOpenProxy" :ui="{ width: 'w-full max-w-md sm:max-w-lg' }">
    <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-100' }">
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            指派案件
          </h3>
          <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark-20-solid" class="-my-1" @click="close" />
        </div>
      </template>

      <div class="space-y-4">
        <!-- 案件資訊 -->
        <div v-if="caseData" class="p-4 bg-gray-50 rounded-lg">
          <h4 class="text-sm font-medium text-gray-900 mb-2">案件資訊</h4>
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div><span class="text-gray-500">客戶姓名:</span> {{ caseData.customer_name }}</div>
            <div><span class="text-gray-500">手機號碼:</span> {{ caseData.customer_phone }}</div>
            <div><span class="text-gray-500">需求金額:</span> {{ formatCurrency(caseData.demand_amount) }}</div>
            <div><span class="text-gray-500">諮詢項目:</span> {{ caseData.consultation_item || '-' }}</div>
          </div>
        </div>

        <!-- 指派業務選擇 -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            指派給業務 <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.assignedTo"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          >
            <option value="">請選擇業務</option>
            <option value="1">王小明 - 資深業務</option>
            <option value="2">李美華 - 貸款專員</option>
            <option value="3">張志偉 - 房貸專家</option>
          </select>
        </div>

        <!-- 備註 -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            指派備註
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="選填：指派原因或特別說明..."
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <UButton color="gray" variant="outline" @click="close">
            取消
          </UButton>
          <UButton color="primary" @click="save" :loading="saving" :disabled="!form.assignedTo">
            確認指派
          </UButton>
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup>
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  case: {
    type: Object,
    default: null
  }
})

// Rename case to avoid reserved keyword conflict
const caseData = computed(() => props.case)

const emit = defineEmits(['close', 'save'])

// Form data
const form = ref({
  assignedTo: '',
  notes: ''
})

const saving = ref(false)

// Two-way binding for modal
const isOpenProxy = computed({
  get: () => props.isOpen,
  set: (value) => {
    if (!value) emit('close')
  }
})

// Methods
const close = () => {
  resetForm()
  emit('close')
}

const save = async () => {
  if (!form.value.assignedTo) {
    return
  }

  saving.value = true
  try {
    await emit('save', {
      caseId: caseData.value?.id,
      assignedTo: form.value.assignedTo,
      notes: form.value.notes
    })
    resetForm()
  } finally {
    saving.value = false
  }
}

const resetForm = () => {
  form.value = {
    assignedTo: '',
    notes: ''
  }
}

const formatCurrency = (amount) => {
  if (!amount) return '-'
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0
  }).format(amount)
}

// Watch for modal open to reset form
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>