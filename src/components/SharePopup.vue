<template>
	<NcModal ref="modalRef" @close="closeModal">
		<div class="modal__content" style="padding: 50px; text-align: center">
			<label for="user-select" class="tw-mb-2">{{ t('rolls', 'Search for share recipients') }}</label>
			<div class="form-group tw-flex tw-flex-row tw-justify-center tw-items-end tw-gap-2">
				<NcSelect
					v-model="selectedOption"
					ref="userselect"
					:userSelect="true"
					:closeOnSelect="true"
					:multiple="false"
					input-id="user-select"
					:options="selectOptions"
					:labelOutside="true"
					:placeholder="t('rolls', 'Name or email')"
				/>
				<div>
					<NcButton>{{ t("rolls", "Add") }}</NcButton>
				</div>
			</div>
			<div class="tw-mt-10 tw-max-w-80 tw-m-auto share-list">
				<ul>
					<NcListItem
						v-for="user in shareData"
						:key="user.id"
						:name="user.share_with_displayname"
						counterType="highlighted"
						:force-display-actions="true"
					>
						<template #icon>
							<NcAvatar
								disable-menu
								:size="44"
								:user="user.share_with"
								:display-name="user.share_with_displayname"
							/>
						</template>
						<template #actions>
							<NcActionButton @click="() => removeShare(user)">
								<template #icon>
									<Delete :size="20" />
								</template>
								{{ t("rolls", "Delete") }}
							</NcActionButton>
						</template>
					</NcListItem>
				</ul>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { NcButton, NcSelect, NcModal, NcListItem, NcAvatar, NcActionButton } from "@nextcloud/vue";
import axios from "@nextcloud/axios";
import { SHARE_API_HEADERS, SHARE_API_URL } from "../constants";
import { debounce, removeUserPath } from "../utils/funcs";
import Delete from "vue-material-design-icons/Delete.vue";

export default {
	name: "SharePopup",
	components: {
		NcButton,
		NcModal,
		NcSelect,
		NcListItem,
		NcAvatar,
		Delete,
		NcActionButton,
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
			selectedOption: [],
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
		document.querySelector("#user-select")?.addEventListener("keyup", debounce(this.getSharee, 500));
	},
	methods: {
		async getSharee(e) {
			const query = e.target.value;

			if (query.lenght < 2) {
				return;
			}

			this.sharee = [];
			const { data } = await axios.get(`${SHARE_API_URL}/sharees`, {
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
			this.selectOptions = this.sharee.map((el, i) => {
				return {
					id: i.toString(),
					subname: el.subline,
					displayName: el.shareWithDisplayNameUnique,
					isNoUser: false,
					user: el.label,
				};
			});
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

		async removeShare(user) {},

		closeModal() {
			this.$emit("close");
		},
	},
};
</script>

<style >
</style>

<style>
.share-list .list-item {
	background-color: var(--color-background-hover) !important;
}
</style>