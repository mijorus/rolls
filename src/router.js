
// Each route should map to a component. The "component" can
// either be an actual component constructor created via
// `Vue.extend()`, or just a component options object.

import VueRouter from 'vue-router'
import Record from './pages/Record.vue'
import Index from './pages/Index.vue'
import Watch from './pages/Watch.vue'

const BASE_URL = '/index.php/apps/rolls'

// We'll talk about nested routes later.
const routes = [
	{ path: '/watch', component: Watch },
	{ path: '/watch/:uuid', component: Watch },
	{ path: '/record', component: Record },
	{ path: '/', component: Index },
	{
		// will match everything
		path: '*',
		component: { template: '<div>Not found</div>' },
	},
]

// You can pass in additional options here, but let's
// keep it simple for now.
export default new VueRouter({
	mode: 'history',
	base: BASE_URL,
	routes, // short for `routes: routes`
})
