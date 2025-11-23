import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import HomePage from '../views/HomePage.vue'
import CreateTripPage from '../views/CreateTripPage.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/home'
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage
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
