<template>
	<NcEmptyContent :name="t('rolls', 'Recording...')">
		<template #icon>
			<RecordRec />
		</template>
		<template #description>
			<span>{{ t('rolls', 'Your screen is being recorded')}}</span>
		</template>
	</NcEmptyContent>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import RecordRec from "vue-material-design-icons/RecordRec.vue";


export default {
	name: 'ScreenBeingRecorded',
	components: {
		RecordRec,
		NcEmptyContent
	}
}
</script>
