<template>
	<div class="server-list-container">
		<h5>{{ t('scim_client', 'Registered Servers') }}</h5>
		<div class="server-list">
			<ul v-if="servers.length" :aria-label="t('scim_client', 'Registered servers list')">
				<ServerListItem
					v-for="server in servers"
					:key="server.id"
					:server="server"
					:servers="servers"
					:get-all-servers="getAllServers" />
			</ul>
			<NcEmptyContent
				v-else
				:name="t('scim_client', 'No servers configured')"
				:description="t('scim_client', 'Register a new server below and fill in the required details')">
				<template #icon>
					<FormatListBullet :size="20" />
				</template>
			</NcEmptyContent>
		</div>
		<NcButton
			type="primary"
			class="register-button"
			@click="showRegister">
			{{ t('app_api', 'Register') }}
			<template #icon>
				<Plus v-if="!registering" :size="20" />
				<NcLoadingIcon v-else :size="20" />
			</template>
		</NcButton>
		<RegisterServerModal :show.sync="showRegisterModal" :servers="servers" :get-all-servers="getAllServers" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import FormatListBullet from 'vue-material-design-icons/FormatListBulleted.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import RegisterServerModal from './RegisterServerModal.vue'
import ServerListItem from './ServerListItem.vue'

export default {
	name: 'ServerList',
	components: {
		FormatListBullet,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		Plus,
		RegisterServerModal,
		ServerListItem,
	},
	props: {
		servers: {
			type: Array,
			required: true,
			default: () => [],
		},
	},
	data() {
		return {
			registering: false,
			showRegisterModal: false,
		}
	},
	methods: {
		showRegister() {
			this.showRegisterModal = true
		},
		getAllServers() {
			return axios.get(generateUrl('/apps/scim_client/servers'))
				.then(res => this.$emit('update:servers', res.data))
		},
	},
}
</script>

<style scoped lang="scss">
.server-list {
	max-width: 75%;
	max-height: 300px;
	overflow-y: scroll;
}

.register-button {
	margin: 20px 0;
}
</style>
