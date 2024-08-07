<template>
	<div>
		<div v-if="ready">
			<ul v-if="rolls.length">
				<li v-for="roll in rolls" :key="roll.uuid" class="tw-relative">
					<RouterLink
						:to="'/watch/' + roll.uuid"
						class="tw-relative list-item tw-my-5 tw-flex tw-flex-col md:tw-flex-row tw-gap-2"
					>
						<div class="tw-w-60 tw-h-30">
							<img
								v-if="roll.thumbnail"
								:src="DAV_URL + '/files/' + roll.thumbnail"
								alt=""
								class="tw-w-full tw-h-full tw-object-cover tw-rounded-xl"
							/>
							<div v-else class="tw-bg-black"></div>
						</div>
						<div>
							<h2 class="tw-text-xl">{{ roll.title }}</h2>
							<div class="muted tw-text-muted tw-whitespace-pre-line tw-overflow-hidden tw-text-ellipsis">
								{{ roll.description }}
							</div>
						</div>
					</RouterLink>
					<div v-if="roll.isMine" class="tw-absolute tw-right-0 tw-top-0 tw-z-10">
						<NcActions :inline="0">
							<NcActionButton @click="showMessage('Edit')">
								<template #icon>
									<Share :size="20" />
								</template>
								Share
							</NcActionButton>
							<NcActionButton @click="() => onDeleteClicked(roll)">
								<template #icon>
									<Delete :size="20" />
								</template>
								Delete
							</NcActionButton>
						</NcActions>
					</div>
				</li>
			</ul>
			<div v-else>
				<NcEmptyContent
					:name="t('rolls', 'No rolls yet')"
					:description="t('rolls', 'Start by creating a new roll using the button above.')"
				>
					<template #icon>
						<MovieRoll />
					</template>
				</NcEmptyContent>
			</div>
		</div>
	</div>
</template>

<script>
import { APP_API, DAV_URL, APP_URL, APP_INDEX } from "../constants";
import axios from "@nextcloud/axios";
import { NcListItem, NcActionButton, NcActions, NcLoadingIcon, NcEmptyContent } from "@nextcloud/vue";
import MarkdownPreview from "./MarkdownPreview.vue";
import RecordCircleOutline from "vue-material-design-icons/RecordCircleOutline.vue";
import MovieRoll from "vue-material-design-icons/MovieRoll.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Share from "vue-material-design-icons/Share.vue";

export default {
	name: "ListRolls",
	components: {
		NcListItem,
		MarkdownPreview,
		NcEmptyContent,
		MovieRoll,
		NcActionButton,
		NcActions,
		Delete,
		Share
	},
	async mounted() {
		let rolls = (await axios.get(`${APP_API}/rolls`)).data.data;

		this.rolls = rolls.map((roll) => {
			if (!roll.text) {
				roll.text = "";
			}

			const text = roll.text.trim();
			const creationDate = new Date(roll.creationDate * 1000);

			roll.title = creationDate.toLocaleString() + ", Roll";
			roll.description = "";

			if (text.startsWith("#")) {
				const tokens = text.split("\n", 2);
				roll.title = tokens[0].replace(/^#/, "").trim();

				if (tokens.length > 1) {
					roll.description = tokens[1];
				}
			} else {
				roll.description = text.trim();
			}

			let splittedDesc = roll.description.split("\n");
			let descHasEllipsis = false;

			if (splittedDesc.length > 3) {
				splittedDesc = splittedDesc.slice(0, 3);
				descHasEllipsis = true;
			}

			roll.description = splittedDesc.join("\n");

			if (descHasEllipsis || roll.description.length > 200) {
				roll.description = roll.description.slice(0, 200) + "...";
			}

			return roll;
		});

		this.ready = true;
	},
	data() {
		return {
			ready: false,
			rolls: [],
			DAV_URL,
			APP_URL,
		};
	},
	methods: {
		async onDeleteClicked(roll) {
			const response = confirm(t('rolls', 'Do you really want to delete this Roll?'));
			if (!response) {
				return;
			}

			await axios.delete(`${APP_API}/rolls/${roll.uuid}`);
			this.rolls = this.rolls.filter(el => el.uuid !== roll.uuid);
		}
	},
};
</script>

<style scoped>
.list-item:hover {
	background-color: var(--color-background-hover);
}

.list-item {
	border-radius: var(--border-radius-element, 32px);
}
</style>