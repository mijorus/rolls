<template>
	<NcModal ref="modalRef" @close="closeModal">
		<div class="modal__content" style="padding: 50px; text-align: center">
			<div class="form-group">
				<NcSelect
					ref="userselect"
					:userSelect="true"
					input-id="user-select"
					:options="selectOptions"
					:inputLabel="t('files_sharing', 'Search for share recipients')"
					:placeholder="t('rolls', 'Name or email')"
				/>
			</div>

			<NcButton> Submit </NcButton>
		</div>
	</NcModal>
</template>

<script>
import { NcButton, NcSelect, NcModal } from "@nextcloud/vue";
import axios from "@nextcloud/axios";
import { SHARE_API_HEADERS, SHARE_API_URL } from "../constants";
import { removeUserPath } from "../utils/funcs";

export default {
	name: "SharePopup",
	components: {
		NcButton,
		NcModal,
		NcSelect,
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
			selectOptions: [],
		};
	},
	async mounted() {
		let path = removeUserPath(this.$props.path);

		const { data } = await axios.get(`${SHARE_API_URL}/shares`, {
			headers: SHARE_API_HEADERS,
			params: {
				path,
				format: "json",
				reshares: true,
			},
		});

		this.shareData = data.ocs.data;
		this.ready = true;
		document.querySelector('#user-select')?.addEventListener('keyup', this.getSharee)
		// this.selectOptions = this.shareData.map((el) => {
		// 	return {
		// 		id: el.id,
		// 		displayName: el.share_with_displayname,
		// 		isNoUser: false,
		// 		user: el.share_with,
		// 	};
		// });
	},
	methods: {
		async getSharee(e) {
			const query = e.target.value;

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

		closeModal() {
			this.$emit("close");
		},
	},
};
</script>
