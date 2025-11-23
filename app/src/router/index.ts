import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue'
import CreateTripPage from '../views/CreateTripPage.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/tabs/feed'
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/feed'
      },
      {
        path: 'feed',
        component: () => import('../views/FeedPage.vue')
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
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
