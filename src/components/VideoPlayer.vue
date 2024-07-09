<template>
	<div v-if="src">
		<VuePlyr ref="plyr" :options="options">
			<video ref="video" :autoplay="true" :playsinline="true" :src="src" preload="metadata">
				{{ t('rolls', 'Your browser does not support videos.') }}
			</video>
		</VuePlyr>
	</div>
</template>

<script>
import VuePlyr from '@skjnldsv/vue-plyr';

export default {
	name: 'VideoPlayer',
	props: ['src', 'markers'],
	components: {
		VuePlyr,
	},
	mounted() {
		// console.log(this.$props.src);
	},
	computed: {
		options() {
			return {
				autoplay: true,
				controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
				loadSprite: false,
				fullscreen: {
					iosNative: true,
				},
				markers: {
					enabled: true,
					points: this.$props.markers
				}
			};
		},
	}
};
</script>

<style scoped lang="scss">
video {
	/* over arrows in tiny screens */
	align-self: center;
	max-width: 100%;
	max-height: 100% !important;
	background-color: black;
	justify-self: center;
}

:deep() {
	.plyr:-webkit-full-screen video {
		width: 100% !important;
		height: 100% !important;
	}
	.plyr:fullscreen video {
		width: 100% !important;
		height: 100% !important;
	}
	.plyr__progress__container {
		flex: 1 1;
	}

	.plyr {
		@import '../mixins/Plyr';

		border-radius: 10px;
		// Override server font style
		button {
			color: white;

			&:hover,
			&:focus {
				color: var(--color-primary-element-text);
				background-color: var(--color-primary-element);
			}
		}

		.plyr__tooltip {
			white-space: normal !important;
			max-width: 150px !important;
		}
	}
}
</style>