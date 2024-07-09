<template>
	<div>
		<ul>
			<li v-for="roll in rolls" :key="roll.uuid">
				<RouterLink
					:to="'/watch/' + roll.uuid"
					class="list-item tw-my-5 tw-flex tw-flex-col md:tw-flex-row tw-gap-2"
				>
					<div class="tw-w-60 tw-h-30">
						<img
							v-if="roll.thumbnail"
							:src="DAV_URL + '/files/' +roll.thumbnail"
							alt=""
							class="tw-w-full tw-h-full tw-object-cover tw-rounded-xl"
						/>
						<div v-else class="tw-bg-black"></div>
					</div>
					<div>
						<h2 class="tw-text-xl">{{ roll.title }}</h2>
						<div
							class="muted tw-text-muted tw-whitespace-pre-line tw-overflow-hidden tw-text-ellipsis"
						>
							{{ roll.description }}
						</div>
					</div>
				</RouterLink>
			</li>
		</ul>
	</div>
</template>

<script>
import { APP_API, DAV_URL, APP_URL } from "../constants";
import axios from "@nextcloud/axios";
import {
	NcListItem,
	NcActionButton,
	NcActions,
	NcLoadingIcon,
} from "@nextcloud/vue";
import MarkdownPreview from "./MarkdownPreview.vue";

export default {
	name: "ListRolls",
	components: {
		NcListItem,
		MarkdownPreview,
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
	},
	data() {
		return {
			rolls: [],
			DAV_URL,
			APP_URL,
		};
	},
	methods: {},
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