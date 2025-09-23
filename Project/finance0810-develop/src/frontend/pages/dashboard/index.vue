<template>
  <div>
    <!-- Redirect to analytics -->
    <div class="flex items-center justify-center h-64">
      <div class="text-center">
        <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500 mx-auto"></div>
        <p class="mt-4 text-gray-600">正在跳轉到儀表板...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
// 自動跳轉到 analytics 頁面
await navigateTo('/dashboard/analytics', { replace: true })
</script>