<template>
	<NcAppContent>
		<div id="scim_client">
			<h1>Hello world!</h1>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'

export default {
	name: 'App',
	components: {
		NcAppContent,
	},
}
</script>

<style scoped lang="scss">
#scim_client {
	display: flex;
	justify-content: center;
	margin: 16px;
}
</style>
