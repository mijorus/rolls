<template>
	<NcModal ref="modalRef" @close="closeModal">
		<div class="modal__content" style="padding: 50px; text-align: center">
			<label for="user-select" class="tw-mb-2">{{ t("rolls", "Search for share recipients") }}</label>
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
					<NcButton :disabled="!selectOptions.length" @click="() => shareWith()">{{
						t("rolls", "Add")
					}}</NcButton>
				</div>
			</div>
			<div class="tw-mt-10 tw-max-w-80 tw-m-auto share-list">
				<ul>
					<NcListItem
						v-for="share in shareData"
						:key="share.id"
						:name="share.share_with_displayname"
						counterType="highlighted"
						:force-display-actions="true"
					>
						<template #icon>
							<NcAvatar
								disable-menu
								:size="44"
								:user="share.share_with"
								:display-name="share.share_with_displayname"
							/>
						</template>
						<template #actions>
							<NcActionButton :disabled="busy" @click="() => removeShare(share)" v-if="share.can_delete">
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
			busy: false,
			ready: false,
			shareData: null,
			sharee: [],
			selectOptions: [],
			selectedOption: null,
		};
	},
	async mounted() {
		document.querySelector("#user-select")?.addEventListener("keyup", debounce(this.getSharee, 500));
		await this.getShares();
		this.ready = true;
	},
	destroyed() {
		document.querySelector("#user-select")?.removeEventListener("keyup", debounce);
	},
	methods: {
		async getShares() {
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
		},

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

			const currentShares = this.shareData.map((el) => el.share_with);

			this.selectOptions = this.sharee
				.filter((el) => {
					return !currentShares.includes(el.value.shareWith);
				})
				.map((el, i) => {
					return {
						id: i.toString(),
						subname: el.subline,
						displayName: el.shareWithDisplayNameUnique,
						isNoUser: false,
						user: el.value.shareWith,
					};
				});
		},

		async shareWith() {
			let path = removeUserPath(this.$props.path);
			const userUID = this.selectedOption.user;

			this.busy = true;
			const { data } = await axios.post(
				`${SHARE_API_URL}/shares`,
				{
					path,
					shareType: 0,
					permissions: 1,
					shareWith: userUID,
					attributes: '[{"enabled":true,"key":"download","scope":"permissions"}]',
				},
				{
					headers: SHARE_API_HEADERS,
				}
			);

			this.busy = false;
			this.selectedOption = null;
			this.selectOptions = [];

			this.getShares()
		},

		async removeShare(share) {
			if (!share.can_delete) {
				return;
			}

			this.busy = true;
			const { data } = await axios.delete(`${SHARE_API_URL}/shares/${share.id}`, {
				headers: SHARE_API_HEADERS,
			});

			await this.getShares()
			this.busy = false;
		},

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