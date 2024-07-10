<template>
	<NcButton :type="activeTab === name ? 'primary' : 'tertiary'" @click="onClick">
		<template #icon>
			<slot name="icon" />
		</template>
		<div class="tw-capitalize">{{ title }}</div>
	</NcButton>
</template>

<script>
import { NcLoadingIcon, NcListItem, NcAvatar, NcActions, NcButton, NcActionButton } from "@nextcloud/vue";

export default {
	name: "Tab",
	components: {
		NcButton,
	},
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
			this.$emit("click", { name: this.name });
		},
	},
};
</script>
