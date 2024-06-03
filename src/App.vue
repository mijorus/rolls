<template>
	<NcAppContent>
		<div id="rolls">
			<div class="container">
				<Breadcrumbs />
				<router-view />
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import Breadcrumbs from './components/Breadcrumbs.vue'

export default {
	name: 'App',
	components: {
		NcAppContent,
		Breadcrumbs,
	},
	mounted() {
		console.log('activated', this.$route)
	},
}
</script>

<style scoped lang="scss">
#rolls {
	display: flex;
	justify-content: center;
	margin: 16px;
}
</style>
