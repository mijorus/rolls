<template>
	<NcAppContent>
		<div id="rolls">
			<div class="container">
				<router-view />
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'

export default {
	name: 'App',
	components: {
		NcAppContent,
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
