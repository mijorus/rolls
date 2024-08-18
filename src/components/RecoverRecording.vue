<template>
	<div>
		<NcNoteCard v-if="recoveryAvailable" type="info" >
			{{ t("rolls", "An unsaved video was found and can be recovered") }}
		</NcNoteCard>
	</div>
</template>

<script>
import { videoDbName, videoDbSchema, videoDbTable } from '../lib/dexiedb';
import { NcNoteCard } from "@nextcloud/vue";
import Dexie from 'dexie'

export default {
	name: 'RecoverRecording',
	components: {
		NcNoteCard
	},
	data() {
		return {
			appDB: null,
			recoveryAvailable: false
		};
	},
	async mounted() {
		this.appDB = new Dexie(videoDbName);
		this.appDB.version(1).stores(videoDbSchema);

		const tableSize = await this.appDB.table(videoDbTable).count();
		this.recoveryAvailable = tableSize > 0;
	}
}
</script>