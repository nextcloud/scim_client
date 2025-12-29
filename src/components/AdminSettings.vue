<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection
		:name="t('scim_client', 'SCIM Client')"
		:description="t('scim_client', 'Use Nextcloud as an identity provider for external services using the SCIM standard.')"
		:aria-label="t('scim_client', 'SCIM Client')">
		<ServerList v-model:servers="servers" />
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

import ServerList from './Server/ServerList.vue'

export default {
	name: 'AdminSettings',
	components: {
		NcSettingsSection,
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
