<template>
	<div>
		<div v-if="ready && videoUrl" class="tw-mb-10" ref="player">
			<VideoPlayer :src="videoUrl" :markers="markers"></VideoPlayer>
		</div>

		<div>
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
				<div class="tw-flex tw-flex-row tw-items-center tw-gap-3 tw-mb-5">
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
					<NcButton v-if="ready && roll.isMine" class="button" type="error" @click="deleteRoll">
						<template #icon>
							<Delete :size="20" />
						</template>
						<div class="tw-capitalize">{{ t("files", "Delete") }}</div>
					</NcButton>
				</div>

				<div>
					<div ref="editor" v-show="activeTab === 'description'"></div>
					<div v-show="activeTab === 'comments'">
						<CommentsBox :roll="roll" v-if="ready" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import axios from "@nextcloud/axios";
import VideoPlayer from "./../components/VideoPlayer.vue";
import { APP_API, APP_INDEX, APP_URL, DAV_URL, REMOTE_URL } from "../constants";
import { convertUrlsToMarkdown, validateUUID, xmlResponse } from "../utils/funcs";
import MarkdownPreview from "../components/MarkdownPreview.vue";
import MarkdownEditor from "../components/MarkdownEditor.vue";
import { NcLoadingIcon, NcListItem, NcAvatar, NcActions, NcButton, NcActionButton } from "@nextcloud/vue";
import Comment from "vue-material-design-icons/Comment.vue";
import CommentOff from "vue-material-design-icons/CommentOff.vue";
import TextIcon from "vue-material-design-icons/Text.vue";
import IconEdit from "vue-material-design-icons/Pencil.vue";
import ArrowDown from "vue-material-design-icons/ArrowDown.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Send from "vue-material-design-icons/Send.vue";
import Tab from "../components/Tab.vue";
import { COMMENTS_DAYJS_FORMAT, PROMISE_STATUS } from "../utils/constants";
import { createComment, deleteComment, getComments } from "../utils/comments";
import CommentsBox from "../components/CommentsBox.vue";

dayjs.extend(relativeTime);

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
	},
	data() {
		return {
			APP_INDEX,
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
			PROMISE_STATUS,
			currentUser: window.OC.getCurrentUser(),
			newComment: "",
		};
	},
	async mounted() {
		const uuid = this.$route.params.uuid;

		if (!validateUUID(uuid)) {
			alert(t("rolls", "This URL is not valid"));
			return;
		}

		const rolls = (await axios.get(`${APP_API}/rolls`, { params: { uuid } })).data.data;

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
		changeActiveTab(e) {
			this.activeTab = e.name;
			this.$refs.player.scrollIntoView();
		},

		async loadDescription() {
			let { text } = this.roll;

			const descriptionPlaceholder = `*${t("rolls", "No description provided")}*`;

			this.description = descriptionPlaceholder;

			if (text) {
				if (text.startsWith("#")) {
					const tokens = text.split("\n", 2);
					this.title = tokens[0].replace(/^#/, "").trim();

					if (tokens.length > 1) {
						this.description = tokens[1];
					}
				} else {
					this.description = text.trim();

					if (!this.description.length) {
						this.description = descriptionPlaceholder;
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
						this.description = convertUrlsToMarkdown(this.description);
					}
				}
			}

			if (window.OCA.Text) {
				if (this.editor) {
					this.editor.destroy();
				}

				if (this.editMode) {
					let editorConf = {
						content: this.description,
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
					console.log(this.description);
					this.editor = await window.OCA.Text.createEditor({
						content: this.description,
						el: this.$refs.editor,
						readOnly: true,
					});
				}
			}
		},

		async deleteRoll() {
			const confirmation = confirm(t("roll", "Do you really want to delete this roll?"));

			if (!confirmation) {
				return;
			}

			const uuid = this.$route.params.uuid;
			await axios.delete(`${APP_API}/rolls`, { params: { uuid } });

			this.$router.push("/");
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