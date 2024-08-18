<template>
	<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
		<NcPopover>
			<template #trigger>
				<NcButton :disabled="!ready">
					<template #icon>
						<Webcam :size="20" />
					</template>
					{{ activeCam ? addEllipsis(activeCam.label, 18) : t("rolls", "Disabled") }}
				</NcButton>
			</template>
			<template>
				<ul v-if="webcamDevices.length" class="tw-flex tw-flex-col tw-items-start tw-p-1">
					<li v-for="dev in webcamDevices" :key="dev.deviceId">
						<NcButton type="tertiary" @click="$emit('webcamChange', dev)">
							<template #icon>
								<CheckBold
									:size="20"
									v-if="dev.deviceId === activeWebcamId"
									class="tw-text-green-500"
								/>
								<Webcam :size="20" v-else />
							</template>
							{{ dev.label }}
						</NcButton>
					</li>
				</ul>
				<NcButton type="tertiary-no-background" v-else>
					{{ t("rolls", "No microphones found") }}
				</NcButton>
			</template>
		</NcPopover>
		<NcPopover>
			<template #trigger>
				<NcButton :disabled="!ready" aria-label="microphone">
					<template #icon>
						<Microphone :size="20" v-if="activeMicId" />
						<MicrophoneOff :size="20" v-else />
					</template>
					<span>
						{{ activeMic ? addEllipsis(activeMic.label, 18) : t('rolls', 'Disabled')}}
					</span>
				</NcButton>
			</template>
			<template>
				<ul v-if="micDevices.length" class="tw-flex tw-flex-col tw-items-start tw-p-1">
					<li>
						<NcButton type="tertiary" @click="$emit('micChange', { kind: 'audioinput', deviceId: null })">
							<template #icon>
								<CheckBold :size="20" v-if="!activeMicId" class="tw-text-green-500" />
								<MicrophoneOff :size="20" v-else />
							</template>
							{{ t("rolls", "Disabled") }}
						</NcButton>
					</li>
					<li v-for="dev in micDevices" :key="dev.deviceId">
						<NcButton type="tertiary" @click="$emit('micChange', dev)">
							<template #icon>
								<CheckBold :size="20" v-if="dev.deviceId === activeMicId" class="tw-text-green-500" />
								<Microphone :size="20" v-else />
							</template>
							{{ dev.label }}
						</NcButton>
					</li>
				</ul>
				<NcButton type="tertiary-no-background" v-else>
					{{ t("rolls", "No microphones found") }}
				</NcButton>
			</template>
		</NcPopover>
	</div>
</template>

<script>
import {
	NcButton,
	NcActionButton,
	NcActions,
	NcLoadingIcon,
	NcPopover,
} from "@nextcloud/vue";
import Webcam from "vue-material-design-icons/Webcam.vue";
import Microphone from "vue-material-design-icons/Microphone.vue";
import MicrophoneOff from "vue-material-design-icons/MicrophoneOff.vue";
import CheckBold from "vue-material-design-icons/CheckBold.vue";

export default {
	name: 'RecordSwitchSourceBtns',
	components: {
		NcButton,
		NcPopover,
		NcActionButton,
		NcActions,
		Webcam,
		Microphone,
		CheckBold,
		MicrophoneOff
	},
	props: {
		ready: {
			type: Boolean
		},
		activeWebcamId: {
			type: String
		},
		activeMicId: {
			type: String
		}
	},
	async mounted() {
		await this.getDevices();
		if (this.webcamDevices.length) {
			this.$emit('webcamChange', this.webcamDevices[0]);
		}

		if (this.micDevices.length) {
			this.$emit('micChange', this.micDevices[0]);
		}
	},
	data() {
		return {
			/** @type { MediaDeviceInfo[]} */
			webcamDevices: [],
			/** @type { MediaDeviceInfo[] } */
			micDevices: []
		};
	},
	methods: {
		async getDevices() {
			const devs = await navigator.mediaDevices.enumerateDevices();
			this.webcamDevices = devs.filter(el => el.kind === 'videoinput');
			this.micDevices = devs.filter(el => el.kind === 'audioinput');
		},
	},
	computed: {
		activeMic() {
			return this.micDevices.find(dev => dev.deviceId === this.activeMicId);
		},
		activeCam() {
			return this.webcamDevices.find(dev => dev.deviceId === this.activeWebcamId);
		}
	}
}
</script>

<style>
</style>