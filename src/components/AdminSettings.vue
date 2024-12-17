<template>
	<div id="scim_client">
		<div class="section">
			<h2 style="display: flex;">
				<ScimClientIcon style="margin-right: 12px;" />
				{{ t('scim_client', 'SCIM Client') }}
			</h2>
			<p>{{ t('scim_client', 'Use Nextcloud as an identity provider for external services using the SCIM standard.') }}</p>
		</div>
		<NcSettingsSection
			:name="t('scim_client', 'Registered Servers')"
			:aria-label="t('scim_client', 'Registered Servers')" />
	</div>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import ScimClientIcon from './icons/ScimClientIcon.vue'

export default {
	name: 'AdminSettings',
	components: {
		NcSettingsSection,
		ScimClientIcon,
	},
}
</script>
