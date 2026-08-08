// src/api/auth.js
import api from './axios'
export const authApi = {
  login:  (credentials) => api.post('/auth/login', credentials),
  logout: ()            => api.post('/auth/logout'),
  me:     ()            => api.get('/auth/me'),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/tenants.js
export const tenantsApi = {
  list:         (params) => api.get('/tenants', { params }),
  get:          (id)     => api.get(`/tenants/${id}`),
  create:       (data)   => api.post('/tenants', data),
  update:       (id, data) => api.put(`/tenants/${id}`, data),
  toggleActive: (id)     => api.patch(`/tenants/${id}/toggle-active`),
  destroy:      (id)     => api.delete(`/tenants/${id}`),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/users.js
export const usersApi = {
  list:         (params) => api.get('/users', { params }),
  get:          (id)     => api.get(`/users/${id}`),
  create:       (data)   => api.post('/users', data),
  update:       (id, data) => api.put(`/users/${id}`, data),
  toggleActive: (id)     => api.patch(`/users/${id}/toggle-active`),
  destroy:      (id)     => api.delete(`/users/${id}`),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/products.js
export const productsApi = {
  list:       (params) => api.get('/products', { params }),
  get:        (id)     => api.get(`/products/${id}`),
  create:     (data)   => api.post('/products', data),
  // Alta masiva: devuelve { created, failed, results:[{index, ok, error}] }
  bulkCreate: (items)  => api.post('/products/bulk', { items }),
  update:     (id, data) => api.put(`/products/${id}`, data),
  destroy:    (id)     => api.delete(`/products/${id}`),
  categories: ()       => api.get('/products/categories'),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/movements.js
export const movementsApi = {
  list:   (params) => api.get('/inventory-movements', { params }),
  get:    (id)     => api.get(`/inventory-movements/${id}`),
  create: (data)   => api.post('/inventory-movements', data),
  export: (params) => api.get('/inventory-movements/export', { params }),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/dashboard.js
export const dashboardApi = {
  get: (params) => api.get('/dashboard', { params }),
}

// ──────────────────────────────────────────────────────────────────────────────
// src/api/pos.js
export const posApi = {
  scanProduct:  (code)   => api.get(`/pos/scan/${encodeURIComponent(code)}`),
  createSale:   (data)   => api.post('/pos/sales', data),
  getSale:      (id)     => api.get(`/pos/sales/${id}`),
  salesList:    (params) => api.get('/pos/sales', { params }),
  cancelSale:   (id)     => api.post(`/pos/sales/${id}/cancel`),
  exportSales:  (params) => api.get('/pos/sales/export', { params }),
}
