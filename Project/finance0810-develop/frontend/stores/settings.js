export const useSettingsStore = defineStore('settings', () => {
  const showFootbar = ref(true)
  const sidebarMenuItems = ref([
    // 儀表板
    {
      name: '儀錶板',
      icon: 'ChartBarIcon',
      href: '/dashboard/analytics',
      permissions: ['dashboard', 'all_access']
    },

    // 聊天室
    {
      name: '聊天室',
      icon: 'ChatBubbleLeftRightIcon',
      href: '/chat',
      permissions: ['chat', 'all_access']
    },

    // 網路進線
    {
      name: '網路進線',
      icon: 'DocumentTextIcon',
      href: '/cases/unassigned',
      permissions: ['customer_management', 'all_access']
    },

    // 網路進線管理
    {
      name: '網路進線管理',
      icon: 'UserGroupIcon',
      children: [
        { name: '有效客', href: '/cases/valid-customer', permissions: ['customer_management', 'all_access'] },
        { name: '無效客', href: '/cases/invalid-customer', permissions: ['customer_management', 'all_access'] },
        { name: '客服', href: '/cases/customer-service', permissions: ['customer_management', 'all_access'] },
        { name: '黑名單', href: '/cases/blacklist', permissions: ['customer_management', 'all_access'] }
      ],
      permissions: ['customer_management', 'all_access']
    },

    // 送件管理
    {
      name: '送件管理',
      icon: 'DocumentCheckIcon',
      children: [
        { name: '核准撥款', href: '/cases/approved-disbursed', permissions: ['customer_management', 'all_access'] },
        { name: '核准未撥', href: '/cases/approved-undisbursed', permissions: ['customer_management', 'all_access'] },
        { name: '附條件', href: '/cases/conditional-approval', permissions: ['customer_management', 'all_access'] },
        { name: '婉拒', href: '/cases/rejected', permissions: ['customer_management', 'all_access'] }
      ],
      permissions: ['customer_management', 'all_access']
    },

    // 業務管理
    {
      name: '業務管理',
      icon: 'BriefcaseIcon',
      children: [
        { name: '追蹤管理', href: '/cases/tracking-management', permissions: ['customer_management', 'all_access'] },
        { name: '追蹤紀錄', href: '/business/tracking-records', permissions: ['customer_management', 'all_access'] }
      ],
      permissions: ['customer_management', 'all_access']
    },

    // 網站設定
    {
      name: '網站設定',
      icon: 'CogIcon',
      children: [
        { name: '網站名稱管理', href: '/settings/site-name', permissions: ['admin', 'all_access'] },
        { name: 'Line整合', href: '/settings/line-integration', permissions: ['admin', 'all_access'] },
        { name: '自定義欄位', href: '/settings/custom-fields', permissions: ['admin', 'all_access'] },
        { name: '系統除錯', href: '/settings/system-debug', permissions: ['admin', 'all_access'] }
      ],
      permissions: ['admin', 'all_access']
    },

    // 統計報表
    {
      name: '統計報表',
      icon: 'ChartPieIcon',
      href: '/reports/statistics',
      permissions: ['admin', 'all_access']
    }
  ])
  
  const toggleFootbar = () => {
    showFootbar.value = !showFootbar.value
  }
  
  const updateMenuItems = (newItems) => {
    sidebarMenuItems.value = newItems
  }
  
  return {
    showFootbar,
    sidebarMenuItems,
    toggleFootbar,
    updateMenuItems
  }
})