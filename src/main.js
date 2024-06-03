import Vue from 'vue'
import VueRouter from 'vue-router'
import App from './App.vue'
import router from './router.js'
import { linkTo } from '@nextcloud/router'

const isDevServer = process.env.WEBPACK_DEV_SERVER

// eslint-disable-next-line camelcase,no-undef
if (isDevServer || false) {
	// eslint-disable-next-line camelcase,no-undef
	__webpack_public_path__ = 'http://127.0.0.1:3000/apps/rolls/js/'
}

// eslint-disable-next-line camelcase,no-undef
__webpack_public_path__ = `${linkTo('rolls', 'js')}/`

Vue.use(VueRouter)
Vue.mixin({ methods: { t, n } })

const View = Vue.extend(App)
new View({ router }).$mount('#rolls')
