import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/HomeView.vue'),
    meta: { requiresAuth: true }
  },
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
  {
    path: '/admin/roles',
    name: 'AdminRoles',
    component: () => import('../views/admin/AdminRoleView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/alumni',
    name: 'AlumniList',
    component: () => import('../views/admin/AlumniListView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/alumni/import',
    name: 'AlumniImport',
    component: () => import('../views/admin/AlumniImportView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/alumni/:id',
    name: 'AlumniDetail',
    component: () => import('../views/admin/AlumniDetailView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/my-store',
    name: 'MyStore',
    component: () => import('../views/store/MyStoreView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/stores/:id',
    name: 'StoreProfile',
    component: () => import('../views/store/StoreProfileView.vue')
  },
  {
    path: '/admin/stores',
    name: 'AdminStores',
    component: () => import('../views/admin/AdminStoreListView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/categories',
    name: 'AdminCategories',
    component: () => import('../views/admin/AdminCategoryView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/my-store/products',
    name: 'SellerProducts',
    component: () => import('../views/store/product/ProductListView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/my-store/products/create',
    name: 'SellerProductCreate',
    component: () => import('../views/store/product/ProductFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/my-store/products/:id/edit',
    name: 'SellerProductEdit',
    component: () => import('../views/store/product/ProductFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/products/:slug',
    name: 'ProductDetail',
    component: () => import('../views/product/ProductDetailView.vue')
  },
  {
    path: '/my-store/services',
    name: 'SellerServices',
    component: () => import('../views/store/service/ServiceListView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/my-store/services/create',
    name: 'SellerServiceCreate',
    component: () => import('../views/store/service/ServiceFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/my-store/services/:id/edit',
    name: 'SellerServiceEdit',
    component: () => import('../views/store/service/ServiceFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/services/:slug',
    name: 'ServiceDetail',
    component: () => import('../views/service/ServiceDetailView.vue')
  },
  {
    path: '/email/verify/:id/:hash',
    name: 'EmailVerify',
    component: () => import('../views/auth/EmailVerifyView.vue')
  },
  {
    path: '/catalog',
    name: 'Catalog',
    component: () => import('../views/CatalogView.vue')
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: () => import('../views/FavoritesView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/cart',
    name: 'Cart',
    component: () => import('../views/CartView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/checkout',
    name: 'Checkout',
    component: () => import('../views/CheckoutView.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
  const isAdmin = permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
  
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      next({ name: 'Login' })
    } else if (to.matched.some(record => record.meta.requiresAdmin) && !isAdmin) {
      next({ name: 'Home' })
    } else {
      next()
    }
  } else if (to.matched.some(record => record.meta.guestOnly)) {
    if (token) {
      next({ name: 'Home' })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
