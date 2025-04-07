import { createRouter, createWebHistory } from 'vue-router';
import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import ResetPassword from '../views/ResetPassword.vue';
import { useAuthStore } from '../stores/authStore';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Home,
      meta: { requiresAuth: true }
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { guest: true }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPassword,
      meta: { guest: true }
    },
    {
      path: '/user-dashboard',
      name: 'user-dashboard',
      component: () => import('../views/UserDashboard.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/segment/:id?',
      name: 'segment',
      component: () => import('../views/Segment.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/segments',
      name: 'segments',
      component: () => import('../views/Segments.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/batch',
      name: 'batch',
      component: () => import('../views/Batch.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/sms',
      name: 'sms',
      component: () => import('../views/SMS.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/sms-history',
      name: 'sms-history',
      component: () => import('../views/SMSHistory.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/sms-templates',
      name: 'sms-templates',
      component: () => import('../views/SMSTemplates.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/scheduled-sms',
      name: 'scheduled-sms',
      component: () => import('../views/ScheduledSMS.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/import',
      name: 'import',
      component: () => import('../views/Import.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/contacts',
      name: 'contacts',
      component: () => import('../views/Contacts.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/contact-groups',
      name: 'contact-groups',
      component: () => import('../views/ContactGroups.vue'),
      meta: { requiresAuth: true }
    },
    // Routes d'administration
    {
      path: '/admin-dashboard',
      name: 'admin-dashboard',
      component: () => import('../views/AdminDashboard.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/users',
      name: 'users',
      component: () => import('../views/Users.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/user/:id',
      name: 'user-details',
      component: () => import('../views/UserDetails.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/sender-names',
      name: 'sender-names',
      component: () => import('../views/SenderNames.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/sms-orders',
      name: 'sms-orders',
      component: () => import('../views/SMSOrders.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/orange-api-config',
      name: 'orange-api-config',
      component: () => import('../views/OrangeAPIConfig.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    // Route 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFound.vue')
    }
  ]
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin);
    const isGuestRoute = to.matched.some(record => record.meta.guest);

    let isAuthenticated = authStore.isAuthenticated;

    // If navigating away from login page immediately after successful login,
    // trust the store state set by the login action.
    if (from.name === 'login' && isAuthenticated) {
        // Already handled by the login component's redirect logic,
        // but we ensure the guard allows it without re-checking auth immediately.
        if (requiresAdmin && !authStore.isAdmin) {
             // This case should ideally not happen if login sets isAdmin correctly
            next({ name: 'home' });
        } else {
            next(); // Allow navigation
        }
        return; // Skip further checks for this specific transition
    }

    // For other navigations (refresh, direct access, etc.)
    // If route requires auth and store says not authenticated, check backend
    if (requiresAuth && !isAuthenticated) {
        isAuthenticated = await authStore.checkAuth();
    }

    // Final checks based on potentially updated isAuthenticated
    if (requiresAuth) {
        if (isAuthenticated) {
            // Authenticated: Check admin rights if needed
            if (requiresAdmin && !authStore.isAdmin) {
                next({ name: 'home' }); // Redirect non-admins
            } else {
                next(); // Allow access
            }
        } else {
             // Not Authenticated: Redirect to login
            next({ path: '/login', query: { redirect: to.fullPath } });
        }
    } else if (isGuestRoute) {
         // Guest Route: Redirect if already logged in
        if (isAuthenticated) {
            next({ name: authStore.isAdmin ? 'admin-dashboard' : 'home' });
        } else {
            next(); // Allow access if not logged in
        }
    } else {
        // Public Route: Allow access
        next();
    }
});

export default router;
