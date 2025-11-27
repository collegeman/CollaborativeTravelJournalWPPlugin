import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue'
import CreateTripPage from '../views/CreateTripPage.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/tabs/timeline'
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/timeline'
      },
      {
        path: 'timeline',
        component: () => import('../views/TimelinePage.vue')
      },
      {
        path: 'media',
        component: () => import('../views/MediaPage.vue')
      },
      {
        path: 'map',
        component: () => import('../views/MapPage.vue')
      },
      {
        path: 'music',
        component: () => import('../views/MusicPage.vue')
      },
      {
        path: 'me',
        component: () => import('../views/MePage.vue')
      }
    ]
  },
  {
    path: '/trip/create',
    name: 'CreateTrip',
    component: CreateTripPage
  },
  {
    path: '/trips',
    name: 'Trips',
    component: () => import('../views/TripsPage.vue')
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
