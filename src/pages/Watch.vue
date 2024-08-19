<template>
	<div>
		<NcNoteCard v-if="ready && !roll" type="error" :heading="t('rolls', 'Error')">
			{{ t("rolls", "This roll was not found") }}
		</NcNoteCard>

		<div v-if="ready && videoUrl" class="tw-mb-10" ref="player">
			<VideoPlayer :src="videoUrl" :markers="markers"></VideoPlayer>
		</div>

		<div v-if="ready && roll">
			<div class="tw-mb-5" v-if="ready">
				<h1 class="!tw-text-3xl tw-font-bold">{{ title }}</h1>
				<small
					>{{ t("roll", "Created by") }}:
					<a :href="`${APP_INDEX}/u/${roll.owner.id}`" class="tw-underline">
						{{ roll.owner.displayName }}
					</a>
				</small>
			</div>

			<div>
				<div class="tw-flex tw-flex-row tw-items-center tw-gap-3 tw-mb-5" ref="tabSelector">
					<Tab
						:title="t('rolls', 'description')"
						name="description"
						:activeTab="activeTab"
						@click="changeActiveTab"
					>
						<template #icon>
							<TextIcon :size="20" />
						</template>
					</Tab>
					<Tab
						:title="t('rolls', 'comments')"
						name="comments"
						:activeTab="activeTab"
						@click="changeActiveTab"
					>
						<template #icon>
							<Comment :size="20" />
						</template>
					</Tab>
					<div class="tw-h-10 tw-w-0.5 separator"></div>
					<NcButton>
						<template #icon>
							<ArrowDown :size="20" />
						</template>
						<a
							name="download"
							:download="roll ? roll.file.path.split('/').at(-1) : false"
							:href="videoUrl"
							:disabled="!roll"
						>
							<div class="tw-capitalize">{{ t("files", "Download") }}</div>
						</a>
					</NcButton>
					<NcButton v-if="ready" class="button" @click="copyUrl">
						<template #icon>
							<ContentCopy v-if="!isCopying" :size="20" />
							<Check v-else :size="20" />
						</template>
						<div class="tw-capitalize">
							<span v-if="!isCopying">{{ t("rolls", "Copy url") }}</span>
							<span v-else>{{ t("rolls", "Copied!") }}</span>
						</div>
					</NcButton>
					<NcButton v-if="ready && roll.isMine" class="button" @click="openShareModal">
						<template #icon>
							<Share :size="20" />
						</template>
						<div class="tw-capitalize">{{ t("files", "Share") }}</div>
					</NcButton>
					<NcButton v-if="ready && roll.isMine" class="button" type="error" @click="deleteRoll">
						<template #icon>
							<Delete :size="20" />
						</template>
						<div class="tw-capitalize">{{ t("files", "Delete") }}</div>
					</NcButton>
				</div>

				<div>
					<div v-show="activeTab === 'description'" class="tw-relative">
						<div class="tw-text-right" v-if="roll && roll.isMine">
							<button :disabled="busy" v-if="!editMode" class="tw-z-10" @click="enableEditMode">
								<IconEdit :size="20" />
							</button>
							<button :disabled="busy" v-else class="tw-z-10" @click="disableEditMode">
								<Close :size="20" />
							</button>
						</div>
						<div class="muted" v-if="roll.text.length === 0 && !editMode">
							<i>{{ t("rolls", "No description provided") }}</i>
						</div>
						<div ref="editor"></div>
					</div>
					<div v-show="activeTab === 'comments'">
						<CommentsBox :roll="roll" v-if="ready" />
					</div>
				</div>
			</div>
		</div>

		<SharePopup
			:path="roll.file.folder"
			itemType="folder"
			v-if="showShareModal && roll.isMine"
			@close="closeShareModal"
		/>
	</div>
</template>

<script>
import axios from "@nextcloud/axios";
import VideoPlayer from "./../components/VideoPlayer.vue";
import { APP_API, APP_INDEX, APP_URL, DAV_URL, REMOTE_URL } from "../constants";
import { convertUrlsToMarkdown, validateUUID, xmlResponse } from "../utils/funcs";
import MarkdownPreview from "../components/MarkdownPreview.vue";
import MarkdownEditor from "../components/MarkdownEditor.vue";
import { NcLoadingIcon, NcListItem, NcAvatar, NcActions, NcButton, NcActionButton, NcNoteCard } from "@nextcloud/vue";
import Comment from "vue-material-design-icons/Comment.vue";
import CommentOff from "vue-material-design-icons/CommentOff.vue";
import TextIcon from "vue-material-design-icons/Text.vue";
import IconEdit from "vue-material-design-icons/Pencil.vue";
import ArrowDown from "vue-material-design-icons/ArrowDown.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Close from "vue-material-design-icons/Close.vue";
import Send from "vue-material-design-icons/Send.vue";
import Share from "vue-material-design-icons/Share.vue";
import ContentCopy from "vue-material-design-icons/ContentCopy.vue";
import Check from "vue-material-design-icons/Check.vue";
import Tab from "../components/Tab.vue";
import { COMMENTS_DAYJS_FORMAT, PROMISE_STATUS } from "../utils/constants";
import CommentsBox from "../components/CommentsBox.vue";
import SharePopup from "../components/SharePopup.vue";


export default {
	name: "Watch",
	components: {
		ArrowDown,
		VideoPlayer,
		MarkdownPreview,
		MarkdownEditor,
		Comment,
		Tab,
		CommentOff,
		TextIcon,
		NcLoadingIcon,
		NcListItem,
		Send,
		NcActionButton,
		NcButton,
		NcAvatar,
		NcActions,
		IconEdit,
		Delete,
		CommentsBox,
		NcNoteCard,
		Close,
		Share,
		SharePopup,
		Check,
		ContentCopy,
	},
	data() {
		return {
			APP_INDEX,
			isCopying: false,
			showShareModal: false,
			activeTab: "description",
			/** @type { object | undefined} */
			roll: undefined,
			/** @type { string | undefined} */
			videoUrl: undefined,
			title: "",
			description: "",
			editor: undefined,
			editMode: false,
			markers: [],
			comments: [],
			commentsStatus: PROMISE_STATUS.done,
			submitCommentStatus: PROMISE_STATUS.done,
			ready: false,
			busy: false,
			PROMISE_STATUS,
			currentUser: window.OC.getCurrentUser(),
			newComment: "",
		};
	},
	async mounted() {
		const rolls = await this.getRoll();

		if (rolls.length) {
			this.roll = rolls[0];

			const creationDate = new Date(this.roll.creationDate * 1000);

			this.videoUrl = `${DAV_URL}/files${this.roll.file.path}`;
			this.title = creationDate.toLocaleString() + ", Roll";

			this.loadDescription();
		}

		this.ready = true;
	},
	methods: {
		async getRoll() {
			const uuid = this.$route.params.uuid;

			const { data } = await axios.get(`${APP_API}/rolls`, { params: { uuid } });
			const rolls = data.data;
			return rolls;
		},

		changeActiveTab(e) {
			this.activeTab = e.name;
			this.$refs.tabSelector.scrollIntoView();
		},

		async loadDescription() {
			let { text } = this.roll;

			let content = "";

			if (text) {
				if (text.startsWith("#")) {
					const tokens = text.split("\n");
					this.title = tokens[0].replace(/^#/, "").trim();

					if (tokens.length > 1) {
						tokens.splice(0, 1);
						content = tokens.join("\n");
					}
				} else {
					content = text;
				}

				content = content.trim();

				if (!content.length) {
					content = descriptionPlaceholder;
				} else {
					const blockQuoteReg = new RegExp(/(^(\>{1})(\s)(.*)(?:$)?\n)+/, "gm");

					let blockQuotes = text.match(blockQuoteReg) || [];
					let markers = [];

					const timestampRegex = new RegExp(/^>\s\[((\d{2}:\d{2})|((\d{2}:)\d{2}:\d{2}))\]\n/);

					for (let element of blockQuotes) {
						element = element.trim();
						const match = element.match(timestampRegex);

						if (!match) {
							continue;
						}

						let time = 0;
						const lines = element.split(">");

						if (lines.length < 3) {
							// Empty note
							continue;
						}

						const contentString = lines[2].trim();
						const durationString = match[1];
						const tokens = durationString.split(":").map((el) => parseInt(el));

						if (tokens.length === 2) {
							// Minutes, seconds
							time = tokens[0] * 60 + tokens[1];
						} else {
							// Hours, minutes, seconds
							time = tokens[0] * 3600 + tokens[1] * 60 + tokens[2];
						}

						markers.push({
							time,
							label: contentString,
						});
					}

					this.markers = markers;
					content = convertUrlsToMarkdown(content);
				}
			}

			await this.$nextTick();

			if (window.OCA.Text) {
				if (this.editor) {
					this.editor.destroy();
				}

				if (this.editMode) {
					let editorConf = {
						content: content,
						el: this.$refs.editor,
						readOnly: false,
					};

					if (this.roll.textFile) {
						editorConf = {
							el: this.$refs.editor,
							readOnly: true,
							filePath: this.roll.textFile.path,
							fileId: this.roll.textFile.id,
						};
					}

					this.editor = await window.OCA.Text.createEditor(editorConf);
				} else {
					this.editor = await window.OCA.Text.createEditor({
						content: content,
						el: this.$refs.editor,
						readOnly: true,
					});
				}
			}
		},

		enableEditMode() {
			this.editMode = true;
			this.loadDescription();
		},

		async disableEditMode() {
			this.editMode = false;

			this.busy = true;
			const rolls = await this.getRoll();

			if (rolls.length) {
				this.roll = rolls[0];
			}

			this.busy = false;
			this.loadDescription();
		},

		async deleteRoll() {
			const confirmation = confirm(t("roll", "Do you really want to delete this Roll?"));

			if (!confirmation) {
				return;
			}

			const uuid = this.$route.params.uuid;
			await axios.delete(`${APP_API}/rolls/${uuid}`);

			this.$router.push("/");
		},

		openShareModal(roll) {
			this.showShareModal = true;
		},

		closeShareModal() {
			this.showShareModal = false;
		},

		copyUrl() {
			if (!this.roll) {
				return;
			}

			this.isCopying = true;
			const url = `${window.location.origin}/index.php/apps/rolls/watch/${this.roll.uuid}`;
			navigator.clipboard.writeText(url);
			setTimeout(() => (this.isCopying = false), 3000);
		},
	},
};
</script>

<style>
div.content-wrapper.text-editor__content-wrapper > div.editor__content.text-editor__content {
	max-width: 100% !important;
}

.editor .text-readonly-bar:has(span.format-list-bulleted-icon) {
	display: none;
}
</style>