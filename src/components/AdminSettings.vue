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
			<ServerList :servers.sync="servers" />
		</NcSettingsSection>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import ScimClientIcon from './icons/ScimClientIcon.vue'
import ServerList from './Server/ServerList.vue'

export default {
	name: 'AdminSettings',
	components: {
		NcSettingsSection,
		ScimClientIcon,
		ServerList,
	},
	data() {
		return {
			servers: [],
		}
	},
	mounted() {
		this.servers = loadState('scim_client', 'admin-server-list')
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
}
</style>
