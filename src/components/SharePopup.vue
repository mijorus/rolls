<template>
	<div class="tw-mb-10">
		<router-link to="/record">
			<NcButton v-if="!isMobile">
				<template #icon>
					<Plus :size="20" />
				</template>
				{{ t("rolls", "New roll") }}
			</NcButton>
		</router-link>
	</div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import axios from "@nextcloud/axios";
import { SHARE_API_HEADERS, SHARE_API_URL } from "../constants";

export default {
	name: "SharePopup",
	components: {
		NcButton,
	},
	props: {
		path: {
			type: String,
		},
		itemType: {
			type: String,
		},
	},
	data() {
		return {
			ready: false,
			shareData: null,
			sharee: [],
		};
	},
	async mounted() {
		const { data } = await axios.get(`${SHARE_API_URL}`, {
			headers: SHARE_API_HEADERS,
			params: {
				format: "json",
				path: this.$props.path,
				reshares: true,
			},
		});

		this.shareData = data.ocs.data;
		this.ready = true;
	},
	methods: {
		async getSharee(query) {
			this.sharee = [];
			const { data } = await axios.get(`${SHARE_API_URL}/sharee`, {
				headers: SHARE_API_HEADERS,
				params: {
					itemType: this.$props.itemType,
					format: "json",
					search: query,
					perPage: 25,
					lookup: false,
				},
			});

			this.sharee = data.ocs.data.users;
		},

		async shareWith(userUID) {
			const { data } = await axios.post(
				`${SHARE_API_URL}/sharee`,
				{
					path: this.$props.path,
					shareType: 0,
					permissions: 1,
					shareWith: userUID,
					attributes: '[{"enabled":true,"key":"download","scope":"permissions"}]',
				},
				{
					headers: SHARE_API_HEADERS,
				}
			);
		},
	},
};
</script>
