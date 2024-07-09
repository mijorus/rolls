<template>
	<NcActions :force-name="true" :inline="3">
		<NcActionButton
			:disabled="!ready"
			@click="streamMonitor()"
			aria-label="Show screen"
			:modelValue="activeStreamName === 'screen'"
		>
			<template #icon>
				<Monitor :size="20" />
			</template>
		</NcActionButton>
		<NcActionButton
			:disabled="!ready"
			@click="streamWebcam()"
			aria-label="Show webcam"
			:modelValue="activeStreamName === 'webcam-stream'"
		>
			<template #icon>
				<Webcam :size="20" />
			</template>
		</NcActionButton>
		<NcActionButton
			:disabled="!ready"
			@click="streamMonitorWithWebcam()"
			aria-label="Show screen and webcam"
			:modelValue="activeStreamName === 'webcam-screen'"
		>
			<template #icon>
				<PictureInPictureBottomRight :size="20" />
			</template>
		</NcActionButton>
	</NcActions>
</template>

<script>
import {
	NcButton,
	NcActionButton,
	NcActions,
	NcLoadingIcon,
} from "@nextcloud/vue";
import Video from "vue-material-design-icons/Video.vue";
import Monitor from "vue-material-design-icons/Monitor.vue";
import Webcam from "vue-material-design-icons/Webcam.vue";
import PictureInPictureBottomRight from "vue-material-design-icons/PictureInPictureBottomRight.vue";

export default {
	name: 'RecordActions',
	components: {
		NcButton,
		NcActionButton,
		NcActions,
		PictureInPictureBottomRight,
		Video,
		Monitor,
		Webcam,
		NcLoadingIcon,
	},
	props: {
		activeStreamName: {
			type: String,
		},
		streamMonitor: {
			type: Function
		},
		streamWebcam: {
			type: Function
		},
		streamMonitorWithWebcam: {
			type: Function
		},
		ready: {
			type: Boolean,
		}
	}
}
</script>
