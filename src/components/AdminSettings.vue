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
			:aria-label="t('scim_client', 'Registered Servers')">
			<NcEmptyContent
				:name="t('scim_client', 'No servers configured')"
				:description="t('scim_client', 'Register a new server below and fill in the required details')">
				<template #icon>
					<FormatListBullet :size="20" />
				</template>
			</NcEmptyContent>
			<NcButton
				type="primary"
				style="margin: 20px 0;"
				@click="showRegister">
				{{ t('app_api', 'Register') }}
				<template #icon>
					<Plus v-if="!registering" :size="20" />
					<NcLoadingIcon v-else />
				</template>
			</NcButton>
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

import ScimClientIcon from './icons/ScimClientIcon.vue'

export default {
	name: 'AdminSettings',
	components: {
		FormatListBullet,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcSettingsSection,
		Plus,
		ScimClientIcon,
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
	},
}
</script>
