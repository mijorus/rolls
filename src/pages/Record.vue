<template>
	<div>
		<video ref="screenVideo" width="300" height="500"></video>

		<div>
			<NcButton @click="startCapture">Start</NcButton>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue';

export default {
	name: 'Record',
	components: {
		NcButton
	},
	data() {
		return {
		}
	},
	methods: {
		async startCapture() {
			if (!this.$refs.screenVideo) {
				return
			}

			const displayMediaOptions = {
				video: {
					displaySurface: 'monitor',
				},
				preferCurrentTab: false,
				selfBrowserSurface: "exclude",
				systemAudio: "exclude",
				surfaceSwitching: "include",
				monitorTypeSurfaces: "include",
			};

			try {
				await navigator.mediaDevices.getUserMedia({video: true})

				this.$refs.screenVideo.srcObject =
					await navigator.mediaDevices.getDisplayMedia();

			} catch (err) {
				console.error(err);
			}
		}
	}
}
</script>

<style>
	video {
		background-color: black;
	}
</style>