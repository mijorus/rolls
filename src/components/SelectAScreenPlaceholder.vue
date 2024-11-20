<template>
	<NcEmptyContent
		:name="t('rolls', 'Select a screen to get started')"
		:description="
			t('rolls', 'If you decide to share a window only, the webcam Picture in Picture will not be available')
		"
	>
		<template #icon>
			<MonitorShare />
		</template>
	</NcEmptyContent>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import MonitorShare from "vue-material-design-icons/MonitorShare.vue";


export default {
	name: 'SelectAScreenPlaceholder',
	components: {
		MonitorShare,
		NcEmptyContent
	}
}
</script>
