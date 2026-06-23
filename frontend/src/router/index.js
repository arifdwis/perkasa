import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  // Guest only routes
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/LoginView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/auth/RegisterView.vue'),
    meta: { guestOnly: true }
  },

  // Parent: Buyer App Layout Wrapper
  {
    path: '/buyer',
    component: () => import('../layouts/BuyerLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'home',
        name: 'BuyerHome',
        component: () => import('../views/product/BuyerHomeView.vue')
      },
      {
        path: 'catalog',
        name: 'Catalog',
        component: () => import('../views/CatalogView.vue')
      },
      {
        path: 'favorites',
        name: 'Favorites',
        component: () => import('../views/FavoritesView.vue')
      },
      {
        path: 'cart',
        name: 'Cart',
        component: () => import('../views/CartView.vue')
      },
      {
        path: 'checkout',
        name: 'Checkout',
        component: () => import('../views/CheckoutView.vue')
      },
      {
        path: 'orders',
        name: 'BuyerOrders',
        component: () => import('../views/order/BuyerOrdersView.vue')
      },
      {
        path: 'orders/:id',
        name: 'OrderDetail',
        component: () => import('../views/order/OrderDetailView.vue')
      },
      {
        path: 'stores/:id',
        name: 'StoreProfile',
        component: () => import('../views/store/StoreProfileView.vue')
      },
      {
        path: 'products/:slug',
        name: 'ProductDetail',
        component: () => import('../views/product/ProductDetailView.vue')
      },
      {
        path: 'notifications',
        name: 'Notifications',
        component: () => import('../views/NotificationListView.vue')
      },
      {
        path: 'profile',
        name: 'AlumniProfile',
        component: () => import('../views/alumni/AlumniProfileView.vue')
      }
    ]
  },

  // Parent: Seller App Layout Wrapper
  {
    path: '/seller',
    component: () => import('../layouts/SellerLayout.vue'),
    meta: { requiresAuth: true, requiresSellerMode: true },
    children: [
      {
        path: 'home',
        name: 'SellerHome',
        component: () => import('../views/store/SellerHomeView.vue')
      },
      {
        path: 'store',
        name: 'SellerStore',
        component: () => import('../views/store/MyStoreView.vue')
      },
      {
        path: 'products',
        name: 'SellerProducts',
        component: () => import('../views/store/product/ProductListView.vue')
      },
      {
        path: 'products/create',
        name: 'SellerProductCreate',
        component: () => import('../views/store/product/ProductFormView.vue')
      },
      {
        path: 'products/:id/edit',
        name: 'SellerProductEdit',
        component: () => import('../views/store/product/ProductFormView.vue')
      },
      {
        path: 'orders',
        name: 'SellerOrders',
        component: () => import('../views/store/order/SellerOrdersView.vue')
      },
      {
        path: 'finance',
        name: 'SellerFinance',
        component: () => import('../views/store/SellerFinanceView.vue')
      },
      {
        path: 'orders/:id',
        name: 'SellerOrderDetail',
        component: () => import('../views/order/OrderDetailView.vue')
      }
    ]
  },

  // Parent: Admin App Layout Wrapper
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('../views/admin/AdminDashboardView.vue')
      },
      {
        path: 'roles',
        name: 'AdminRoles',
        component: () => import('../views/admin/AdminRoleView.vue')
      },
      {
        path: 'alumni',
        name: 'AlumniList',
        component: () => import('../views/admin/AlumniListView.vue')
      },
      {
        path: 'alumni/import',
        name: 'AlumniImport',
        component: () => import('../views/admin/AlumniImportView.vue')
      },
      {
        path: 'alumni/:id',
        name: 'AlumniDetail',
        component: () => import('../views/admin/AlumniDetailView.vue')
      },
      {
        path: 'stores',
        name: 'AdminStores',
        component: () => import('../views/admin/AdminStoreListView.vue')
      },
      {
        path: 'categories',
        name: 'AdminCategories',
        component: () => import('../views/admin/AdminCategoryView.vue')
      },
      {
        path: 'reports',
        name: 'AdminReports',
        component: () => import('../views/admin/AdminReportView.vue')
      },
      {
        path: 'finance',
        name: 'AdminFinance',
        component: () => import('../views/admin/AdminFinanceView.vue')
      }
    ]
  },

  // Handle Root Redirect and legacy redirects
  {
    path: '/',
    name: 'Home',
    redirect: to => {
      const token = localStorage.getItem('token')
      if (!token) return { name: 'Login' }

      // Resolve userMode redirect
      const userMode = localStorage.getItem('userMode')
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
      const isAdmin = permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
      const isSeller = user?.roles?.some(r => r.name === 'alumni_penjual') || false
      const store = user?.profile?.store || null
      const isStoreActive = store && (store.status === 'active' || store.status === 'suspended')

      if (userMode === 'admin' && isAdmin) {
        return { name: 'AdminDashboard' }
      } else if (userMode === 'seller' && isStoreActive) {
        return { name: 'SellerHome' }
      } else {
        return { name: 'BuyerHome' }
      }
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'CatchAll',
    redirect: () => {
      const token = localStorage.getItem('token')
      if (!token) return { name: 'Login' }

      const userMode = localStorage.getItem('userMode')
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
      const isAdmin = permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
      const isSeller = user?.roles?.some(r => r.name === 'alumni_penjual') || false
      const store = user?.profile?.store || null
      const isStoreActive = store && (store.status === 'active' || store.status === 'suspended')

      if (userMode === 'admin' && isAdmin) {
        return { name: 'AdminDashboard' }
      } else if (userMode === 'seller' && isStoreActive) {
        return { name: 'SellerHome' }
      } else {
        return { name: 'BuyerHome' }
      }
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach((to, from) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')

  const isAdmin = permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
  const isSeller = user?.roles?.some(r => r.name === 'alumni_penjual') || false
  const store = user?.profile?.store || null
  const isStoreActive = store && (store.status === 'active' || store.status === 'suspended')

  // 1. Guest Only Routes
  if (to.matched.some(record => record.meta.guestOnly)) {
    if (token) {
      const userMode = localStorage.getItem('userMode')
      if (userMode === 'admin' && isAdmin) {
        return { name: 'AdminDashboard' }
      } else if (userMode === 'seller' && isStoreActive) {
        return { name: 'SellerHome' }
      } else {
        return { name: 'BuyerHome' }
      }
    }
    return true
  }

  // 2. Authentication Check
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      return { name: 'Login' }
    }
  }

  // 3. Admin — block from accessing buyer/seller routes
  if (isAdmin && (to.path.startsWith('/buyer') || to.path.startsWith('/seller'))) {
    return { name: 'AdminDashboard' }
  }

  // 4. Admin Permission Check
  if (to.matched.some(record => record.meta.requiresAdmin) || to.path.startsWith('/admin')) {
    if (!isAdmin) {
      return { name: 'BuyerHome' }
    }
  }

  // 5. Seller Mode Check
  if (to.matched.some(record => record.meta.requiresSellerMode) || to.path.startsWith('/seller')) {
    if (to.name === 'SellerStore') {
      return true
    }

    if (!isSeller || !isStoreActive) {
      return { name: 'BuyerHome' }
    }
  }

  // 5. Handle Legacy Path Redirects (Avoid breaking links)
  if (to.path === '/') {
    const userMode = localStorage.getItem('userMode')
    if (userMode === 'admin' && isAdmin) {
      return { name: 'AdminDashboard' }
    } else if (userMode === 'seller' && isStoreActive) {
      return { name: 'SellerHome' }
    } else {
      return { name: 'BuyerHome' }
    }
  }

  if (to.path === '/my-store') return { name: 'SellerStore' }
  if (to.path === '/orders') return { name: 'BuyerOrders' }
  if (to.path === '/catalog') return { name: 'Catalog' }
  if (to.path === '/favorites') return { name: 'Favorites' }
  if (to.path === '/cart') return { name: 'Cart' }
  if (to.path === '/checkout') return { name: 'Checkout' }
  if (to.path === '/notifications') return { name: 'Notifications' }
  if (to.path === '/profile') return { name: 'AlumniProfile' }

  if (to.path.startsWith('/products/')) {
    return { name: 'ProductDetail', params: { slug: to.params.slug || to.path.split('/').pop() } }
  }

  if (to.path.startsWith('/stores/')) {
    return { name: 'StoreProfile', params: { id: to.params.id || to.path.split('/').pop() } }
  }

  return true
})

export default router
