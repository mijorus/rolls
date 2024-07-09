<template>
	<div>
		<div>
			<Breadcrumbs />
		</div>
		<div>
			<ListRolls></ListRolls>
		</div>
	</div>
</template>

<script>
import ListRolls from '../components/ListRolls.vue';
import Breadcrumbs from './../components/Breadcrumbs.vue';

export default {
	name: 'Index',
	components: {
		Breadcrumbs,
		ListRolls
	}
}
</script>
