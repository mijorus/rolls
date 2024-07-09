<template>
	<div class="tw-mb-10">
		<router-link to="/record">
			<NcButton>
				<template #icon>
					<Plus :size="20" />
				</template>
				{{ t('rolls', 'New Roll') }}
			</NcButton>
		</router-link>
	</div>
</template>

<script>
import Plus from 'vue-material-design-icons/Plus.vue';
import { NcButton } from '@nextcloud/vue';

export default {
	name: 'Breadcrumbs',
	components: {
		Plus,
		NcButton,
	},
};
</script>
