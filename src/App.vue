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
import NcAppContent from "@nextcloud/vue/dist/Components/NcAppContent.js";

export default {
	name: "App",
	components: {
		NcAppContent,
	},
	mounted() {},
};
</script>

<style>
@import "./style/tailwind.css";
@import "./style/style.css";
</style>

<style scoped>
.container {
	max-width: 950px;
	width: 90vw;
	margin: auto;
	padding: 20px;
}
</style>
