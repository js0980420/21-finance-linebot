export const useCases = () => {
  const { get, put, post, delete: del } = useApi()

  const list = async (params = {}) => {
    const { data, error } = await get('/cases', params)
    if (error) return { success: false, error }
    return {
      success: true,
      items: data.data || [],
      meta: {
        total: data.total || 0,
        perPage: data.per_page || params.per_page || 15,
        currentPage: data.current_page || params.page || 1,
        lastPage: data.last_page || 1
      }
    }
  }

  const getOne = async (id) => {
    const { data, error } = await get(`/cases/${id}`)
    if (error) return { success: false, error }
    return { success: true, case: data.case }
  }

  const createOne = async (payload) => {
    const { data, error } = await post('/cases', payload)
    if (error) return { success: false, error }
    return { success: true, case: data.case }
  }

  const updateOne = async (id, payload) => {
    const { data, error } = await put(`/cases/${id}`, payload)
    if (error) return { success: false, error }
    return { success: true, case: data.case }
  }

  const deleteOne = async (id) => {
    const { error } = await del(`/cases/${id}`)
    if (error) return { success: false, error }
    return { success: true }
  }

  const assignCase = async (id, payload) => {
    const { data, error } = await put(`/cases/${id}/assign`, payload)
    if (error) return { success: false, error }
    return { success: true, case: data.case }
  }

  // Create a new case for a specific customer
  const createForCustomer = async (customerId, payload) => {
    const { data, error } = await post(`/customers/${customerId}/cases`, payload)
    if (error) return { success: false, error }
    return { success: true, case: data.case }
  }

  // Helper: get latest case for a customer (by created_at desc)
  const getLatestForCustomer = async (customerId) => {
    const { success, items } = await list({ customer_id: customerId, per_page: 1 })
    return success && items.length > 0 ? items[0] : null
  }

  // Get status summary
  const getStatusSummary = async () => {
    const { data, error } = await get('/cases/status-summary')
    if (error) return { success: false, error }
    return { success: true, summary: data }
  }

  return {
    list,
    getOne,
    createOne,
    updateOne,
    deleteOne,
    assignCase,
    createForCustomer,
    getLatestForCustomer,
    getStatusSummary
  }
}
