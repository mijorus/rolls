<template>
	<div>
		<div v-if="commentsStatus === PROMISE_STATUS.done" class="tw-mb-20">
			<div v-if="comments.length">
				<ul class="tw-mb-10">
					<NcListItem
						v-for="comment in comments"
						:key="comment['oc:id']"
						:name="comment['oc:actorDisplayName']"
						:bold="true"
						:active="false"
						:force-display-actions="true"
						:details="comment['fromNow']"
					>
						<template #icon>
							<NcAvatar
								disable-menu
								:size="44"
								:user="comment['oc:actorId']"
								:display-name="comment['oc:actorDisplayName']"
							/>
						</template>
						<template #subname>
							<i v-if="newCommenEditMode === comment['oc:id']">
								{{ t("rolls", "Editing") }}: {{ comment["oc:message"] }}
							</i>
							<span v-else>
								{{ comment["oc:message"] }}
							</span>
						</template>
						<template #actions>
							<NcActionButton @click="() => setEditComment(comment)">
								<template #icon>
									<IconEdit :size="20" />
								</template>
								{{ t("comments", "Edit comment") }}
							</NcActionButton>
							<NcActionButton @click="() => removeComment(comment)">
								<template #icon>
									<Delete :size="20" />
								</template>
								{{ t("comments", "Delete comment") }}
							</NcActionButton>
						</template>
					</NcListItem>
				</ul>
			</div>
			<div v-else class="muted tw-pb-10 tw-pt-5">
				<NcEmptyContent
					:name="t('rolls', 'No comments yet')"
					:description="t('rolls', 'Start writing comments and they will appear here.')"
				>
					<template #icon>
						<Comment />
					</template>
				</NcEmptyContent>
			</div>

			<form class="tw-flex tw-flex-row tw-gap-2" @submit.prevent>
				<NcAvatar disable-menu :size="44" :user="currentUser.uid" :display-name="currentUser.displayName" />
				<textarea
					@keydown="(e) => detectSubmitOnType(e, onCommentSave)"
					v-model="newComment"
					name="newcomment"
					class="tw-w-full"
					style="resize: vertical"
					:placeholder="t('rolls', 'Type a comment')"
				></textarea>
				<div class="tw-flex tw-flex-col tw-gap-2">
					<NcButton
						@click="onCommentSave"
						variant="secondary"
						action="submit"
						:disabled="newComment.length === 0 || submitCommentStatus !== PROMISE_STATUS.done"
					>
						<template #icon>
							<Send :size="20" v-if="submitCommentStatus === PROMISE_STATUS.done" />
							<NcLoadingIcon :size="20" v-else />
						</template>
						{{ newCommenEditMode === null ? t("rolls", "Add") : t("rolls", "Update") }}
					</NcButton>
					<NcButton type="tertiary-no-background" @click="discardEditing" v-if="newCommenEditMode !== null">
						{{ t("rolls", "Discard") }}
					</NcButton>
				</div>
			</form>
		</div>
		<div v-else-if="commentsStatus === PROMISE_STATUS.pending" class="tw-flex tw-flex-row tw-justify-center">
			<NcLoadingIcon />
		</div>
	</div>
</template>

<script>
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import axios from "@nextcloud/axios";
import { NcLoadingIcon, NcListItem, NcAvatar, NcActions, NcButton, NcActionButton, NcEmptyContent } from "@nextcloud/vue";
import Comment from "vue-material-design-icons/Comment.vue";
import TextIcon from "vue-material-design-icons/Text.vue";
import IconEdit from "vue-material-design-icons/Pencil.vue";
import ArrowDown from "vue-material-design-icons/ArrowDown.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Send from "vue-material-design-icons/Send.vue";
import Tab from "../components/Tab.vue";
import { COMMENTS_DAYJS_FORMAT, PROMISE_STATUS } from "../utils/constants";
import { createComment, deleteComment, editComment, getComments } from "../utils/comments";

dayjs.extend(relativeTime);

export default {
	name: "Watch",
	components: {
		NcEmptyContent,
		ArrowDown,
		Comment,
		Tab,
		Comment,
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
	},
	props: {
		roll: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			comments: [],
			commentsStatus: PROMISE_STATUS.done,
			submitCommentStatus: PROMISE_STATUS.done,
			ready: false,
			PROMISE_STATUS,
			currentUser: window.OC.getCurrentUser(),
			newComment: "",
			newCommenEditMode: null,
		};
	},
	mounted() {
		this.loadComments();
	},
	methods: {
		async loadComments() {
			if (!this.$props.roll) {
				return;
			}

			this.commentsStatus = PROMISE_STATUS.pending;
			this.comments = await getComments(this.roll.file.id);
			this.commentsStatus = PROMISE_STATUS.done;
		},

		async onCommentSave({ target }) {
			if (this.newComment.length === 0) {
				return;
			}

			const fileId = this.$props.roll.file.id;
			let id;

			try {
				this.submitCommentStatus = PROMISE_STATUS.pending;
				if (this.newCommenEditMode !== null) {
					await editComment(fileId, this.newCommenEditMode, this.newComment);
					await this.loadComments();
					this.newCommenEditMode = null;
				} else {
					id = await createComment(fileId, this.newComment);
					const newItem = await getComments(fileId, id);
					this.comments = [...this.comments, newItem[0]];
				}
			} catch (e) {
				console.error(e);
				alert("Something went wrong while saving the comment");
			}

			this.newComment = "";
			this.submitCommentStatus = PROMISE_STATUS.done;
		},

		async removeComment(comment) {
			const id = comment["oc:id"];
			await deleteComment(this.roll.file.id, id);

			let toDelete;
			for (let i = 0; i < this.comments.length; i++) {
				const c = this.comments[i];
				if (c["oc:id"] === id) {
					toDelete = i;
					break;
				}
			}

			if (typeof toDelete !== "undefined") {
				this.comments.splice(toDelete, 1);
			}
		},

		setEditComment(comment) {
			const id = comment["oc:id"];
			this.newCommenEditMode = id;
			this.newComment = comment["oc:message"];
		},

		discardEditing() {
			this.newCommenEditMode = null;
			this.newComment = "";
		},
	},
};
</script>