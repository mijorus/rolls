<template>
	<button
		:class="{active: activeTab === name}"
		@click="onClick"
	>
		<slot name="icon" />
		<div class="tw-capitalize">{{ title }}</div>
	</button>
</template>

<script>
export default {
	name: 'Tab',
	props: {
		name: {
			type: String,
			required: true,
		},
		activeTab: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
	},
	methods: {
		onClick() {
			this.$emit('click', { name: this.name });
		}
	}
}
</script>
