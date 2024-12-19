<template>
	<div id="scim_client">
		<div class="section">
			<h2>
				<ScimClientIcon class="admin-settings-icon" />
				{{ t('scim_client', 'SCIM Client') }}
			</h2>
			<p>{{ t('scim_client', 'Use Nextcloud as an identity provider for external services using the SCIM standard.') }}</p>
		</div>
		<NcSettingsSection
			:name="t('scim_client', 'Registered Servers')"
			:aria-label="t('scim_client', 'Registered Servers')">
			<div class="server-list">
				<ul v-if="servers.length" :aria-label="t('scim_client', 'Registered servers list')">
					<ServerListItem
						v-for="server in servers"
						:key="server.id"
						:server="server"
						:save-options="saveOptions"
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
		</NcSettingsSection>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import FormatListBullet from 'vue-material-design-icons/FormatListBulleted.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import RegisterServerModal from './Server/RegisterServerModal.vue'
import ScimClientIcon from './icons/ScimClientIcon.vue'
import ServerListItem from './Server/ServerListItem.vue'

export default {
	name: 'AdminSettings',
	components: {
		FormatListBullet,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcSettingsSection,
		Plus,
		RegisterServerModal,
		ScimClientIcon,
		ServerListItem,
	},
	data() {
		return {
			registering: false,
			servers: [],
			showRegisterModal: false,
		}
	},
	mounted() {
		// TODO: get current server list from state
	},
	methods: {
		showRegister() {
			this.showRegisterModal = true
		},
		saveOptions(values) {
			// TODO: update selected server config to database
		},
		getAllServers() {
			// TODO: retrieve server list from database

			// return axios.get(generateUrl('/apps/scim_client/servers'))
			// .then(res => {
			// this.$emit('update:servers', res.data.servers)
			// })
		},
	},
}
</script>

<style scoped lang="scss">
#scim_client {
	h2 {
		display: flex;

		.admin-settings-icon {
			margin-right: 12px;
		}
	}

	.server-list {
		max-width: 75%;
		max-height: 300px;
		overflow-y: scroll;
	}

	.register-button {
		margin: 20px 0;
	}
}
</style>
