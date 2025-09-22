/**
 * 格式化日期和時間的工具函數
 * 使用一致的格式化方法避免 SSR/CSR hydration 不匹配問題
 */

/**
 * 格式化日期為 YYYY/MM/DD 格式
 * @param {string|Date} date - 日期字符串或Date對象
 * @returns {string} 格式化的日期字符串，如果日期無效則返回 '-'
 */
export function formatDate(date) {
  if (!date) return '-'

  try {
    const d = new Date(date)
    if (isNaN(d.getTime())) return '-'

    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}/${month}/${day}`
  } catch (error) {
    return '-'
  }
}

/**
 * 格式化時間為 HH:MM 格式
 * @param {string|Date} date - 日期字符串或Date對象
 * @returns {string} 格式化的時間字符串，如果日期無效則返回空字符串
 */
export function formatTime(date) {
  if (!date) return ''

  try {
    const d = new Date(date)
    if (isNaN(d.getTime())) return ''

    const hours = String(d.getHours()).padStart(2, '0')
    const minutes = String(d.getMinutes()).padStart(2, '0')
    return `${hours}:${minutes}`
  } catch (error) {
    return ''
  }
}

/**
 * 格式化日期時間為 YYYY/MM/DD HH:MM 格式
 * @param {string|Date} date - 日期字符串或Date對象
 * @returns {string} 格式化的日期時間字符串
 */
export function formatDateTime(date) {
  const dateStr = formatDate(date)
  const timeStr = formatTime(date)

  if (dateStr === '-') return '-'
  if (!timeStr) return dateStr

  return `${dateStr} ${timeStr}`
}

/**
 * 格式化相對時間（多久以前）
 * @param {string|Date} date - 日期字符串或Date對象
 * @returns {string} 相對時間字符串
 */
export function formatRelativeTime(date) {
  if (!date) return '-'

  try {
    const d = new Date(date)
    if (isNaN(d.getTime())) return '-'

    const now = new Date()
    const diffMs = now.getTime() - d.getTime()
    const diffMinutes = Math.floor(diffMs / (1000 * 60))
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

    if (diffMinutes < 1) return '剛剛'
    if (diffMinutes < 60) return `${diffMinutes}分鐘前`
    if (diffHours < 24) return `${diffHours}小時前`
    if (diffDays < 7) return `${diffDays}天前`

    return formatDate(date)
  } catch (error) {
    return '-'
  }
}

/**
 * 格式化金額
 * @param {number} amount - 金額
 * @returns {string} 格式化的金額字符串
 */
export function formatCurrency(amount) {
  if (!amount && amount !== 0) return '-'

  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0
  }).format(amount)
}