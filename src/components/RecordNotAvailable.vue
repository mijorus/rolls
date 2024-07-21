<template>
	<NcEmptyContent :name="t('rolls', 'Rolls is not available on mobile devices')">
		<template #icon>
			<CellphoneOff />
		</template>
	</NcEmptyContent>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import CellphoneOff from "vue-material-design-icons/CellphoneOff.vue";


export default {
	name: 'RecordNotAvailable',
	components: {
		CellphoneOff,
		NcEmptyContent
	}
}
</script>
