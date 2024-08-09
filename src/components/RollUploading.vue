<template>
	<NcEmptyContent :name="t('rolls', 'Uploading...')">
		<template #icon>
			<Upload />
		</template>
		<template #description>
			<span v-if="perc > 0">{{ perc }}%</span>
		</template>
	</NcEmptyContent>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import Upload from "vue-material-design-icons/Upload.vue";

export default {
	name: "RollUploading",
	components: {
		Upload,
		NcEmptyContent,
	},
	props: ["perc"],
};
</script>
