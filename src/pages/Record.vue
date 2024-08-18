<template>
	<div>
		<div v-if="isMobile">
			<RecordNotAvailable />
		</div>
		<div v-else>
			<canvas ref="ulMainCanvas" class="tw-w-full hidden tw-rounded-xl"></canvas>
			<div class="tw-relative tw-mb-3" id="main-video-container">
				<video id="main-video" ref="mainVideo" class="tw-rounded-2xl" autoplay></video>
				<button
					id="main-video-placeholder"
					class="!tw-rounded-2xl"
					v-show="status === statusOpts.ASKING_PERMISSION"
					@click="() => initScreen()"
				>
					<SelectAScreenPlaceholder />
				</button>
				<ScreenBeingRecorded
					v-show="status === statusOpts.RECORDING && ['screen', 'webcam-screen'].includes(activeStreamName)"
					class="tw-absolute tw-h-full tw-w-full separator tw-rounded-2xl"
				/>
				<RollUploading
					v-show="status === statusOpts.UPLOADING"
					:perc="uploadPerc"
					class="tw-absolute tw-h-full tw-w-full separator tw-rounded-2xl"
				/>
			</div>
			<video id="screen-video" ref="screenVideo" muted autoplay class="invisible"></video>
			<video
				id="webcam-video"
				ref="webcamVideo"
				muted
				autoplay
				:style="{ visibility: webcamVideoVisibility }"
			></video>

			<div>
				<RecordActions
					:ready="status !== statusOpts.ASKING_PERMISSION && screenSharingHasEnded === false"
					:activeStreamName="activeStreamName"
					:streamMonitor="streamMonitor"
					:streamMonitorWithWebcam="streamMonitorWithWebcam"
					:streamWebcam="streamWebcam"
				/>
			</div>

			<div class="tw-pt-5"></div>

			<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
				<NcButton
					:disabled="![statusOpts.READY, statusOpts.RECORDING, statusOpts.UPLOAD_ERR].includes(status)"
					aria-label="Start recording"
					@click="startCapture()"
					:type="status === statusOpts.RECORDING ? 'error' : 'secondary'"
				>
					<template #icon>
						<Stop :size="20" v-if="status === statusOpts.RECORDING" />
						<NcLoadingIcon :size="20" v-else-if="status === statusOpts.UPLOADING"></NcLoadingIcon>
						<ArrowUp :size="20" v-else-if="status === statusOpts.UPLOAD_ERR"></ArrowUp>
						<Video :size="20" v-else class="video-icon" />
					</template>
					<span v-if="status === statusOpts.RECORDING">
						{{ videoDuration }}
					</span>
					<span v-else-if="status === statusOpts.UPLOADING">
						{{ t("rolls", "Uploading...") }}
					</span>
					<span v-else-if="status === statusOpts.UPLOAD_ERR">
						{{ t("rolls", "Try to upload again") }}
					</span>
					<span v-else>
						{{ t("rolls", "Start recording") }}
					</span>
				</NcButton>
				<div class="tw-ml-1"></div>
				<RecordSwitchSourceBtns
					v-if="status !== statusOpts.ASKING_PERMISSION"
					:activeWebcamId="webcamId"
					:activeMicId="micId"
					:ready="status === statusOpts.READY"
					v-on:webcamChange="setDevice"
					v-on:micChange="setDevice"
				/>
			</div>

			<div class="tw-py-5">
				<hr />
			</div>

			<div>
				<div class="tw-mb-8">
					<input
						type="text"
						class="tw-w-full tw-d-block !tw-border-none !tw-outline-none tw-font-bold !tw-text-3xl placeholder:!tw-text-3xl"
						:placeholder="t('rolls', 'Add a title')"
						v-model="title"
					/>
				</div>
				<div class="tw-relative">
					<span ref="addCommentBox">
						<div
							class="tw-text-white tw-inline-flex tw-px-2 tw-py-1 tw-rounded-full tw-flex-row tw-gap-2 bg-primary"
						>
							<Clock :size="20" />
							<span>{{ commentAt || videoDuration }}</span>
						</div>
						<input
							ref="inputComment"
							class="!tw-border-none !tw-outline-none tw-w-full"
							type="text"
							:placeholder="t('rolls', 'Add a comment at this timestamp')"
							@keydown="onCommentKeyDown"
							@focus="onCommentFocus"
							@blur="onCommentBlur"
						/>
					</span>

					<p v-for="comment in sortedComments" :key="comment.id">{{ comment.ts }} - {{ comment.text }}</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import fixWebmDuration from "fix-webm-duration";
import dayjs from "dayjs";
import Dexie from "dexie";
import dayjs_duration from "dayjs/plugin/duration";
import { NcButton, NcActionButton, NcActions, NcLoadingIcon, NcEmptyContent } from "@nextcloud/vue";
import { mdiClock } from "@mdi/js";
import Video from "vue-material-design-icons/Video.vue";
import Stop from "vue-material-design-icons/Stop.vue";
import Clock from "vue-material-design-icons/Clock.vue";
import Monitor from "vue-material-design-icons/Monitor.vue";
import ArrowUp from "vue-material-design-icons/ArrowUp.vue";
import Webcam from "vue-material-design-icons/Webcam.vue";
import OpenInNew from "vue-material-design-icons/OpenInNew.vue";
import axios from "@nextcloud/axios";
import PictureInPictureBottomRight from "vue-material-design-icons/PictureInPictureBottomRight.vue";
import { APP_API, DAV_URL } from "../constants";
import MarkdownEditor from "../components/MarkdownEditor.vue";
import { randomString } from "../utils/funcs";
import RecordActions from "../components/RecordActions.vue";
import RecordSwitchSourceBtns from "../components/RecordSwitchSourceBtns.vue";
import SelectAScreenPlaceholder from "../components/SelectAScreenPlaceholder.vue";
import RecordNotAvailable from "../components/RecordNotAvailable.vue";
import ScreenBeingRecorded from "../components/ScreenBeingRecorded.vue";
import RollUploading from "../components/RollUploading.vue";
import { videoDbName, videoDbSchema, videoDbTable } from "../lib/dexiedb";

dayjs.extend(dayjs_duration);

const videoMime = "video/webm";
const tbMime = "image/png";

export default {
	name: "Record",
	components: {
		NcButton,
		NcActionButton,
		NcActions,
		PictureInPictureBottomRight,
		OpenInNew,
		NcEmptyContent,
		Video,
		Stop,
		Monitor,
		ArrowUp,
		Webcam,
		NcLoadingIcon,
		MarkdownEditor,
		Clock,
		RecordActions,
		RecordSwitchSourceBtns,
		SelectAScreenPlaceholder,
		RecordNotAvailable,
		ScreenBeingRecorded,
		RollUploading,
	},
	setup() {
		return {
			mdiClock,
		};
	},
	data() {
		return {
			isUnmounting: false,
			title: "",
			comments: [],
			commentAt: null,
			webcamIsVisible: false,
			uploadPerc: 0,
			pictureInPictureSupported: false,
			webcamVideoVisibility: "hidden",
			// screen, webcam-stream, webcam-screen
			activeStreamName: "",
			screenSharingHasEnded: false,
			activeStream: undefined,
			/** @type {HTMLVideoElement | null} */
			ulFlippedWebcamVideo: null,
			/** @type {HTMLCanvasElement | null} */
			ulFlippedWebcamCanvas: null,
			/** @type {MediaStream} */
			audioStream: undefined,
			/** @type {Date | null} */
			videoStartedAt: null,
			/** @type {Date | null} */
			videoEndedAt: null,
			/** @type {number | undefined} */
			videoStartedInterval: undefined,
			/** @type {string} */
			videoDuration: "00:00",
			/** @type {MediaRecorder | undefined} */
			recorder: undefined,
			videoChunks: 0,
			/** @type {Blob | boolean} */
			thumbnail: false,
			/** @type {Dexie | undefined} */
			appDB: undefined,
			savingCompleted: false,
			status: "ASKING_PERMISSION",
			statusOpts: {
				ASKING_PERMISSION: "ASKING_PERMISSION",
				READY: "READY",
				BUSY: "BUSY",
				UPLOADING: "UPLOADING",
				RECORDING: "RECORDING",
				UPLOAD_ERR: "UPLOAD_ERR",
			},
			webcamId: undefined,
			micId: undefined,
		};
	},
	async mounted() {
		this.appDB = new Dexie(videoDbName);
		this.appDB.version(1).stores(videoDbSchema);
		this.isUnmounting = false;
	},
	beforeDestroy() {
		this.isUnmounting = true;
		this.dismissCapture();
	},
	beforeRouteLeave(to, from, next) {
		if (this.status === this.statusOpts.RECORDING) {
			const answer = window.confirm('Do you really want to leave while a recording is in progress?');
			if (!answer) return false;
		}

		next();
	},
	methods: {
		async initScreen() {
			try {
				await this.streamMonitor();
				await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
				this.status = this.statusOpts.READY;
			} catch (err) {
				console.error(err);
				alert("Permissions denied!");
			}
		},

		stopWebcam() {
			if (this.ulFlippedWebcamVideo) {
				this.ulFlippedWebcamVideo.srcObject.getTracks().forEach((track) => track.stop());

				this.ulFlippedWebcamVideo.remove();
			}

			if (this.ulFlippedWebcamCanvas) {
				this.ulFlippedWebcamCanvas
					.captureStream()
					.getTracks()
					.forEach((track) => track.stop());

				this.ulFlippedWebcamCanvas.remove();
			}

			if (this.$refs.webcamVideo) {
				this.webcamVideoVisibility = "hidden";

				if (this.$refs.webcamVideo.srcObject) {
					let wt = [];
					this.$refs.webcamVideo.srcObject.getTracks().forEach((track) => {
						wt.push(track);
						track.stop();
					});

					wt.forEach((t) => this.$refs.webcamVideo.srcObject.removeTrack(t));
				}

				if (document.pictureInPictureElement) {
					document.exitPictureInPicture();
				}
			}

			this.webcamIsVisible = false;
		},

		stopScreen() {
			if (this.$refs.screenVideo.srcObject) {
				this.$refs.screenVideo.srcObject.getTracks()[0].removeEventListener("ended", this.screenSharingEnded);
				this.$refs.screenVideo.srcObject.getTracks().forEach((track) => track.stop());
			}
		},

		stopCapture() {
			this.recorder.stop();
			window.removeEventListener("beforeunload", this.preventUnload);
		},

		dismissCapture() {
			this.stopWebcam();
			this.stopScreen();
			this.stopMic();
			clearInterval(this.videoStartedInterval);

			if (this.recorder) {
				this.stopCapture();
			}

			this.status = this.statusOpts.READY;
		},

		async startCapture() {
			if (this.status === this.statusOpts.UPLOAD_ERR) {
				this.initVideoUpload();
				return;
			}

			if (!this.$refs.screenVideo) {
				return;
			}

			if (this.videoStartedAt !== null) {
				this.stopCapture();
				return;
			}

			let streams = [];
			let audioTrack = null;

			const mainStream = this.$refs.mainVideo.srcObject;
			const videoTrack = mainStream.getTracks()[0];

			streams = [videoTrack];

			if (this.micId) {
				try {
					this.audioStream = await navigator.mediaDevices.getUserMedia({
						video: false,
						audio: { deviceId: this.micId },
					});

					audioTrack = this.audioStream.getAudioTracks()[0];
				} catch (err) {
					console.error(err);
					return;
				}
			}

			if (audioTrack) {
				streams = [...streams, audioTrack];
			}

			const mediaStream = new MediaStream(streams);

			this.recorder = new MediaRecorder(mediaStream, {
				mimeType: videoMime,
				audioBitsPerSecond: audioTrack ? audioTrack.getSettings().sampleRate : undefined,
				videoBitsPerSecond: videoTrack.getSettings().sampleRate,
			});

			this.recorder.ondataavailable = (event) => {
				this.saveVideoChunk(event);
			};

			await this.appDB.table(videoDbTable).clear();
			this.recorder.start(3 * 1000);

			this.status = this.statusOpts.RECORDING;
			this.videoStartedAt = new Date();
			this.savingCompleted = false;
			this.startVideoTimer();
			window.addEventListener("beforeunload", this.preventUnload);
		},

		stopMic() {
			if (this.audioStream) {
				this.audioStream.getTracks().forEach((track) => track.stop());
			}
		},

		screenSharingEnded(e) {
			if (typeof this.activeStream !== "undefined") {
				this.streamWebcam();
				this.screenSharingHasEnded = true;
			}
		},

		async createFlippedWebcamStream() {
			if (this.$refs.webcamVideo.srcObject && this.$refs.webcamVideo.srcObject.getTracks().length) {
				return;
			}

			try {
				let videoOpt = {
					width: this.$refs.screenVideo.videoWidth,
					height: this.$refs.screenVideo.videoHeight,
				};

				if (this.webcamId) {
					videoOpt.deviceId = this.webcamId;
				}

				const stream = await navigator.mediaDevices.getUserMedia({
					video: videoOpt,
					audio: false,
				});

				this.ulFlippedWebcamVideo = document.createElement("video");
				this.ulFlippedWebcamVideo.setAttribute("muted", true);
				this.ulFlippedWebcamVideo.classList.add("hidden");

				this.ulFlippedWebcamVideo.srcObject = stream;

				await this.ulFlippedWebcamVideo.play();

				this.ulFlippedWebcamCanvas = document.createElement("canvas");
				this.ulFlippedWebcamCanvas?.classList.add("hidden");

				this.webcamIsVisible = true;
				this.drawVideoOnCanvas(this.ulFlippedWebcamVideo, this.ulFlippedWebcamCanvas);

				const canvasStream = this.ulFlippedWebcamCanvas.captureStream(30);

				this.$refs.webcamVideo.srcObject = canvasStream;
				await this.$refs.webcamVideo.play();
			} catch (err) {
				console.error(err);
				return;
			}
		},

		async streamMonitor(stopPip = true) {
			if (this.activeStreamName === "screen") {
				return;
			}

			if (stopPip && document.pictureInPictureElement) {
				document.exitPictureInPicture();
			}

			const displayMediaOptions = {
				video: {
					displaySurface: "monitor",
				},
				preferCurrentTab: false,
				selfBrowserSurface: "exclude",
				systemAudio: "exclude",
				surfaceSwitching: "include",
				monitorTypeSurfaces: "include",
			};

			if (!this.$refs.screenVideo.srcObject) {
				this.$refs.screenVideo.srcObject = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions);
			}

			this.$refs.screenVideo.play();
			this.screenSharingHasEnded = false;
			this.$refs.screenVideo.srcObject.getTracks()[0].addEventListener("ended", this.screenSharingEnded);
			this.setVideoOnMainCanvas(this.$refs.screenVideo, "screen");
		},

		async streamWebcam(force = false) {
			if (this.activeStreamName === "webcam-stream" && !force) {
				return;
			}

			if (document.pictureInPictureElement) {
				document.exitPictureInPicture();
			}

			await this.createFlippedWebcamStream();
			this.setVideoOnMainCanvas(this.$refs.webcamVideo, "webcam-stream");
		},

		async streamMonitorWithWebcam() {
			if (this.activeStreamName === "webcam-screen" && this.webcamIsVisible) {
				return;
			}

			this.streamMonitor(false);
			this.openWebcamInPip();

			this.activeStreamName = "webcam-screen";
		},

		setVideoOnMainCanvas(video, name) {
			this.activeStreamName = name;
			if (!this.activeStream) {
				this.activeStream = video;
				this.drawVideoOnMainCanvas();

				const stream = this.$refs.ulMainCanvas.captureStream(30);
				this.$refs.mainVideo.srcObject = stream;
			} else {
				this.activeStream = video;
			}
		},

		async openWebcamInPip() {
			if (!this.$refs.webcamVideo) {
				return;
			}

			await this.createFlippedWebcamStream();

			if (document.pictureInPictureEnabled) {
				await this.$refs.webcamVideo.requestPictureInPicture();
			}
		},

		/**
		 *  @param target {HTMLCanvasElement}
		 * @param source {HTMLVideoElement}
		 */
		drawVideoOnCanvas(source, target, streamName = "webcam") {
			const ctx = target.getContext("2d");

			if (source && source.videoWidth > 0 && source.videoHeight > 0) {
				target.width = source.videoWidth;
				target.height = source.videoHeight;

				if (streamName === "webcam") {
					ctx.scale(-1, 1);
					ctx.drawImage(source, target.width * -1, 0);
				} else {
					ctx.drawImage(source, 0, 0);
				}

				this.wbFrames += 1;
			}

			requestAnimationFrame(() => {
				if (source && !source.paused) {
					this.drawVideoOnCanvas(source, target, streamName);
				}
			});
		},

		drawVideoOnMainCanvas() {
			const target = this.$refs.ulMainCanvas;

			if (!target) {
				return;
			}

			const source = this.activeStream;
			const ctx = target.getContext("2d");

			if (source && source.videoWidth > 0 && source.videoHeight > 0) {
				target.width = this.$refs.screenVideo.videoWidth;
				target.height = this.$refs.screenVideo.videoHeight;

				if (source.videoWidth !== target.width && source.videoHeight !== target.height) {
					// Get canvas dimensions
					const canvasWidth = target.width;
					const canvasHeight = target.height;

					// Get image dimensions
					const imageWidth = source.videoWidth;
					const imageHeight = source.videoHeight;

					// Calculate the ratio of the canvas and image
					const canvasRatio = canvasWidth / canvasHeight;
					const imageRatio = imageWidth / imageHeight;

					// Determine how to fit the image based on ratios
					let newWidth, newHeight;
					if (canvasRatio > imageRatio) {
						// Image is narrower than canvas
						newWidth = canvasWidth;
						newHeight = Math.round(canvasWidth / imageRatio);
					} else {
						// Image is wider than canvas
						newHeight = canvasHeight;
						newWidth = Math.round(canvasHeight * imageRatio);
					}

					// Calculate the X and Y offsets to center the image
					const dx = (canvasWidth - newWidth) * 0.5;
					const dy = (canvasHeight - newHeight) * 0.5;

					ctx.drawImage(source, dx, dy, newWidth, newHeight);
				} else {
					ctx.drawImage(source, 0, 0, target.width, target.height);
				}

				if (this.status === this.statusOpts.RECORDING && !this.thumbnail) {
					this.thumbnail = true;
					target.toBlob((b) => {
						this.thumbnail = b;
						console.log("Saved thumbnail!");
					}, tbMime);
				}

				this.wbFrames += 1;
			}

			requestAnimationFrame(() => {
				if (source && !source.paused) {
					this.drawVideoOnMainCanvas(source, target);
				}
			});
		},

		/**
		 * @param event BlobEvent
		 */
		async saveVideoChunk(event) {
			if (event.data.size <= 0) {
				return;
			}

			this.videoChunks = this.videoChunks + 1;
			await this.appDB.table(videoDbTable).add({
				id: this.videoChunks,
				blob: event.data,
			});

			console.log("Saved chunk!", this.recorder.state);
			if (this.recorder.state === "inactive") {
				if (this.savingCompleted) {
					return;
				}

				this.videoEndedAt = new Date();
				this.savingCompleted = true;
				this.initVideoUpload();
			}
		},

		startVideoTimer() {
			clearInterval(this.videoStartedInterval);
			this.videoDuration = "00:00";

			this.videoStartedInterval = setInterval(() => {
				const diff = dayjs().diff(this.videoStartedAt, "milliseconds");
				if (diff > 1000 * 3600) {
					this.videoDuration = dayjs.duration(diff).format("HH:mm:ss");
				} else {
					this.videoDuration = dayjs.duration(diff).format("mm:ss");
				}
			}, 1000);
		},

		async initVideoUpload() {
			if (this.isUnmounting) {
				return;
			}

			try {
				this.stopWebcam();
				this.stopScreen();
				this.stopMic();
				this.uploadPerc = 0;

				const data = await this.uploadVideo();
				this.videoDuration = "00:00";
				this.videoStartedAt = null;
				this.videoEndedAt = null;
				this.thumbnail = false;
				this.activeStream = undefined;
				this.commentAt = null;
				this.$refs.inputComment.value = "";
				this.comments = [];
				clearInterval(this.videoStartedInterval);

				this.$router.push("/watch/" + data.data.uuid);
			} catch (err) {
				console.error(err);
				this.status = this.statusOpts.UPLOAD_ERR;

				alert(t("rolls", "An error occurred while uploading your Roll"));
			}
		},

		async uploadVideo() {
			const duration = this.videoEndedAt.getTime() - this.videoStartedAt.getTime();
			const chunksData = await this.appDB.table(videoDbTable).orderBy("id").toArray();

			let recordedBlob = new Blob([...chunksData.map((c) => c.blob)], {
				type: videoMime,
			});

			recordedBlob = await fixWebmDuration(recordedBlob, duration, {});

			this.status = this.statusOpts.UPLOADING;

			const ext = {
				"video/webm": "webm",
				"video/x-matroska": "mkv",
				"video/mp4": "mp4",
				"image/png": "png",
			};

			let text = "";

			if (this.title.length) {
				text = `# ${this.title}\n`;
			}

			this.comments.forEach((comment) => {
				text += `> [${comment.ts}]\n`;
				text += `> ${comment.text}`;
				text += `\n\n`;
			});

			const formData = new FormData();
			formData.append("video", recordedBlob);
			formData.append("thumbnail", this.thumbnail);
			formData.append("text", new Blob([text], { type: "text/plain" }));

			return (
				await axios.post(
					`${APP_API}/rolls`,
					formData,
					{
						params: { ext: ext[videoMime] },
						headers: {
							"Content-Type": "multipart/form-data",
						},
					},
					{
						onUploadProgress: function (progressEvent) {
							this.uploadPerc = Math.round((progressEvent.loaded * 100) / progressEvent.total);
						},
					}
				)
			).data;
		},

		onCommentKeyDown(e) {
			const { target } = e;
			if (e.key === "Enter" || e.keyCode === 13) {
				e.preventDefault();
				this.comments = [
					...this.comments,
					{
						text: target.value,
						ts: this.commentAt,
						id: randomString(10),
					},
				];

				target.value = "";
				this.commentAt = null;

				if (document.activeElement === this.$refs.inputComment) {
					this.onCommentFocus();
				}
			}
		},

		onCommentFocus() {
			this.commentAt = this.videoDuration;
		},

		onCommentBlur(event) {
			const { target } = event;

			if (target.value.length === 0) {
				this.commentAt = null;
			}
		},

		async openCommentPip(event) {
			if (!documentPictureInPicture) {
				return;
			}

			const pipWindow = await documentPictureInPicture.requestWindow();

			[...document.styleSheets].forEach((styleSheet) => {
				try {
					const cssRules = [...styleSheet.cssRules].map((rule) => rule.cssText).join("");
					const style = document.createElement("style");

					style.textContent = cssRules;
					pipWindow.document.head.appendChild(style);
				} catch (e) {
					const link = document.createElement("link");

					link.rel = "stylesheet";
					link.type = styleSheet.type;
					link.media = styleSheet.media;
					link.href = styleSheet.href;
					pipWindow.document.head.appendChild(link);
				}
			});

			pipWindow.document.body.append(this.$refs.addCommentBox);
		},

		/**
		 * @param device { MediaDeviceInfo }
		 */
		async setDevice(device) {
			if (device.kind === "audioinput") {
				this.micId = device.deviceId;
			} else if (device.kind === "videoinput") {
				this.webcamId = device.deviceId;

				if (["webcam-stream", "webcam-screen"].includes(this.activeStreamName)) {
					this.stopWebcam();
					await this.createFlippedWebcamStream();
				}

				if ("webcam-screen" === this.activeStreamName) {
					this.$refs.webcamVideo.requestPictureInPicture();
				}
			}
		},

		preventUnload(e) {
			e.preventDefault();
			e.returnValue = "";
		},
	},
	computed: {
		sortedComments() {
			return this.comments.slice().reverse();
		},
		isMobile() {
			return window.isMobile;
		},
	},
};
</script>

<style scoped>
#main-video {
	height: 100%;
	background-color: transparent;
	width: fit-content;
}

#main-video-container {
	position: relative;
	width: 100%;
	aspect-ratio: 16 / 9;
	display: flex;
	flex-flow: column;
	justify-content: center;
	align-items: center;
}

#main-video-placeholder {
	z-index: 2;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	margin: 0;
}

#webcam-video {
	position: absolute;
	bottom: 0;
	left: 0;
	background-color: black;
	width: 200px;
}

.hidden {
	display: none;
}

.invisible {
	visibility: hidden;
	z-index: -99;
	position: absolute;
	bottom: 0;
}

.video-icon {
	color: red;
}

.bg-primary {
	background-color: var(--color-primary-element);
}
</style>

<style>
div.content-wrapper.text-editor__content-wrapper > div.editor__content.text-editor__content {
	max-width: 100% !important;
}

.text-menubar.text-menubar--ready .text-menubar__entries {
	margin-left: 0 !important;
}
</style>