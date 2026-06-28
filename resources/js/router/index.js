import { createRouter, createWebHistory } from 'vue-router'

const HomePage        = () => import('@/pages/HomePage.vue')
const CatalogPage     = () => import('@/pages/CatalogPage.vue')
const ProductPage     = () => import('@/pages/ProductPage.vue')
const CartPage        = () => import('@/pages/CartPage.vue')
const CheckoutPage    = () => import('@/pages/CheckoutPage.vue')
const LoginPage       = () => import('@/pages/LoginPage.vue')
const RegisterPage    = () => import('@/pages/RegisterPage.vue')
const AccountPage     = () => import('@/pages/AccountPage.vue')
const FavoritesPage   = () => import('@/pages/FavoritesPage.vue')
const VendorsPage     = () => import('@/pages/VendorsPage.vue')
const VendorPage      = () => import('@/pages/VendorPage.vue')
const CompanyPage     = () => import('@/pages/CompanyPage.vue')
const WebDevPage      = () => import('@/pages/WebDevPage.vue')
const NotFoundPage    = () => import('@/pages/NotFoundPage.vue')

const AdminLayout     = () => import('@/pages/admin/AdminLayout.vue')
const AdminDashboard  = () => import('@/pages/admin/DashboardPage.vue')
const AdminProducts   = () => import('@/pages/admin/ProductsPage.vue')
const AdminCategories = () => import('@/pages/admin/CategoriesPage.vue')
const AdminVendors    = () => import('@/pages/admin/VendorsPage.vue')
const AdminOrders     = () => import('@/pages/admin/OrdersPage.vue')
const AdminBanners    = () => import('@/pages/admin/BannersPage.vue')
const AdminPages      = () => import('@/pages/admin/PagesPage.vue')

const routes = [
    { path: '/',                name: 'home',      component: HomePage },
    { path: '/catalog',         name: 'catalog',   component: CatalogPage },
    { path: '/catalog/:slug',   name: 'category',  component: CatalogPage },
    { path: '/product/:slug',   name: 'product',   component: ProductPage },
    { path: '/cart',            name: 'cart',      component: CartPage },
    { path: '/checkout',        name: 'checkout',  component: CheckoutPage, meta: { requiresAuth: true } },
    { path: '/login',           name: 'login',     component: LoginPage,    meta: { guest: true } },
    { path: '/register',        name: 'register',  component: RegisterPage, meta: { guest: true } },
    { path: '/account',         name: 'account',   component: AccountPage,  meta: { requiresAuth: true } },
    { path: '/favorites',       name: 'favorites', component: FavoritesPage, meta: { requiresAuth: true } },
    { path: '/vendors',         name: 'vendors',       component: VendorsPage },
    { path: '/vendors/:slug',   name: 'vendor',        component: VendorPage },
    { path: '/company/veb-razrabotka', name: 'web-dev',      component: WebDevPage },
    { path: '/company/:slug',          name: 'company-page', component: CompanyPage },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '',           name: 'admin',            component: AdminDashboard },
            { path: 'products',   name: 'admin-products',   component: AdminProducts },
            { path: 'categories', name: 'admin-categories', component: AdminCategories },
            { path: 'vendors',    name: 'admin-vendors',    component: AdminVendors },
            { path: 'orders',     name: 'admin-orders',     component: AdminOrders },
            { path: 'banners',    name: 'admin-banners',    component: AdminBanners },
            { path: 'pages',      name: 'admin-pages',      component: AdminPages },
        ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        return savedPosition || { top: 0 }
    },
})

router.beforeEach((to, from, next) => {
    const token           = localStorage.getItem('auth_token')
    const role            = localStorage.getItem('auth_role')
    const isAuthenticated = !!token
    const isAdmin         = role === 'admin'

    if (to.meta.requiresAuth && !isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } })
    }
    if (to.meta.requiresAdmin && !isAdmin) {
        return next({ name: 'home' })
    }
    if (to.meta.guest && isAuthenticated) {
        return next({ name: 'account' })
    }
    next()
})

export default router
